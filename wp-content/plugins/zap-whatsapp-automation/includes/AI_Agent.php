<?php
namespace ZapWA;

if (!defined('ABSPATH')) { exit; }

class AI_Agent {

    public static function create_tables() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $agents_table = $wpdb->prefix . 'zapwa_ai_agents';
        $sql_agents = "CREATE TABLE $agents_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL DEFAULT '',
            provider VARCHAR(20) NOT NULL DEFAULT 'openai',
            model VARCHAR(100) NOT NULL DEFAULT '',
            system_prompt LONGTEXT NULL,
            temperature DECIMAL(3,2) NOT NULL DEFAULT 0.70,
            memory_enabled TINYINT(1) NOT NULL DEFAULT 0,
            voice_enabled TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider (provider)
        ) $charset;";

        $memory_table = $wpdb->prefix . 'zapwa_ai_memory';
        $sql_memory = "CREATE TABLE $memory_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contact_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            agent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY contact_id (contact_id),
            KEY agent_id (agent_id),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_agents);
        dbDelta($sql_memory);
    }

    public static function get_agent($agent_id) {
        global $wpdb;

        $agent_id = absint($agent_id);
        if (!$agent_id) return null;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}zapwa_ai_agents WHERE id = %d LIMIT 1",
                $agent_id
            )
        );
    }

    public static function get_all_agents() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}zapwa_ai_agents ORDER BY name ASC"
        );

        return is_array($results) ? $results : [];
    }

    public static function run($agent_id, $contact_id, $prompt, $extra_context = []) {
        $agent = self::get_agent($agent_id);
        if (!$agent) {
            return ['text' => null, 'audio_url' => null, 'error' => 'Agent not found.'];
        }

        $contact_id = absint($contact_id);

        // Build messages array with system prompt
        $messages = [];
        if (!empty($agent->system_prompt)) {
            $messages[] = ['role' => 'system', 'content' => $agent->system_prompt];
        }

        // Inject conversation memory
        if ($agent->memory_enabled) {
            $history = self::get_memory($contact_id, $agent_id, 20);
            foreach ($history as $entry) {
                $messages[] = ['role' => $entry->role, 'content' => $entry->content];
            }
        }

        // Append current user prompt
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Call the appropriate provider
        $response_text = null;
        if ($agent->provider === 'gemini') {
            $response_text = self::call_gemini($agent, $messages);
        } else {
            $response_text = self::call_openai($agent, $messages);
        }

        if ($response_text === null) {
            return ['text' => null, 'audio_url' => null, 'error' => 'AI provider returned no response.'];
        }

        // Persist conversation turns
        self::save_memory($contact_id, $agent_id, 'user', $prompt);
        self::save_memory($contact_id, $agent_id, 'assistant', $response_text);

        // Optionally generate audio
        $audio_url = null;
        if ($agent->voice_enabled && get_option('zapwa_elevenlabs_api_key', '')) {
            $audio_url = self::text_to_speech($response_text, $agent);
        }

        return ['text' => $response_text, 'audio_url' => $audio_url, 'error' => null];
    }

    private static function call_openai($agent, $messages) {
        $api_key = get_option('zapwa_openai_api_key', '');
        if (empty($api_key)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('AI_Agent: OpenAI API key not configured.');
            }
            return null;
        }

        $body = wp_json_encode([
            'model'       => $agent->model ?: 'gpt-3.5-turbo',
            'messages'    => $messages,
            'temperature' => (float) $agent->temperature,
        ]);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('AI_Agent: OpenAI request failed.', ['error' => $response->get_error_message()]);
            }
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('AI_Agent: OpenAI response received.', ['http_code' => wp_remote_retrieve_response_code($response)]);
        }

        return $data['choices'][0]['message']['content'] ?? null;
    }

    private static function call_gemini($agent, $messages) {
        $api_key = get_option('zapwa_gemini_api_key', '');
        if (empty($api_key)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('AI_Agent: Gemini API key not configured.');
            }
            return null;
        }

        // Convert OpenAI-style messages to Gemini contents format.
        // System prompt is prepended to the first user turn.
        $contents      = [];
        $system_inject = '';

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system_inject = $msg['content'] . "\n\n";
                continue;
            }

            $gemini_role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $text        = ($gemini_role === 'user' && !empty($system_inject))
                ? $system_inject . $msg['content']
                : $msg['content'];

            // Flush system inject after first user message
            if ($gemini_role === 'user') {
                $system_inject = '';
            }

            $contents[] = [
                'role'  => $gemini_role,
                'parts' => [['text' => $text]],
            ];
        }

        $model = $agent->model ?: 'gemini-pro';
        $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $body = wp_json_encode([
            'contents'           => $contents,
            'generationConfig'   => ['temperature' => (float) $agent->temperature],
        ]);

        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('AI_Agent: Gemini request failed.', ['error' => $response->get_error_message()]);
            }
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('AI_Agent: Gemini response received.', ['http_code' => wp_remote_retrieve_response_code($response)]);
        }

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    private static function text_to_speech($text, $agent) {
        $api_key = get_option('zapwa_elevenlabs_api_key', '');
        if (empty($api_key)) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        $audio_dir  = $upload_dir['basedir'] . '/zapwa-audio';

        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }

        $filename = 'zapwa-tts-' . wp_hash(uniqid('zapwa', true) . mt_rand()) . '.mp3';
        $filepath = $audio_dir . '/' . $filename;

        // Voice ID is configurable; falls back to the ElevenLabs default "Rachel" voice.
        $voice_id = get_option('zapwa_elevenlabs_voice_id', '21m00Tcm4TlvDq8ikWAM');

        $response = wp_remote_post(
            'https://api.elevenlabs.io/v1/text-to-speech/' . rawurlencode($voice_id),
            [
                'timeout' => 30,
                'headers' => [
                    'xi-api-key'   => $api_key,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'audio/mpeg',
                ],
                'body' => wp_json_encode([
                    'text'          => $text,
                    'model_id'      => 'eleven_monolingual_v1',
                    'voice_settings' => ['stability' => 0.5, 'similarity_boost' => 0.5],
                ]),
            ]
        );

        if (is_wp_error($response)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('AI_Agent: ElevenLabs TTS request failed.', ['error' => $response->get_error_message()]);
            }
            return null;
        }

        $audio_data = wp_remote_retrieve_body($response);
        if (empty($audio_data)) {
            return null;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        if (file_put_contents($filepath, $audio_data) === false) {
            return null;
        }

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('AI_Agent: TTS audio saved.', ['file' => $filename]);
        }

        return $upload_dir['baseurl'] . '/zapwa-audio/' . $filename;
    }

    private static function save_memory($contact_id, $agent_id, $role, $content) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'zapwa_ai_memory',
            [
                'contact_id' => absint($contact_id),
                'agent_id'   => absint($agent_id),
                'role'       => sanitize_text_field($role),
                'content'    => $content,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    private static function get_memory($contact_id, $agent_id, $limit = 20) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT role, content FROM {$wpdb->prefix}zapwa_ai_memory
                 WHERE contact_id = %d AND agent_id = %d
                 ORDER BY created_at DESC LIMIT %d",
                absint($contact_id),
                absint($agent_id),
                absint($limit)
            )
        );

        if (!is_array($results)) {
            return [];
        }

        // Return in chronological order (oldest first)
        return array_reverse($results);
    }
}
