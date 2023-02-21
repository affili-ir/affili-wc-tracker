<?php
/**
 * @link              https://affili.ir
 * @since             1.0.0
 * @package           AffiliWCTracker
 *
 * Plugin Name:       شبکه همکاری در فروش افیلی
 * Plugin URI:        https://github.com/affili-ir/wordpress
 * Description:       ردیابی خریدها و لیدهای انجام شده توسط بازاریابان افیلی
 * Version:           3.0.0
 * Author:            Affili IR
 * Author URI:        https://affili.ir
 * License:           GPLv2 or later
 * License URI:       https://github.com/affili-ir/wordpress/blob/master/LICENSE
 * Text Domain:       affili_wc_tracker
 * Domain Path:       /languages
 */

if( !defined('ABSPATH') ) {
    exit;
}

class AFFL_WC_Products_Extractor extends WP_REST_Controller
{
    public function registerRoutes()
    {
        register_rest_route('affl/v1', '/products', [
            [
                'methods' => 'POST',
                'callback' => [
                    $this,
                    'getProducts'
                ],
                'permission_callback' => '__return_true',
                'args' => []
            ]
        ]);
    }

    /**
     * @param WP_REST_Request $request
     */
    private function checkRequest($request)
    {
        // Get shop domain
        $site_url = wp_parse_url(get_site_url());
        $store_domain = str_replace('www.', '', $site_url['host']);

        // torob verify token url
        $endpoint_url = 'https://core.affili.ir/services/hook/validate-token/';

        // Get Parameters
        $token = sanitize_text_field($request->get_param('token'));

        // Get Headers
        $header = $request->get_header('X-Authorization');
        if(empty($header)){
            $header = $request->get_header('Authorization');
        }

        // Verify token
        $response = wp_safe_remote_post($endpoint_url, [
            'method' => 'POST',
            'timeout' => 5,
            'redirection' => 0,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'AUTHORIZATION' => $header,
            ],
            'body' => [
                'token' => $token,
                'store_domain' => $store_domain,
                'version' => '3.0.0'
            ],
            'cookies' => []
        ]);

        return $response;
    }

    /**
     * Find mathing product and variation
     *
     * @param WC_Product $product
     * @param array $attributes
     */
    private function findMatchingVariation($product, $attributes)
    {
        foreach ($attributes as $key => $value) {
            if(strpos($key, 'attribute_') === 0) {
                continue;
            }

            unset($attributes[ $key ]);
            $attributes[sprintf('attribute_%s', $key)] = $value;
        }

        if (class_exists('WC_Data_Store')) {
            $data_store = WC_Data_Store::load('product');

            return $data_store->find_matching_product_variation($product, $attributes);
        }

        return $product->get_matching_variation($attributes);
    }


    /**
     * Get single product values
     * @param WC_Product $product
     * @param bool $is_child
     */
    function getProductValues($product, $is_child = false)
    {
        $images = [];
        $attachment_ids = $product->get_gallery_image_ids();
        foreach ( $attachment_ids as $attachment_id ) {
            $img = wp_get_attachment_image_src($attachment_id, 'full');
            if($img) {
                array_push($images, $img[0]);
            }
        }

        $primary_image = null;
        $image = wp_get_attachment_image_src($product->get_image_id(), 'full');
        if($image) {
            $primary_image = $image[0];
            if (!in_array($image[0], $images)){
                array_push($images, $image[0]);
            }
        }

        $tmp_product = [
            'name' => '',
            'english_name' => '',
            'categories' => [],
            'parent_id' => 0,
            'pid' => $product->get_id(),
            'price' => $product->get_price(),
            'old_price' => $product->get_regular_price() ?: null,
            'in_stock' => $product->is_in_stock(),
            'images' => $images,
            'created' => $product->get_date_created(),
            'updated' => $product->get_date_modified(),
            'primary_image' => $primary_image,
            'url' => get_permalink($product->get_id()),
            'short_desc' => $product->get_short_description(),
            'attrs' => [],
        ];

        if($is_child) {
            $parent = wc_get_product($product->get_parent_id());
            $tmp_product['name'] = $parent->get_name();
            $tmp_product['english_name'] = get_post_meta($product->get_parent_id(), 'product_english_name', true);
            $categories = get_the_terms($parent->get_id(), 'product_cat');
            $tmp_product['parent_id'] = $parent->get_id();
        } else {
            $tmp_product['name'] = $product->get_name();
            $tmp_product['english_name'] = get_post_meta($product->get_id(), 'product_english_name', true);
            $categories = get_the_terms($product->get_id(), 'product_cat');
        }

        $cat_count = count($categories);
        foreach (array_values($categories) as $index => $cat) {
            $tmp_product['categories'][] = [
                'cid' => $cat->term_id,
                'name' => $cat->name,
                'url' => get_term_link($cat->term_id, 'product_cat'),
                'parent_cid' => $cat->parent,
                'is_primary' => $index + 1 == $cat_count,
            ];
        }

        $attrs = [];
        if ($is_child) {
            foreach ( $product->get_attributes() as $key => $value ) {
                if ( !empty($value) ) {
                    if (substr($key, 0, 3) === 'pa_' ) {
                        $value = get_term_by('slug', $value, $key);
                        $value = $value ? $value->name : '';

                        $key = wc_attribute_label($key);
                    }

                    $attrs[urldecode($key)] = rawurldecode($value);
                }
            }
        } else {
            if ($product->is_type('variable')) {
                // Set prices to 0 then calcualte them
                $tmp_product['price'] = 0;
                $tmp_product['old_price'] = 0;

                // Find price for default attributes. If can't find return max price of variations
                $variation_id = $this->findMatchingVariation($product, $product->get_default_attributes());
                if ($variation_id != 0) {
                    $variation = wc_get_product($variation_id);
                    $tmp_product['price'] = $variation->get_price();
                    $tmp_product['old_price'] = $variation->get_regular_price();
                    $tmp_product['in_stock'] = $variation->get_stock_status();
                } else {
                    $tmp_product['price'] = $product->get_variation_price('max');
                    $tmp_product['old_price'] = $product->get_variation_regular_price('max');
                }

                // Extract default attributes
                foreach ($product->get_default_attributes() as $key => $value) {
                    if (!empty($value)) {
                        if (substr($key, 0, 3) === 'pa_') {
                            $value = get_term_by('slug', $value, $key);
                            $value = $value ? $value->name : '';

                            $key = wc_attribute_label($key);
                        }

                        $attrs[urldecode($key)] = rawurldecode($value);
                    }
                }
            }

            // add remain attributes
            foreach($product->get_attributes() as $attribute) {
                if ($attribute['visible'] == 1) {
                    $name = wc_attribute_label($attribute['name']);

                    if (!array_key_exists($name, $attrs)) {
                        $values = substr($attribute['name'], 0, 3) === 'pa_'
                            ? wc_get_product_terms($product->get_id(), $attribute['name'], ['fields' => 'names'])
                            : $attribute['options']
                        ;
                        $attrs[$name] = implode(', ', $values);
                    }
                }
            }
        }

        if (!array_key_exists('شناسه کالا', $attrs) && ($sku = $product->get_sku()) != '') {
            $attrs['شناسه کالا'] = $sku;
        }

        $tmp_product['attrs'] = $attrs;

        return $tmp_product;
    }

    /**
     * Get all products
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    private function getAllProducts($show_variations, $limit, $page)
    {
        $parent_ids = [];
        $post_type = ['product'];
        if ($show_variations) {
            // Get all posts have children
            $query = new WP_Query(array(
                'post_type' => ['product_variation'],
                'post_status' => 'publish'
            ));
            $parent_ids = array_column($query->get_posts(), 'post_parent');
            $post_type = ['product', 'product_variation'];
        }

        // Make query
        $query = new WP_Query(array(
            'posts_per_page' => $limit,
            'paged'          => $page,
            'post_status'    => 'publish',
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'post_type'      => $post_type,
            'post__not_in'   => $parent_ids
        ));
        $products = $query->get_posts();

        $data['meta'] = [
            // Count products
            'total' => $query->found_posts,
            // Total pages
            'last' => $query->max_num_pages,
            'current' => $page,
            'per' => $limit,
        ];


        $data['products'] = [];

        // Retrive and send data in json
        foreach ($products as $product) {
            $product = wc_get_product($product->ID);
            $parent_id = $product->get_parent_id();

            if (
                ($parent_id == 0 && !$show_variations) ||
                // Exclude the variable product. (variations of it will be inserted.)
                ($parent_id == 0 && $show_variations && !$product->is_type('variable')) ||
                // Process for visible child
                ($parent_id !== 0 and $product->get_price())
            ) {
                $temp_product = $this->getProductValues($product, $parent_id != 0);
                $data['products'][] = $this->prepare_response_for_collection($temp_product);
            }
        }

        return $data;
    }

    /**
     * Get a product or list of products
     *
     * @param array $product_list
     *
     * @return array
     */
    private function getListProducts($product_list)
    {
        $data['products'] = [];

        // Retrive and send data in json
        foreach ($product_list as $pid) {
            $product = wc_get_product($pid);
            if (!$product || $product->get_status() !== 'publish') {
                continue;
            }

            $parent_id = $product->get_parent_id();

            if (
                // Process for parent product
                ($parent_id == 0) ||
                // Process for visible child
                ($parent_id != 0 && $product->get_price())
            ) {
                $temp_product = $this->getProductValues($product, $parent_id != 0);
                $data['products'][] = $this->prepare_response_for_collection($temp_product);
            }
        }

        return $data;
    }

    /**
     * Get a slugs or list of slugs. For getting product's data by its link
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    private function getListSlugs($slug_list)
    {
        $data['products'] = [];

        // Retrive and send data in json
        foreach ($slug_list as $sid) {
            $product = get_page_by_path($sid, OBJECT, 'product');
            if ($product && $product->post_status === 'publish') {
                $tmp_product = $this->getProductValues(wc_get_product($product->ID));
                $data['products'][] = $this->prepare_response_for_collection($tmp_product);
            }
        }

        return $data;
    }

    /**
     * @param WP_REST_Request $request
     */
    public function getProducts($request)
    {
        // Get Parameters
        $show_variations = rest_sanitize_boolean($request->get_param('variation'));
        $limit = intval($request->get_param('limit')) ?: 20;
        $page = intval($request->get_param('page')) ?: 1;

        if (!empty($request->get_param('products'))) {
            $product_ids = explode(',', sanitize_text_field($request->get_param('products')));
            $product_ids = is_array($product_ids) ? array_map('intval', $product_ids) : [];
        }
        if (!empty($request->get_param('slugs'))) {
            $slug_list = explode(',', (sanitize_text_field(urldecode($request->get_param('slugs')))));
        }

        // Check request is valid and update
        $response = $this->checkRequest($request);
        if (!is_array($response)) {
            $data['response'] = '';
            $data['errors'] = $response;
            $response_code = 500;
        } else {
            $response_body = $response['body'];
            $response = json_decode($response_body, true);

            if ($response['status'] === 'ok' && $response['data']['msg'] === 'TOKEN_IS_VALID') {
                if (!empty($product_ids)) {
                    $data = $this->getListProducts($product_ids);
                } elseif (!empty($slug_list)) {
                    $data = $this->getListSlugs($slug_list);
                } else {
                    $data = $this->getAllProducts($show_variations, $limit, $page);
                }

                $response_code = 200;
            } else {
                $data['response'] = $response;
                $data['errors'] = $response['errors'];
                $response_code = 401;
            }
        }

        $data['version'] = '3.0.0';

        return new WP_REST_Response($data, $response_code);
    }
}


class AFFL_Script
{
    public function loadScript()
    {
        $script = '';
        $script .= 'window.affiliData = window.affiliData || [];function affili(){affiliData.push(arguments);}'.PHP_EOL;
        $script .= 'affili(\'create\');'.PHP_EOL;

        wp_enqueue_script('affili-wc-tracker-script', 'https://analytics.affili.ir/scripts/affili-v2.js');
        wp_add_inline_script('affili-wc-tracker-script', $script);

        function asyncAffiliScript($tag, $handle) {
            if ($handle === 'affili-wc-tracker-script') {
                $tag = str_replace( '></', ' async></', $tag );
            }

            return $tag;
        }

        add_filter('script_loader_tag', 'asyncAffiliScript', 10, 2);
    }

    public function trackOrders($order_id)
    {
        $order_id = apply_filters('woocommerce_thankyou_order_id', absint($order_id));

        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $products = [];
        foreach ($order->get_items() as $item) {
            $item_subtotal = floatval(
                number_format((float) $item->get_subtotal(), wc_get_price_decimals(), '.', '')
            );

            $products[] = [
                'pid' => $item->get_product_id(),
                'name' => $item['name'],
                'unit_price' => $item_subtotal / $item['qty'],
                'quantity' => $item['qty'],
                'total_price' => $item_subtotal,
            ];
        }

        $coupon = array_pop(array_reverse($order->get_items('coupon'))) ?: '';
        if ($coupon) {
            $coupon = $coupon->get_code();
        }

        $amount = $order->get_subtotal() - $order->get_total_discount();
        $options = [
            'coupon' => $coupon,
            'products' => $products,
            'currency' => $order->get_currency(),
        ];
        $options = count($options) ? json_encode($options) : json_encode($options, JSON_FORCE_OBJECT);

        $script = "affili('conversion', '{$order_id}', '{$amount}', {$options})";

        wp_add_inline_script("affili-wc-tracker-script", $script);
    }
}

if (in_array( 'woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    if (!is_admin()) {
        $affl = new AFFL_Script;
        $affl->loadScript();

        add_action('woocommerce_thankyou', [$affl, 'trackOrders']);
    }

    $wc_product_extractor = new AFFL_WC_Products_Extractor;

    // register rest api route
    add_action('rest_api_init', [$wc_product_extractor, 'registerRoutes']);
}
