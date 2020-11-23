<?php
/**
 * @link              https://affili.ir
 * @since             1.0.0
 * @package           AffiliIR
 *
 * Plugin Name:       affili_ir
 * Plugin URI:        https://github.com/affili-ir/wordpress
 * Description:       The WordPress plugin for Affili's merchants.
 * Version:           1.1.0
 * Author:            Affili IR
 * Author URI:        https://affili.ir
 * License:           GPLv2 or later
 * License URI:       https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * Text Domain:       affili_ir
 * Domain Path:       /languages
 */

if( !defined('ABSPATH') ) {
    exit;
}

if (!defined('AFFILI_FILE_DIR')) {
    define('AFFILI_FILE_DIR', __FILE__);
}

if (!defined('AFFILI_PLUGIN_DIR')) {
    define('AFFILI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('AFFILI_BASENAME')) {
    define('AFFILI_BASENAME', plugin_basename(AFFILI_PLUGIN_DIR));
}

include_once __DIR__.'/classes/Action.php';
include_once __DIR__.'/classes/Installer.php';

register_activation_hook(AFFILI_FILE_DIR, ['AffiliIR\Installer', 'activation']);
register_deactivation_hook(AFFILI_FILE_DIR, ['AffiliIR\Installer', 'deactivation']);

AffiliIR\Action::factory();