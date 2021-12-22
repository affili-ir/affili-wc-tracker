<?php

namespace AffiliIR;


class Woocommerce
{
    protected $table_name;
    protected $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb       = $wpdb;
        $this->table_name = $wpdb->prefix . 'affili';
    }

    public function getOrderData($order)
    {
        $coupon = '';
        $products = [];
        foreach ($order->get_items() as $item) {
            $item_subtotal = floatval($this->wooRound($item->get_subtotal()));

            $products[] = [
                'product_page_id' => $item->get_product_id(),
                'sku' => $item['name'],
                'unit_price' => $item_subtotal / $item['qty'],
                'quantity' => $item['qty'],
                'total_price' => $item_subtotal,
            ];
        }

        if ($this->isWoo3()) {
            if ($coupons = $order->get_data()['coupon_lines']) {
                $coupon = array_values($coupons)[0]->get_code() ?? '';
            }
        } else {
            if ($coupons = $order->get_coupon_codes()) {
                $coupon = array_values($coupons)[0] ?? '';
            }
        }

        $order_id  = $this->isWoo3() ? $order->get_id() : $order->id;

        $amount = $order->get_subtotal() - $order->get_total_discount();
        $amount = $order->get_currency() === 'IRT' ? $amount * 10 : $amount;

        return [
            'order_id' => $order_id,
            'amount' => $amount,
            'products' => $products,
            'coupon' => $coupon
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