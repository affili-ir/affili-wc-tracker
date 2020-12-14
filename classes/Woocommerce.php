<?php

namespace AffiliIR;


require_once 'ActivePluginsCheck.php';


use AffiliIR\ActivePluginsCheck as AffiliIR_ActivePluginsCheck;

class Woocommerce
{
    private $table_name;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb       = $wpdb;
        $this->table_name = $wpdb->prefix . 'affili';
    }

    public function getCategories($category_id = null, $options = [])
    {
        $taxonomy     = 'product_cat';
        $orderby      = 'id';
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;

        $args = [
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty,
        ];

        if($category_id !== null) {
            $args += [
                'parent'       => $category_id,
                'child_of'     => 0,
            ];
        }

        if(is_array($options) && $options) {
            $args = array_merge($args, $options);
        }

        return get_categories( $args );
    }

    public function getBrands($brand_id = null, $options = [])
    {
        $taxonomy     = 'product_brand';
        $orderby      = 'id';
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;

        $args = [
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty,
        ];

        if($brand_id !== null) {
            $args += [
                'parent'       => $brand_id,
                'child_of'     => 0,
            ];
        }

        if(is_array($options) && $options) {
            $args = array_merge($args, $options);
        }

        $brands = get_terms($args);

        if (is_wp_error($brands)) {
            $brands = [];
        }

        return $brands;
    }

    public function insertCommissionKeys($item)
    {
        $category_id = $item['category_id'] ?? null;
        $brand_id    = $item['brand_id'] ?? null;

        if($category_id === null) {
            return;
        }

        $commission_key = $this->findCommissionKey($category_id, $brand_id);

        $name = $brand_id ? "commission-cat-{$category_id}-brand-{$brand_id}"
            : "commission-cat-{$category_id}"
        ;
        $data = [
            'name'  => $name,
            'value' => $item['commission_key'],
        ];

        if(empty($commission_key)) {
            $this->wpdb->insert($this->table_name, $data, '%s');
        }else {
            $this->wpdb->update($this->table_name, $data, [
                'id' => $commission_key->id
            ]);
        }
    }

    public function findCommissionKey($category_id, $brand_id = null)
    {
        $name = $brand_id ? "commission-cat-{$category_id}-brand-{$brand_id}"
            : "commission-cat-{$category_id}"
        ;
        $result = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE name = '{$name}' limit 1"
        );
        $result = is_array($result) ? array_pop($result) : [];

        return $result;
    }

    public function getCommissionKeys()
    {
        $sql = "SELECT * FROM {$this->table_name} WHERE name LIKE 'commission-cat-%'";
        if(!AffiliIR_ActivePluginsCheck::wooBrandActiveCheck()) {
            $sql .= " AND name NOT LIKE 'commission-cat-%-brand-%'";
        }

        $result = $this->wpdb->get_results($sql);

        return $result;
    }

    public function getOrderData($order)
    {
        $options        = [];
        $commissions    = [];
        $holder         = [];

        foreach ($order->get_items() as $item) {
            $subtotal        = $this->getOrderItemSubtotal($order, $item);
            $item_commission = $this->getOrderItemCommissions($item, $subtotal);

            $commission_name = $item_commission['name'];

            $holder[$commission_name] =
                ($holder[$commission_name] ?? 0) + $item_commission['sub_amount']
            ;

            $key       = "product-{$item->get_product_id()}";
            $line_item = "{$item['name']} - qty: {$item['qty']}";

            $options['meta_data'][$key] = $line_item;
        }

        foreach($holder as $name => $sub_amount) {
            $commissions[] = [
                'name'       => $name,
                'sub_amount' => $sub_amount
            ];
        }

        if($coupons = $order->get_coupon_codes()) {
            $options['coupon'] = array_values($coupons)[0] ?? '';
        }

        $external_id  = $this->isWoo3() ? $order->get_id() : $order->id;

        $amount       = $order->get_subtotal() - $order->get_total_discount();
        $amount       = $order->get_currency() === 'IRT' ? $amount * 10 : $amount;

        $uniq_names   = array_unique(array_column($commissions, 'name'));
        $is_multi     = count($uniq_names) > 1;

        $default_name = count($uniq_names) === 1 ? $uniq_names[0] : 'default';

        $commissions = count($commissions) ? json_encode($commissions) : json_encode($commissions, JSON_FORCE_OBJECT);
        $options     = count($options) ? json_encode($options) : json_encode($options, JSON_FORCE_OBJECT);

        return [
            'commissions'   => $commissions,
            'options'       => $options,
            'external_id'   => $external_id,
            'amount'        => $amount,
            'is_multi'      => $is_multi,
            'default_name'  => $default_name,
            // 'order_key'     => $order->get_order_key(),
        ];

    }

    private function getOrderItemSubtotal($order, $item)
    {
        $item_subtotal = floatval($this->wooRound($item->get_subtotal()));
        if ($item_subtotal === 0.00) {
            return 0;
        }

        $proportional_discount = (
            $item_subtotal / $this->wooRound($order->get_subtotal())
        ) * $order->get_total_discount();

        $subtotal = $item_subtotal - $proportional_discount;
        $subtotal = $order->get_currency() === 'IRT' ? $subtotal * 10 : $subtotal;

        return $subtotal;
    }

    private function getOrderItemCommissions($item, $subtotal)
    {
        $category_commission_type = false;
        $categories = wp_get_post_terms($item->get_product_id(), 'product_cat');

        $brand_id = null;
        if(AffiliIR_ActivePluginsCheck::wooBrandActiveCheck()) {
            $brands = get_the_terms($item->get_product_id(), 'product_brand');
            if($brands) {
                $brand_id = end($brands)->term_id ?? null;
            }
        }

        foreach ( $categories as $category ) {
            $commission_key = $this->findCommissionKey($category->term_id, $brand_id);
            if($commission_key && $commission_key->value) {
                $category_commission_type = $commission_key->value;
            }
        }

        return [
            'sub_amount'    => $subtotal,
            'name'          => $category_commission_type ?: 'default',
        ];
    }

    /**
	 * Check if WooCommerce v3
	 *
	 * @return     bool
	 */
    public function isWoo3()
    {
        if($this->isWoocommerceActivated()) {
            global $woocommerce;

            return version_compare($woocommerce->version, "3.0", ">=");
        }

        return false;
    }

    public function wooRound($amount) {
        return number_format((float) $amount, wc_get_price_decimals(), '.', '');
    }

    /**
	 * Check if WooCommerce is activated
	 *
	 * @return boolean
	 */
	public function isWoocommerceActivated() {
        return class_exists('WooCommerce');
    }
}