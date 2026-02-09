<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class History {

    public static function render() {
        echo '<div class="wrap">';
        echo '<h1>Histórico</h1>';
        echo '<p>Preparado para auditoria futura.</p>';
        echo '</div>';
    }
}
