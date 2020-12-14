<?php

namespace AffiliIR;


class ActivePluginsCheck
{
	private static $active_plugins;

    public static function init()
    {
        self::$active_plugins = (array) get_option('active_plugins', []);

        if(is_multisite()) {
            self::$active_plugins = array_merge(
                self::$active_plugins,
                get_site_option('active_sitewide_plugins', [])
            );
        }
    }

    public static function woocommerceActiveCheck() {

		if(!self::$active_plugins) {
            self::init();
        }

        return in_array('woocommerce/woocommerce.php', self::$active_plugins)
            || array_key_exists('woocommerce/woocommerce.php', self::$active_plugins)
        ;
    }

    public static function wooBrandActiveCheck()
    {
        if(!self::$active_plugins) {
            self::init();
        }

        return in_array('woo-brand/main.php', self::$active_plugins)
            || array_key_exists('woo-brand/main.php', self::$active_plugins)
        ;
    }
}