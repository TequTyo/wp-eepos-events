<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Dynamic_Tag_Eepos_Variable_Tag_2 extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'Eepos event date';
    }

    public function get_title() {
        return esc_html__( 'eepos_event date', 'textdomain' );
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
        // hae nykyisen postauksen perusteella aika ja paikka tiedot
        $variables_date = [];

        $listEventIds = $this->get_eepos_posts();
        $posts = count( $listEventIds )
               ? get_posts( [
                   'include'   => $listEventIds,
                   'post_type' => 'eepos_event'
               ] )
               : [];
        foreach ( $posts as $post ) {
            $post->meta = get_post_meta( $post->ID );

            $startDate = $post->meta['event_start_date'][0];
            $startTime = $post->meta['event_start_time'][0];
            $currentYear = date( 'Y' );

            if ( $startDate ) {
                $startCombined = $startDate . ' ' . $startTime;
                $startDateDT = new DateTime( $startCombined );
                if ( $startDateDT->format( 'Y' ) === $currentYear ) {
                    $startDateFormatted = $startDateDT->format( 'd.n. \k\l\o G.i' );
                } else {
                    $startDateFormatted = $startDateDT->format( 'd.n.Y \k\l\o G.i' );
                }
            }
            if (!empty($startDateFormatted)) {
                $variables_date[ $startDateFormatted ] = ucwords( str_replace( '_', ' ', $startDateFormatted ) );
            }
        }
        $this->add_control(
            'user_selected_variables_date',
            [
                'type' => \Elementor\Controls_Manager::SELECT,
                'label' => esc_html__( 'Date', 'textdomain' ),
                'options' => $variables_date,
            ]
        );
    }

    public function render() {
        $user_selected_variable_date = $this->get_settings( 'user_selected_variables_date' );

        if ( ! $user_selected_variable_date && ! isset( $_SERVER[ $user_selected_variable_date ] )) {
            return;
        }

        echo wp_kses_post( $user_selected_variable_date );
    }
}
