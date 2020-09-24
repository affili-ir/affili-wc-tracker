<?php

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class Installer
{
    private $table_name;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb       = $wpdb;
        $this->table_name = $wpdb->prefix . 'affili';
    }

    public function activation()
    {
        $db_version      = '1.0.0';
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                id bigint NOT NULL AUTO_INCREMENT,
                created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
                name varchar(255) NOT NULL,
                value tinytext DEFAULT '' NOT NULL,
                UNIQUE KEY id (id),
                UNIQUE (name)
            ) {$charset_collate};"
        ;

        dbDelta($sql);
        add_option('affili_db_version', $db_version);
    }

    public function deactivation()
    {
        $sql = "DROP TABLE IF EXISTS {$this->table_name}";
        $this->wpdb->query($sql);

        delete_option("affili_db_version");
    }

    public function setup()
    {
        register_activation_hook(AFFILI_FILE_URL, [$this, 'activation']);
        register_deactivation_hook(AFFILI_FILE_URL, [$this, 'deactivation']);
    }

    public static function factory()
    {
        static $instance;

        if(!$instance) {
            $instance = new static;

            $instance->setup();
        }

        return $instance;
    }
}