<?php

if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto (Agencycoders)
}

class Logger {
    private $log_file;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = plugin_dir_path(__FILE__) . '../logs/plugin.log';
    }

    public function log($message) {
        if (!file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }

        $timestamp = date("Y-m-d H:i:s");
        $log_message = $timestamp . ' - ' . $message . PHP_EOL;

        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
}