<?php

namespace AffiliWCTracker;


require_once 'Woocommerce.php';
require_once 'Installer.php';


use AffiliWCTracker\Woocommerce as AffiliWCTracker_Woocommerce;

class Action
{
    protected $plugin_name = 'affili_wc_tracker';

    public function __construct()
    {
        //
    }

    public function init()
    {
        load_plugin_textdomain('affili', false, 'affili/languages');
    }

    public function menu()
    {
        $page_title = __('Affili Plugin', $this->plugin_name);
        $menu_title = __('Affili', $this->plugin_name);
        $capability = 'manage_options';
        $menu_slug  = $this->plugin_name;
        $function   = [$this, 'renderPage'];
        $icon_url   = 'data:image/svg+xml;base64,'. base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 173.16 173.16"><defs><style>.cls-1{fill:#fff;fill-rule:evenodd;}</style></defs><title>Asset 4</title><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path class="cls-1" d="M173.16,150.47V86.58A86.59,86.59,0,1,0,134,159V115.45c0-3.75-5.22-13.31-12-13.31v8.76c0,10.8-15.87,30.24-47.25,30.24-27.11,0-52-24.37-52-54.56a63.89,63.89,0,0,1,127.78,0v63.89a22.69,22.69,0,0,0,22.69,22.69Z"/><path class="cls-1" d="M130.22,92.17a7,7,0,1,0-7-7A7,7,0,0,0,130.22,92.17Z"/></g></g></svg>');
        $position   = 100;

        add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $function,
            $icon_url,
            $position
        );
    }

    public function renderPage()
    {
        $account_id  = $this->getAccountId();
        $custom_code = $this->getCustomCode();
        $plugin_name = $this->plugin_name;

        include_once __DIR__.'/../views/form.php';
    }

    public function loadAdminStyles()
    {
        wp_enqueue_style( 'affili-wc-tracker-admin-style', plugins_url('assets/css/admin-style-main.css',__DIR__), false, '2.0.0' );

        wp_enqueue_script('affili-wc-tracker-admin-script',  plugins_url('assets/js/admin-script-main.js', __DIR__), array( 'jquery') );
    }

    public function setSettings()
    {
        $nonce     = wp_verify_nonce($_POST['affili_set_settings'], 'eadkf#adk$fawlkaawwlRRe');
        $condition = isset($_POST['affili_set_settings']) && $nonce;

        if($condition) {
            $account_id = sanitize_text_field($_POST['account_id']);
            $custom_code = $_POST['custom_code'];


            update_option($this->plugin_name.'_account_id', $account_id);
            update_option($this->plugin_name.'_custom_code', $custom_code);

            $admin_notice = "success";
            $message      = __('Data saved successful.', $this->plugin_name);

            $this->customRedirect($message, $admin_notice);
            exit;
        }
        else {
            wp_die(
                __( 'Invalid nonce specified', $this->plugin_name ),
                __( 'Error', $this->plugin_name ),
                [
                    'response' 	=> 403,
                    'back_link' => 'admin.php?page=' . $this->plugin_name,
                ]
            );
        }
    }

    public function displayFlashNotices() {
        $notices = get_option($this->plugin_name.'_flash_notices', []);

        // Iterate through our notices to be displayed and print them.
        foreach ($notices as $notice) {
            printf('<div class="notice notice-%1$s %2$s" style="margin:25px 0px 0 20px;"><p>%3$s</p></div>',
                $notice['type'],
                $notice['dismissible'],
                $notice['notice']
            );
        }

        // We reset our options to prevent notices being displayed forever.
        if(!empty($notices)) {
            delete_option($this->plugin_name.'_flash_notices', []);
        }
    }

    public function setAffiliJs()
    {
        $script = $this->createInlineScript();

        wp_enqueue_script("affili-wc-tracker-script", "https://analytics.affili.ir/scripts/affili-v2.js");
        wp_add_inline_script("affili-wc-tracker-script", $script);
    }

    public function createInlineScript()
    {
        $account_id = $this->getAccountId();

        $script = '';

        if($account_id) {
            $script .= 'window.affiliData = window.affiliData || [];function affili(){affiliData.push(arguments);}'.PHP_EOL;
            $script .= 'affili("create", "'.$account_id.'");'.PHP_EOL;
            $script .= 'affili("detect");'.PHP_EOL;

            $custom_code = $this->getCustomCode();
            if($custom_code) {
                $script .= $custom_code;
            }
        }

        return $script;
    }

    public function trackOrders($order_id)
    {
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($GLOBALS['order-received']));
        $order_key = apply_filters('woocommerce_thankyou_order_key', empty($_GET['key']) ? '' : wc_clean($_GET['key']));
        $woocommerce = new AffiliWCTracker_Woocommerce;
        $order = wc_get_order($order_id);

        if ($order_id <= 0) return;

        $order_key_check = $woocommerce->isWoo3() ? $order->get_order_key() : $order->order_key;

        if ($order_key_check != $order_key) return;

        $data = $woocommerce->getOrderData($order);

        $order_id  = $data['order_id'];
        $amount = $data['amount'];
        $options = [
            'coupon' => $data['coupon'],
            'products' => $data['products']
        ];
        $options = count($options) ? json_encode($options) : json_encode($options, JSON_FORCE_OBJECT);

        $script = "affili('conversion', '{$order_id}', '{$amount}', {$options})";

        wp_add_inline_script("affili-wc-tracker-script", $script);
    }

    public function loadTextDomain()
    {
        $lang_dir = AFFILI_WC_TRACKER_BASENAME.'/languages/';
        load_plugin_textdomain($this->plugin_name, false, $lang_dir);
    }

    public function setup()
    {
        add_action('plugins_loaded', [$this, 'loadTextDomain']); // load plugin translation file

        add_action('admin_menu', [$this, 'menu']);
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'loadAdminStyles']);
        add_action('admin_post_set_settings', [$this, 'setSettings']);

        add_action('admin_notices', [$this, 'displayFlashNotices'], 12);
        add_action('wp_head', [$this, 'setAffiliJs'] );

        add_action('woocommerce_thankyou', [$this, 'trackOrders']);
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

    protected function getAccountId()
    {
        return get_option($this->plugin_name.'_account_id');
    }

    protected function getCustomCode()
    {
        $result = get_option($this->plugin_name.'_custom_code');

        if($result) {
            $result = stripslashes($result);
        }

        return $result;
    }

    protected function customRedirect($message, $admin_notice = 'success')
    {
        $this->addFlashNotice(
            $message, $admin_notice, true
        );

        wp_redirect('admin.php?page='.$this->plugin_name);
    }

    protected function addFlashNotice($notice = '', $type = 'success', $dismissible = true ) {
        $notices = get_option($this->plugin_name.'_flash_notices', []);

        $dismissible_text = $dismissible ? 'is-dismissible' : '';

        array_push($notices, [
            'notice'        => $notice,
            'type'          => $type,
            'dismissible'   => $dismissible_text
        ]);

        // We update the option with our notices array
        update_option($this->plugin_name.'_flash_notices', $notices );
    }
}