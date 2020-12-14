<?php

namespace AffiliIR;


require_once 'Woocommerce.php';
require_once 'ActivePluginsCheck.php';

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use AffiliIR\Woocommerce as AffiliIR_Woocommerce;
use AffiliIR\ActivePluginsCheck as AffiliIR_ActivePluginsCheck;

class ListTable extends \WP_List_Table
{
    protected $plugin_name = 'affili_ir';

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = [
            'id'        => __('Category ID', $this->plugin_name),
            'category'  => __('Category', $this->plugin_name),
        ];

        if(AffiliIR_ActivePluginsCheck::wooBrandActiveCheck()) {
            $columns += [
                'brand' => __('Brand', $this->plugin_name),
            ];
        }

        $columns += [
            'value' => __('Commission key', $this->plugin_name),
        ];

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return [
            'id' => ['id', false]
        ];
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $woocommerce = new AffiliIR_Woocommerce;

        $commission_keys = $woocommerce->getCommissionKeys();
        foreach($commission_keys as $key => $commission) {
            $cat_id = str_replace('commission-cat-', '', $commission->name);
            if($this->strContains($cat_id, 'brand-')) {
                $cat_id = $this->strBefore($cat_id, 'brand-');
            }

            $brand_id = $this->strAfter($commission->name, 'brand-');

            $commission_keys[$key]->brand    = $brand_id ? get_the_category_by_ID((int)$brand_id) : null;
            $commission_keys[$key]->category = get_the_category_by_ID((int)$cat_id);
        }

        return json_decode( json_encode($commission_keys), 1 );
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'category':
            case 'brand':
            case 'value':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }

    private function strContains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function strBefore($subject, $search)
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    private function strAfter($subject, $search)
    {
        $result = $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];

        return $result === $subject ? null : $result;
    }
}
?>