<?php

class EeposEventsUpcomingWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'eepos_events_upcoming_widget',
			'Eepos-tapahtumat, tulevat',
			[
				'description' => 'Tiivistetty lista tulevista tapahtumista. Sopii esim. etusivulle.'
			]
		);

		wp_register_style( 'eepos_events_upcoming_widget_styles', plugin_dir_url( __FILE__ ) . '/widget-upcoming-basic.css' );
	}

	public function form( $instance ) {
		global $wpdb;

		$defaults = [
			'title'                => '',
			'event_count'          => 5,
			'more_events_link'     => '',
			'use_default_styles'   => true,
			'restrict_to_category' => 0
		];

		$args = wp_parse_args( $instance, $defaults );

		$catQuery = "
			SELECT {$wpdb->terms}.term_id, {$wpdb->terms}.name FROM {$wpdb->terms}
			INNER JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id
			WHERE {$wpdb->term_taxonomy}.taxonomy = 'eepos_event_category'
			GROUP BY {$wpdb->terms}.term_id
		";
		$catRows  = $wpdb->get_results( $catQuery );
		array_unshift( $catRows, (object) [ 'term_id' => 0, 'name' => 'Kaikki' ] );

		?>
		<p>
			<label>
				<?php _e( 'Otsikko', 'eepos_events' ) ?>
				<input type="text" class="widefat" name="<?= $this->get_field_name( 'title' ) ?>"
				       value="<?= esc_attr( $args['title'] ) ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e( 'Näytettävien tapahtumien määrä', 'eepos_events' ) ?>
				<input type="number" class="widefat" name="<?= $this->get_field_name( 'event_count' ) ?>"
				       value="<?= esc_attr( $args['event_count'] ) ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e( 'Näytettävä kategoria', 'eepos_events' ) ?><br>
				<select name="<?= $this->get_field_name( 'restrict_to_category' ) ?>">
					<?php foreach ( $catRows as $category ) { ?>
						<option
							value="<?= esc_attr( $category->term_id ) ?>"<?= $args['restrict_to_category'] == $category->term_id ? ' selected' : '' ?>>
							<?= esc_html( $category->name ) ?>
						</option>
					<?php } ?>
				</select>
			</label>
		</p>
		<p>
			<label>
				<?php _e( '"Kaikki tapahtumat" -linkki', 'eepos_events' ) ?>
				<input type="text" class="widefat" name="<?= $this->get_field_name( 'more_events_link' ) ?>"
				       value="<?= esc_attr( $args['more_events_link'] ) ?>">
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox"
				       name="<?= $this->get_field_name( 'use_default_styles' ) ?>"<?= $args['use_default_styles'] ? ' checked' : '' ?>>
				<?php _e( 'Käytä perustyylejä', 'eepos_events' ) ?>
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                         = $old_instance;
		$instance['title']                = wp_strip_all_tags( $new_instance['title'] ?? '' );
		$instance['event_count']          = intval( wp_strip_all_tags( $new_instance['event_count'] ?? '5' ) );
		$instance['more_events_link']     = wp_strip_all_tags( $new_instance['more_events_link'] ?? '' );
		$instance['use_default_styles']   = ( $new_instance['use_default_styles'] ?? null ) === 'on';
		$instance['restrict_to_category'] = intval( $new_instance['restrict_to_category'] ?? '0' );

		return $instance;
	}

	protected function getUpcomingEventPostIds( $count, $termId = null ) {
		global $wpdb;

		$values = $termId ? [ $termId, $count ] : [ $count ];

		$query = $wpdb->prepare( "
			SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} AS startDateMeta ON startDateMeta.post_id = {$wpdb->posts}.id AND startDateMeta.meta_key = 'event_start_date'
			INNER JOIN {$wpdb->postmeta} AS startTimeMeta ON startTimeMeta.post_id = {$wpdb->posts}.id AND startTimeMeta.meta_key = 'event_start_time'
			LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.object_id = {$wpdb->posts}.id
			LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
			WHERE {$wpdb->posts}.post_type = 'eepos_event'
			AND {$wpdb->posts}.post_status = 'publish'
			AND startDateMeta.meta_value >= CURDATE()
			" . ( $termId ? "AND {$wpdb->terms}.term_id = %d" : "" ) . "
			GROUP BY {$wpdb->posts}.ID
			ORDER BY startDateMeta.meta_value ASC, startTimeMeta.meta_value ASC
			LIMIT %d
		", $values );
		$posts = $wpdb->get_results( $query );

		return array_map( function ( $p ) {
			return $p->ID;
		}, $posts );
	}

	public function widget( $args, $instance ) {
		$title              = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$count              = intval( $instance['event_count'] ?? 5 );
		$restrictToCategory = $instance['restrict_to_category'] ?? 0;

		$upcomingEventPostIds = $this->getUpcomingEventPostIds( $count, $restrictToCategory );
		$posts                = count( $upcomingEventPostIds )
			? get_posts( [
				'include'   => $upcomingEventPostIds,
				'post_type' => 'eepos_event'
			] )
			: [];

		$moreEventsLink = $instance['more_events_link'] ?? '';

		$useDefaultStyles = $instance['use_default_styles'] ?? true;
		if ( $useDefaultStyles ) {
			wp_enqueue_style( 'eepos_events_upcoming_widget_styles' );
		}

		?>
		<div class="eepos-events-upcoming-widget<?= $useDefaultStyles ? ' with-default-styles' : '' ?>">
			<?php if ($title !== '') { ?>
				<h2 class="widget-title"><?= $title ?></h2>
			<?php } ?>
			<?php if ( count( $posts ) ) { ?>
				<ul class="event-list">
					<?php
					foreach ( $posts as $post ) {
						$meta      = get_post_meta( $post->ID );

						$startDate = new DateTime($meta['event_start_date'][0]);
						$formattedStartDate = date_i18n( 'D j.n.', $startDate->format('U') );

						$startTime = DateTime::createFromFormat('H:i:s', $meta['event_start_time'][0]);
						$formattedStartTime = date_i18n( 'G.i', $startTime->format('U') );

						?>
						<li class="event">
							<div class="event-date">
								<?= $formattedStartDate ?>
								<?php if ($formattedStartTime !== '0.00') { ?>
									klo <?= $formattedStartTime ?>
								<?php } ?>
							</div>
							<div class="event-title"><?= esc_html( $post->post_title ) ?></div>
						</li>
					<?php } ?>
				</ul>
			<?php } else { ?>
				<p class="no-events">Ei tulevia tapahtumia</p>
			<?php } ?>
			<?php if ( $moreEventsLink !== '' ) { ?>
				<div class="more-events">
					<a href="<?= esc_attr( $moreEventsLink ) ?>">Kaikki tapahtumat</a>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}

function eepos_events_register_upcoming_widget() {
	register_widget( 'EeposEventsUpcomingWidget' );
}

add_action( 'widgets_init', 'eepos_events_register_upcoming_widget' );
