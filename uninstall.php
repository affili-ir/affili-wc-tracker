<?php
/**
 * Uninstall Affili Tracker
 *
 * @package     Affili merchant's tracker
 * @subpackage  Uninstall
 * @license     https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * @since       1.1.0
 */


if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
include_once __DIR__.'/classes/Installer.php';

$table_name = $wpdb->prefix.AffiliIR\Installer::$table;

$sql = "DROP TABLE IF EXISTS {$table_name}";
$wpdb->query($sql);

delete_option(AffiliIR\Installer::$table.'_db_version');