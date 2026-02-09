<?php
/**
 * Cloudflare Turnstile Service
 *
 * @package MagicLogin
 */

namespace MagicLogin;

/**
 * Class CloudflareTurnstileService
 */
class CloudflareTurnstileService {
	/**
	 * Site key
	 *
	 * @var string
	 */
	private $site_key;

	/**
	 * Secret key
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Constructor
	 *
	 * @param string $site_key   Site key
	 * @param string $secret_key Site secret
	 */
	public function __construct( $site_key, $secret_key ) {
		$this->site_key   = $site_key;
		$this->secret_key = $secret_key;
	}

	/**
	 * Render the Turnstile
	 *
	 * @return string
	 */
	public function render() {
		return '<div class="magic-login-cf-turnstile" id="magic-login-cf-turnstile-container" data-sitekey="' . $this->site_key . '"></div>';
	}

	/**
	 * Verify the token
	 *
	 * @param string $token Token
	 *
	 * @return bool
	 */
	public function verify( $token ) {
		$url      = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		$response = wp_remote_post(
			$url,
			array(
				'body' => array(
					'secret'   => $this->secret_key,
					'response' => $token,
				),
			)
		);

		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body, true );

		return isset( $result['success'] ) && $result['success'];
	}

	/**
	 * Get the script URL
	 *
	 * @return string
	 */
	public function get_script_url() {
		return 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
	}

	/**
	 * Get the inline script
	 *
	 * @return string
	 */
	public function get_inline_script() {
		return <<<EOT
				document.addEventListener('DOMContentLoaded', function() {
					if (typeof turnstile !== 'undefined' && document.querySelectorAll('.magic-login-cf-turnstile').length) {
					    var response = document.querySelector('#magic-login-cf-turnstile-container input[name="magic-login-cf-turnstile-response"]');

					    if (response) {
					        // Reset the Turnstile if a response is found
					        turnstile.reset('#magic-login-cf-turnstile-container');
					    } else {
					        // Iterate through each .magic-login-cf-turnstile and render Turnstile
					        document.querySelectorAll('.magic-login-cf-turnstile').forEach(function (element) {
					            // Check if it's already rendered using a custom data attribute
					            if (!element.getAttribute('data-magic-login-turnstile-rendered')) {
					                turnstile.render(element, {
					                    'sitekey': element.getAttribute('data-sitekey'),
					                    'response-field-name': 'magic-login-cf-turnstile-response'
					                });
					                // Mark the element as rendered
					                element.setAttribute('data-magic-login-turnstile-rendered', 'true');
					            }
					        });
					    }
					}
			});
		EOT;
	}
}
