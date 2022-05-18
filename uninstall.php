<?php
/**
 * Uninstall Affili WC Tracker
 *
 * @package     Affili WC tracker
 * @subpackage  Uninstall
 * @license     https://github.com/affili-ir/affili-wc-tracker/blob/master/LICENSE
 * @since       1.1.0
 */


if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
include_once __DIR__.'/classes/Installer.php';