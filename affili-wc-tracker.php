<?php
/**
 * @link              https://affili.ir
 * @since             1.0.0
 * @package           AffiliWCTracker
 *
 * Plugin Name:       Affili Plugin
 * Plugin URI:        https://github.com/affili-ir/wordpress
 * Description:       The WordPress plugin for Affili's merchants.
 * Version:           2.0.0
 * Author:            Affili IR
 * Author URI:        https://affili.ir
 * License:           GPLv2 or later
 * License URI:       https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * Text Domain:       affili_wc_tracker
 * Domain Path:       /languages
 */

if( !defined('ABSPATH') ) {
    exit;
}

if (!defined('AFFILI_WC_TRACKER_FILE_DIR')) {
    define('AFFILI_WC_TRACKER_FILE_DIR', __FILE__);
}

if (!defined('AFFILI_WC_TRACKER_PLUGIN_DIR')) {
    define('AFFILI_WC_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('AFFILI_WC_TRACKER_BASENAME')) {
    define('AFFILI_WC_TRACKER_BASENAME', plugin_basename(AFFILI_WC_TRACKER_PLUGIN_DIR));
}

include_once __DIR__.'/classes/Action.php';
include_once __DIR__.'/classes/Installer.php';

register_activation_hook(AFFILI_WC_TRACKER_FILE_DIR, ['AffiliWCTracker\Installer', 'activation']);
register_deactivation_hook(AFFILI_WC_TRACKER_FILE_DIR, ['AffiliWCTracker\Installer', 'deactivation']);

AffiliWCTracker\Action::factory();