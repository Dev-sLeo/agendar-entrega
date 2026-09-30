<?php

/**
 * Plugin Name: Agendar Entregas
 * Description: Agendamento de entregas no checkout do WooCommerce, com turnos, limite de vagas por dia e calendário administrativo.
 * Version: 1.0.1
 * Author: Upsites
 * Text Domain: agendar-entregas
 * Requires Plugins: woocommerce
 */

if (! defined('ABSPATH')) {
	exit;
}

define('AE_PLUGIN_FILE', __FILE__);
define('AE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AE_VERSION', '1.0.0');

require_once AE_PLUGIN_DIR . 'includes/class-ae-plugin.php';

register_activation_hook(__FILE__, array('AE_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('AE_Plugin', 'deactivate'));

add_action('plugins_loaded', array('AE_Plugin', 'instance'));
