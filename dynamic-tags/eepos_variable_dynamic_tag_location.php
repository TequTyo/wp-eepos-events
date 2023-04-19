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

    protected function register_controls() {
        $post = get_post( get_the_ID() );
        $post->meta = get_post_meta( $post->ID );
        $get_loc = $post->meta['location'][0];
        if (!empty($get_loc)) {
            $get_loc  = ucwords( str_replace( '_', ' ', $get_loc ) );
        }

        $this->add_control(
            'user_selected_variables_location',
            [
                'type' => \Elementor\Controls_Manager::TEXT,
                'label' => esc_html__( 'Location', 'textdomain' ),
                'default' => $get_loc,
            ]
        );
    }

    public function render() {
        $user_selected_variable = $this->get_settings_for_display();

        echo '<p>' . $user_selected_variable['user_selected_variables_location']. '</p>';
    }
}
