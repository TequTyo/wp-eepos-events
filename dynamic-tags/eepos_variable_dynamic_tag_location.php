<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Dynamic_Tag_Eepos_Variable_Tag_1 extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'Eepos event location';
    }

    public function get_title() {
        return esc_html__( 'eepos_event location', 'textdomain' );
    }

    public function get_group() {
        return [ 'eepos_group' ];
    }

    public function get_categories() {
        return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
    }

    protected function get_eepos_posts() {
        global $wpdb;

        $query = $wpdb->prepare("SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
            INNER JOIN {$wpdb->postmeta} AS startDateMeta ON startDateMeta.post_id = {$wpdb->posts}.id AND startDateMeta.meta_key = 'event_start_date'
            INNER JOIN {$wpdb->postmeta} AS startTimeMeta ON startTimeMeta.post_id = {$wpdb->posts}.id AND startTimeMeta.meta_key = 'event_start_time'
            LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.object_id = {$wpdb->posts}.id
            LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
            WHERE {$wpdb->posts}.post_type = 'eepos_event'
            AND {$wpdb->posts}.post_status = 'publish'
            AND startDateMeta.meta_value >= CURDATE() GROUP BY {$wpdb->posts}.ID
            ORDER BY startDateMeta.meta_value ASC, startTimeMeta.meta_value ASC");
        $posts = $wpdb->get_results( $query );
        return array_map( function ( $p ) {
            return $p->ID;
        }, $posts);
    }

    protected function register_controls() {
        $variables_location = [];

        $listEventIds = $this->get_eepos_posts();
        $posts = count( $listEventIds )
               ? get_posts( [
                   'include'   => $listEventIds,
                   'post_type' => 'eepos_event'
               ] )
               : [];
        foreach ( $posts as $post ) {
            $post->meta = get_post_meta( $post->ID );
            $get_loc = $post->meta['location'][0];
            if (!empty($get_loc)) {
                $variables_location[ $get_loc ] = ucwords( str_replace( '_', ' ', $get_loc ) );
            }
        }
        natcasesort($variables_location);
        $this->add_control(
            'user_selected_variables',
            [
                'type' => \Elementor\Controls_Manager::SELECT,
                'label' => esc_html__( 'Location', 'textdomain' ),
                'options' => $variables_location,
            ]
        );
    }

    public function render() {
        $user_selected_variable = $this->get_settings( 'user_selected_variables' );

        if ( ! $user_selected_variable && ! isset( $_SERVER[ $user_selected_variable ] )) {
            return;
        }

        echo wp_kses_post( $user_selected_variable );
    }
}
