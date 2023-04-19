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

    protected function register_controls() {
        $post = get_post( get_the_ID() );
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
            $startDateFormatted = ucwords( str_replace( '_', ' ', $startDateFormatted ) );
        }

        $this->add_control(
            'user_selected_variables_date',
            [
                'type' => \Elementor\Controls_Manager::TEXT,
                'label' => esc_html__( 'Date', 'textdomain' ),
                'default' => $startDateFormatted,
            ]
        );
    }

    public function render() {
        $user_variable_date = $this->get_settings_for_display();

        echo '<p>' . $user_variable_date['user_selected_variables_date']. '</p>';
    }

}
