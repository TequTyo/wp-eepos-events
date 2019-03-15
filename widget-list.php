<?php

class EeposEventsListWidget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'eepos_events_list_widget',
			'Eepos-tapahtumat, listaus',
			[
				'description' => 'Laaja lista tapahtumista'
			]
		);

		wp_register_style( 'eepos_events_list_widget_styles', plugin_dir_url( __FILE__ ) . '/widget-list-basic.css' );
		wp_register_script( 'eepos_events_list_widget_script', plugin_dir_url( __FILE__ ) . '/widget-list.js' );
	}

	public function form( $instance ) {
		global $wpdb;

		$defaults = [
			'title'                => '',
			'event_count'          => 5,
			'more_events_link'     => '',
			'use_default_styles'   => true,
			'restrict_to_category' => 0,
			'include_description'  => 1
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
				<input type="checkbox"
				       name="<?= $this->get_field_name( 'use_default_styles' ) ?>"<?= $args['use_default_styles'] ? ' checked' : '' ?>>
				<?php _e( 'Käytä perustyylejä', 'eepos_events' ) ?>
			</label>
		</p>
		<p>
			<label>
				<input type="checkbox"
				       name="<?= $this->get_field_name( 'include_description' ) ?>"<?= $args['include_description'] ? ' checked' : '' ?>>
				<?php _e( 'Näytä kuvaus', 'eepos_events' ) ?>
			</label>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                       = $old_instance;
		$instance['title']              = wp_strip_all_tags( $new_instance['title'] ?? '' );
		$instance['more_events_link']   = wp_strip_all_tags( $new_instance['more_events_link'] ?? '' );
		$instance['use_default_styles'] = ( $new_instance['use_default_styles'] ?? null ) === 'on';
		$instance['include_description'] = ( $new_instance['include_description'] ?? null ) === 'on';

		return $instance;
	}

	protected function getListEventPostIds( $termId = null ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} AS startDateMeta ON startDateMeta.post_id = {$wpdb->posts}.id AND startDateMeta.meta_key = 'event_start_date'
			INNER JOIN {$wpdb->postmeta} AS startTimeMeta ON startTimeMeta.post_id = {$wpdb->posts}.id AND startTimeMeta.meta_key = 'event_start_time'
			LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.object_id = {$wpdb->posts}.id
			LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
			WHERE {$wpdb->posts}.post_type = 'eepos_event'
			AND {$wpdb->posts}.post_status = 'publish'
			AND startDateMeta.meta_value >= DATE_SUB(CURDATE(), INTERVAL 2 WEEK)
			" . ( $termId ? "AND {$wpdb->terms}.term_id = %d" : "" ) . "
			GROUP BY {$wpdb->posts}.ID
			ORDER BY startDateMeta.meta_value ASC, startTimeMeta.meta_value ASC
		", $termId ? [$termId] : [] );
		$posts = $wpdb->get_results( $query );

		return array_map( function ( $p ) {
			return $p->ID;
		}, $posts );
	}

	public function widget( $args, $instance ) {
		$title              = apply_filters( 'widget_title', $instance['title'] ?? '' );
		$restrictToCategory = $instance['restrict_to_category'] ?? 0;

		$listEventPostIds = $this->getListEventPostIds( $restrictToCategory );
		$posts            = count( $listEventPostIds )
			? get_posts( [
				'include'   => $listEventPostIds,
				'post_type' => 'eepos_event'
			] )
			: [];

		foreach ( $posts as $post ) {
			$post->meta = get_post_meta( $post->ID );
		}

		usort($posts, function($a, $b) {
			$aKey = "{$a->meta['event_start_date'][0]} {$a->meta['event_start_time'][0]}";
			$bKey = "{$b->meta['event_start_date'][0]} {$b->meta['event_start_time'][0]}";
			if ($aKey > $bKey) return 1;
			if ($aKey < $bKey) return -1;
			return 0;
		});

		$postsGroupedByMonth = array_reduce( $posts, function ( $map, $post ) {
			$eventDate     = new DateTime( $post->meta['event_start_date'][0] );
			$key           = $eventDate->format( 'Y-m' );
			$map[ $key ]   = $map[ $key ] ?? [];
			$map[ $key ][] = $post;

			return $map;
		}, [] );

		$moreEventsLink = $instance['more_events_link'] ?? '';

		$useDefaultStyles = $instance['use_default_styles'] ?? true;
		if ( $useDefaultStyles ) {
			wp_enqueue_style( 'eepos_events_list_widget_styles' );
		}

		wp_enqueue_script( 'eepos_events_list_widget_script' );

		?>
		<div class="eepos-events-list-widget<?= $useDefaultStyles ? ' with-default-styles' : '' ?>">
			<?php if ( $title !== '' ) { ?>
				<h2 class="widget-title"><?= $title ?></h2>
			<?php } ?>
			<?php if ( count( $postsGroupedByMonth ) ) { ?>
				<?php
				$currentYear = date( 'Y' );
				foreach ( $postsGroupedByMonth as $yearMonth => $posts ) {
					[ $year, $month ] = explode( '-', $yearMonth );
					$monthName = date_i18n( 'F', DateTime::createFromFormat( 'm', $month )->format( 'U' ) );

					?>
					<h3><?= $monthName ?><?= $year !== $currentYear ? ", {$year}" : '' ?></h3>
					<ul class="event-list">
						<?php
						foreach ( $posts as $post ) {
							$startDate = new DateTime($post->meta['event_start_date'][0]);
							$formattedStartDate = date_i18n( 'D j.n.', $startDate->format('U') );

							$startTime = DateTime::createFromFormat('H:i:s', $post->meta['event_start_time'][0]);
							$formattedStartTime = date_i18n( 'G.i', $startTime->format('U') );

							$content = apply_filters('the_content', $post->post_content);
							$content = str_replace(']]>', ']]&gt;', $content);

							$location = $post->meta['location'][0] ?? '';

							?>
							<li class="event">
								<a href="javascript:void(0)" class="event-header eepos-events-list-widget-event-header">
									<h4><?= esc_html( $post->post_title ) ?></h4>
									<span class="extra">
										<?= $formattedStartDate ?>
										<?php if ($formattedStartTime !== '0.00') { ?>
											klo <?= $formattedStartTime ?>
										<?php } ?>
										<?php if ($location !== '') { ?>
											<span class="event-location"><?= esc_html($location) ?></span>
										<?php } ?>
									</span>
								</a>
								<div class="event-info">
									<?php if ($instance['include_description']) { ?>
										<div class="description">
											<?= $content ?>
										</div>
									<?php } ?>
								</div>
							</li>
						<?php } ?>
					</ul>
				<?php } ?>
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

function eepos_events_register_list_widget() {
	register_widget( 'EeposEventsListWidget' );
}

add_action( 'widgets_init', 'eepos_events_register_list_widget' );
