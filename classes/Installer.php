<?php

namespace AffiliIR;


require_once ABSPATH.'wp-admin/includes/upgrade.php';

class Installer
{
    public static $table         = 'affili';
    public static $table_version = '1.1.0';

    public static function activation()
    {
        $db_version = get_option(self::$table.'_db_version', 0);
        if ($db_version >= self::$table_version) {
            return;
        }

        $sql = self::sqlString();

        dbDelta($sql);
        update_option(self::$table.'_db_version', $db_version);
    }

    public static function deactivation()
    {
        //
    }

    public static function sqlString()
    {
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table;

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
                name varchar(255) NOT NULL,
                value text DEFAULT '' NOT NULL,
                PRIMARY KEY id (id),
                UNIQUE (name)
            );"
        ;

        return $sql;
    }
}