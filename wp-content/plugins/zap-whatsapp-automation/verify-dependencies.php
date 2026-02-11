<?php
/**
 * Verificador de Dependências
 * Execute este arquivo para garantir que vendor/ está funcionando
 * 
 * Acesse: https://seusite.com/wp-content/plugins/zap-whatsapp-automation/verify-dependencies.php
 */

// Prevenir acesso direto se WordPress estiver disponível
if (file_exists(__DIR__ . '/../../../wp-load.php')) {
    require_once __DIR__ . '/../../../wp-load.php';
    
    if (!current_user_can('manage_options')) {
        wp_die('Você não tem permissão para acessar esta página.');
    }
}

echo '<h1>🔍 Verificação de Dependências - ZAP WhatsApp Automation</h1>';

// 1. Verificar PHP
echo '<h2>1. Versão do PHP</h2>';
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
echo '<p>Versão: <strong>' . $php_version . '</strong> ';
echo $php_ok ? '✅ OK' : '❌ Requer PHP >= 7.4';
echo '</p>';

// 2. Verificar vendor/autoload.php
echo '<h2>2. Autoloader do Composer</h2>';
$autoload_path = __DIR__ . '/vendor/autoload.php';
$autoload_exists = file_exists($autoload_path);
echo '<p>Arquivo: <code>' . $autoload_path . '</code> ';
echo $autoload_exists ? '✅ Existe' : '❌ Não encontrado';
echo '</p>';

if (!$autoload_exists) {
    echo '<p><strong>ERRO:</strong> vendor/autoload.php não encontrado!</p>';
    echo '<p>Solução: Execute <code>composer install</code> ou baixe o plugin completo.</p>';
    exit;
}

// 3. Carregar autoload
require_once $autoload_path;

// 4. Verificar classe QRCode
echo '<h2>3. Biblioteca chillerlan/php-qrcode</h2>';
$qrcode_class_exists = class_exists('chillerlan\\QRCode\\QRCode');
echo '<p>Classe <code>chillerlan\\QRCode\\QRCode</code> ';
echo $qrcode_class_exists ? '✅ Carregada' : '❌ Não encontrada';
echo '</p>';

// 5. Verificar classe QROptions
$qroptions_class_exists = class_exists('chillerlan\\QRCode\\QROptions');
echo '<p>Classe <code>chillerlan\\QRCode\\QROptions</code> ';
echo $qroptions_class_exists ? '✅ Carregada' : '❌ Não encontrada';
echo '</p>';

// 6. Testar geração de QR Code
echo '<h2>4. Teste de Geração de QR Code</h2>';
try {
    $options = new \chillerlan\QRCode\QROptions([
        'version'      => 5,
        'outputType'   => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'     => \chillerlan\QRCode\QRCode::ECC_L,
        'scale'        => 3,
        'imageBase64'  => true,
    ]);
    
    $qrcode = new \chillerlan\QRCode\QRCode($options);
    $qr_base64 = $qrcode->render('https://github.com/chillerlan/php-qrcode');
    
    echo '<p>✅ QR Code gerado com sucesso!</p>';
    echo '<img src="' . $qr_base64 . '" alt="QR Code de Teste">';
    echo '<p><small>Escaneie este QR Code - deve abrir: https://github.com/chillerlan/php-qrcode</small></p>';
    
} catch (Exception $e) {
    echo '<p>❌ Erro ao gerar QR Code: ' . $e->getMessage() . '</p>';
}

// 7. Verificar extensões PHP
echo '<h2>5. Extensões PHP</h2>';
$extensions = ['mbstring', 'gd', 'imagick'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo '<p>Extensão <code>' . $ext . '</code> ';
    
    if ($ext === 'gd' || $ext === 'imagick') {
        echo $loaded ? '✅ Carregada' : '⚠️ Não carregada (opcional, mas recomendado)';
    } else {
        echo $loaded ? '✅ Carregada' : '❌ Não carregada (OBRIGATÓRIO)';
    }
    echo '</p>';
}

// 8. Resumo
echo '<hr>';
echo '<h2>📊 Resumo</h2>';

$all_ok = $php_ok && $autoload_exists && $qrcode_class_exists && $qroptions_class_exists;

if ($all_ok) {
    echo '<p><strong>✅ TUDO OK!</strong></p>';
    echo '<p>O plugin está pronto para uso. Todas as dependências estão instaladas corretamente.</p>';
} else {
    echo '<p><strong>❌ PROBLEMAS DETECTADOS</strong></p>';
    echo '<p>Corrija os erros acima antes de usar o plugin.</p>';
}

echo '<hr>';
echo '<p><small>ZAP WhatsApp Automation - Verificação de Dependências v1.0</small></p>';
