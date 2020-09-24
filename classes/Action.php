<?php

class Action
{
    protected $plugin_name = 'affili';

    private $table_name;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb       = $wpdb;
        $this->table_name = $wpdb->prefix . 'affili';
    }

    public function init()
    {
        load_plugin_textdomain('affili', false, 'affili/languages');
    }

    public function menu()
    {
        $page_title = __('affili plugin', 'affili');
        $menu_title = __('affili', 'affili');
        $capability = 'manage_options';
        $menu_slug  = 'affili';
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
        $access_token = $this->getAccessToken();

        include_once __DIR__.'/../views/form.php';
    }

    public function loadAdminStyles()
    {
        wp_enqueue_style( 'admin_css_foo', plugins_url('assets/css/admin-style-main.css',__DIR__), false, '1.0.0' );
    }

    public function setAccessToken()
    {
        $nonce     = wp_verify_nonce($_POST['affili_set_access_token'], 'eadkf#adk$fawlkaawwlRRe');
        $condition = isset($_POST['affili_set_access_token']) && $nonce;

        if($condition) {
            $access_token = sanitize_text_field($_POST['access_token']);
            $data = [
                'name'  => 'access_token',
                'value' => $access_token,
            ];

            $access_token_model = $this->getAccessToken();

            if(empty($access_token_model)) {
                $this->wpdb->insert($this->table_name, $data, '%s');
            }else {
                $this->wpdb->update($this->table_name, $data, [
                    'id' => $access_token_model->id
                ]);
            }

            $admin_notice = "success";
            $message      = __('access token saved successful.', 'affili');

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
        $notices = get_option('affili_flash_notices', []);

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
            delete_option('affili_flash_notices', []);
        }
    }

    public function setAffiliJs()
    {
        $model = $this->getAccessToken();

        echo '<script type="text/javascript" src="https://analytics.affili.ir/scripts/affili-js.js" async></script>';
        echo '<script type="text/javascript">';
        echo 'affili("create", "'.$model->value.'");';
        echo 'affili("detect");';
        echo '</script>';
    }

    public function setMetaData()
    {
        $model = $this->getAccessToken();

        echo '<meta name="affiliTokenId" content="'.$model->value.'" />';
    }

    public function trackOrders($order_id)
    {
        $order = wc_get_order($order_id);

        $items          = $order->get_items();
        $order_key      = $order->get_order_key();
        $order_currency = $order->get_currency();

        $amount = 0;
        foreach ($items as $item) {
            // $product = $item->get_product();
            $qty = $item['qty'];

            $subtotal = $order->get_line_subtotal($item, true, true);
            $amount   = $subtotal * $qty + $amount;
        }

        if($order_currency === 'IRT') {
            $amount = $amount * 10;
        }

        $affili = "affili('conversion', '{$order_key}', '{$amount}', 'buy');";
        echo '<script type="text/javascript">'.$affili.'</script>';
    }

    public function setup()
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'loadAdminStyles']);
        add_action('admin_post_set_access_token', [$this, 'setAccessToken']);

        add_action('admin_notices', [$this, 'displayFlashNotices'], 12);
        add_action( 'wp_head', [$this, 'setAffiliJs'] );
        // add_action( 'wp_head', [$this, 'setMetaData'] );

        add_action( 'woocommerce_thankyou', [$this, 'trackOrders']);
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

    protected function getAccessToken()
    {
        $result = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE name = 'access_token' limit 1"
        );
        $result = is_array($result) ? array_pop($result) : [];

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
        $notices = get_option('affili_flash_notices', []);

        $dismissible_text = $dismissible ? 'is-dismissible' : '';

        array_push($notices, [
            'notice'        => $notice,
            'type'          => $type,
            'dismissible'   => $dismissible_text
        ]);

        // We update the option with our notices array
        update_option('affili_flash_notices', $notices );
    }
}