<?php

class EeposEventsWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'eepos_events_widget',
			'Eepos events'
		);
	}

	public function form( $instance ) {
		$defaults = [
			'title'       => '',
			'event_count' => 5
		];

		$args = wp_parse_args( $instance, $defaults );

		?>
		<p>
			<label>
				<?php _e( 'Widget Title', 'eepos_events' ) ?>
				<input type="text" class="widefat" name="<?= $this->get_field_name( 'title' ) ?>"
				       value="<?= esc_attr( $args['title'] ) ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e( 'Events to display at once', 'eepos_events' ) ?>
				<input type="number" class="widefat" name="<?= $this->get_field_name( 'event_count' ) ?>"
				       value="<?= esc_attr( $args['event_count'] ) ?>">
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = wp_strip_all_tags( $new_instance['title'] ?? '' );
		$instance['event_count'] = intval( wp_strip_all_tags( $new_instance['event_count'] ?? '5' ) );

		return $instance;
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ?? '' );

		?>
		<?= $title ?><br>
		Aaaaa
		<?php
	}
}

function eepos_events_register_widget() {
	register_widget( 'EeposEventsWidget' );
}

add_action( 'widgets_init', 'eepos_events_register_widget' );
