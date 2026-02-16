<?php
/**
 * Other code...
 */

// Definir sempre a variável antes de usar
$instance_name = get_option('zapwa_evolution_instance', '');

// Update style attribute
echo "style=\"" . ($instance_name ? "" : "display: none;") . "\"";
