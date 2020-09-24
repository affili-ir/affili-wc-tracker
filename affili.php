<?php

/**
 * @link              https://affili.ir
 * @since             1.0.0
 * @package           Affili
 *
 * Plugin Name:       affili
 * Plugin URI:        https://github.com/affili-ir/wordpress
 * Description:       The WordPress plugin for Affili's merchants.
 * Version:           1.0.0
 * Author:            affili
 * Author URI:        https://affili.ir
 * License:           MIT
 * License URI:       https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * Text Domain:       affili
 * Domain Path:       /affili
 */

if( !defined('ABSPATH') ) {
    exit;
}

define('AFFILI_FILE_URL', __FILE__);

include_once __DIR__.'/classes/Action.php';
include_once __DIR__.'/classes/Installer.php';

Action::factory();
Installer::factory();