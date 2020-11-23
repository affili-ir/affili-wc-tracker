<?php

namespace AffiliIR;

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

    public function getCategories($category_id = null)
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
            'hide_empty'   => $empty
        ];

        if($category_id !== null) {
            $args += [
                'parent'       => $category_id,
                'child_of'     => 0,
            ];
        }

        return get_categories( $args );
    }

    public function insertCommissionKeys($cats)
    {
        foreach($cats as $cat_id => $key) {
            $commission_key = $this->findCommissionKey($cat_id);

            $data = [
                'name'  => "category-commission-{$cat_id}",
                'value' => $key,
            ];

            if(empty($commission_key)) {
                $this->wpdb->insert($this->table_name, $data, '%s');
            }else {
                $this->wpdb->update($this->table_name, $data, [
                    'id' => $commission_key->id
                ]);
            }
        }
    }

    public function findCommissionKey($category_id)
    {
        $result = $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE name = 'category-commission-{$category_id}' limit 1"
        );
        $result = is_array($result) ? array_pop($result) : [];

        return $result;
    }

    public function getOrderData($order)
    {
        $options        = [];
        $commissions    = [];

        foreach ($order->get_items() as $item) {
            $subtotal      = $this->getOrderItemSubtotal($order, $item);
            $commissions[] = $this->getOrderItemCommissions($item, $subtotal);

            $key       = "product-{$item->get_product_id()}";
            $line_item = "{$item['name']} - qty: {$item['qty']}";

            $options['meta_data'][$key] = $line_item;
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
        $categories = wp_get_post_terms($item->get_product_id(), 'product_cat');
        foreach ( $categories as $category ) {
            $category_commission_type = $this->findCommissionKey($category->term_id);
        }

        return $category_commission_type ? [
            'sub_amount'    => $subtotal,
            'name'          => $category_commission_type->value ?: 'default',
        ] : [];
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