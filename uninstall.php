<?php
/**
 * Uninstall Affili Tracker
 *
 * @package     Takhfifan merchant's tracker
 * @subpackage  Uninstall
 * @license     https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * @since       1.1.0
 */


if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}


global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.takhfifan\main::$table);

global $wpdb;

$table_name = $wpdb->prefix.classes\Installer::$table;

$sql = "DROP TABLE IF EXISTS {$table_name}";
$wpdb->query($sql);

delete_option(classes\Installer::$table.'_db_version');