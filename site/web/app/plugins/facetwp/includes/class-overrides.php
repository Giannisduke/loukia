<?php

class FacetWP_Overrides
{

    public $raw;


    function __construct() {
        add_filter( 'facetwp_index_row', [ $this, 'index_row' ], 5, 2 );
        add_filter( 'facetwp_index_row', [ $this, 'format_numbers' ], 15, 2 );
        add_filter( 'facetwp_is_main_query', [ $this, 'ignore_post_types' ], 10, 2 );
    }


    /**
     * Indexer modifications
     */
    function index_row( $params, $class ) {
        if ( $class->is_overridden ) {
            return $params;
        }

        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        // Support "Other data source" values
        if ( ! empty( $facet['source_other'] ) ) {
            $other_params = $params;
            $other_params['facet_source'] = $facet['source_other'];
            $rows = $class->get_row_data( $other_params );
            $params['facet_display_value'] = $rows[0]['facet_display_value'];
        }

        // Store raw numbers to format later, if needed
        if ( in_array( $facet['type'], [ 'number_range', 'slider' ] ) ) {
            $this->raw = [
                'value' => $params['facet_value'],
                'label' => $params['facet_display_value']
            ];
        }

        return $params;
    }


    /**
     * Make sure that numbers are properly formatted
     */
    function format_numbers( $params, $class ) {

        $value = $params['facet_value'];
        $label = $params['facet_display_value'];

        if ( empty( $this->raw ) ) {
            return $params;
        }

        // Only format if un-altered
        if ( $this->raw['value'] === $value && $this->raw['label'] === $label ) {
            $params['facet_value'] = FWP()->helper->format_number( $this->raw['value'] );
            $params['facet_display_value'] = FWP()->helper->format_number( $this->raw['label'] );
        }

        $this->raw = null;

        return $params;
    }


    /**
     * Ignore certain post types
     */
    function ignore_post_types( $is_main_query, $query ) {
        $blacklist = [ 'carts', 'advanced_ads', 'ms_relationship', 'wc_user_membership', 'edd_wish_list' ];
        $post_type = $query->get( 'post_type' );

        if ( is_string( $post_type ) && in_array( $post_type, $blacklist ) ) {
            $is_main_query = false;
        }

        return $is_main_query;
    }
}
