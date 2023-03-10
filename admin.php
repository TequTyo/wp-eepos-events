<?php

global $post;

function eepos_events_define_event_list_columns() {
	return [
		'cb'             => '<input type="checkbox">',
		'title'          => 'Tapahtuman nimi',
		'event_category' => 'Kategoria',
		'event_location' => 'Sijainti',
		'event_room'     => 'Huone',
		'event_desc'     => 'Kuvaus',
		'event_date'     => 'Pvm',
		'event_image'    => 'Kuva',
	];
}

add_filter( 'manage_eepos_event_posts_columns', 'eepos_events_define_event_list_columns' );

function eepos_events_get_event_list_column_values( $column, $post_id ) {
	$cur_post = get_post($post_id);
	switch($column) {
		case 'event_category':
			$terms = wp_get_post_terms( $post_id, 'eepos_event_category' );
			echo implode( ', ', array_map( function ( $term ) {
				return $term->name;
		}, $terms ) );
			break;
		case 'event_date':
			$startDate   = get_post_meta( $post_id, 'event_start_date', true );
			$startTime = get_post_meta( $post_id, 'event_start_time', true );
			$currentYear = date( 'Y' );

			if ( $startDate ) {
				$startCombined = $startDate . ' ' . $startTime;
				$startDateDT = new DateTime( $startCombined );
				if ( $startDateDT->format( 'Y' ) === $currentYear ) {
					$startDateFormatted = $startDateDT->format( 'd.n. \k\l\o G.i' );
				} else {
					$startDateFormatted = $startDateDT->format( 'd.n.Y \k\l\o G.i' );
				}
			} else {
				$startDateFormatted = null;
			}

			echo $startDateFormatted;
			break;
		case 'event_location':
			$eventLocation = get_post_meta( $post_id, 'location', true );

			if ( !empty($eventLocation) ) {
				echo esc_html($eventLocation);
			} else {
				echo '-';
			}
			break;
		case 'event_room':
			$eventRoom = get_post_meta( $post_id, 'room', true );

			if ( !empty($eventRoom) ) {
				echo esc_html($eventRoom);
			} else {
				echo '-';
			}
			break;
		case 'event_desc':
			$post_cont = apply_filters('post_cont', $cur_post->post_content);

			if ( !empty($post_cont)) {
				echo $post_cont;
			} else {
				echo '-';
			}
			break;
		case 'event_image':
			$featured_image = wp_get_attachment_url( get_post_thumbnail_id($cur_post));
			if (!empty($featured_image)) {
				echo '<img src="'.esc_url($featured_image).'" width="50px" height="50px" />';
			} else {
				echo '-';
			}
			break;
		default:
			echo '-';
			break;
	}
}

add_filter( 'manage_eepos_event_posts_custom_column', 'eepos_events_get_event_list_column_values', 10, 2 );

function eepos_events_set_sortable_event_list_columns( $columns ) {
	$columns['event_date'] = 'eepos_events_event_date';

	return $columns;
}

add_filter( 'manage_edit-eepos_event_sortable_columns', 'eepos_events_set_sortable_event_list_columns' );

function eepos_events_custom_sorts( WP_Query $query ) {
	$order = $query->get('order') ?: 'ASC';
	$orderBy = $query->get('orderby');

	if ($orderBy === 'eepos_events_event_date') {
		$query->set( 'meta_query', [
			'relation'    => 'AND',
			'date_clause' => [
				'key'     => 'event_start_date',
				'compare' => 'EXISTS'
			],
			'time_clause' => [
				'key'     => 'event_start_time',
				'compare' => 'EXISTS'
			],
		] );
		$query->set( 'orderby', [
			'date_clause' => $order,
			'time_clause' => $order
		] );
	}
}

add_action( 'pre_get_posts', 'eepos_events_custom_sorts' );

function eepos_events_add_import_menu_item() {
	add_submenu_page(
		'edit.php?post_type=eepos_event',
		'Tuo tapahtumat Eepoksesta',
		'Tuo tapahtumat Eepoksesta',
		'manage_options',
		'import-eepos-events',
		'eepos_events_import_page'
	);
}

add_action( 'admin_menu', 'eepos_events_add_import_menu_item' );

function eepos_events_import_page() {
	$formAction = admin_url( 'admin-post.php' );

	$eeposUrl = get_option( 'eepos_events_eepos_url', '' );
	$eeposUrl = esc_attr( $eeposUrl );

	$importKey = get_option( 'eepos_events_import_key', null );
	if ( ! $importKey ) {
		$importKey = bin2hex( openssl_random_pseudo_bytes( 16 ) );
		update_option( 'eepos_events_import_key', $importKey );
	}

	$importUrl = get_site_url() . '/eepos-events/actions/import?key=' . $importKey;

	?>
	<div class="wrap">
		<h1>Tuo tapahtumat Eepoksesta</h1>

		<form action="<?= $formAction ?>" method="post">
			<input type="hidden" name="action" value="eepos_events_import">
			<table class="form-table">
				<tr>
					<th>Eepoksen osoite)</th>
					<td>
						<input type="text" name="eepos_url" class="regular-text" value="<?= $eeposUrl ?>"><br>
						Esim. <strong>demo.eepos.fi</strong>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input class="button button-primary" type="submit" value="Tuo tapahtumat">
			</p>
		</form>

		<form action="<?= $formAction ?>" method="post" id="eepos_events_clear_form">
			<input type="hidden" name="action" value="eepos_events_clear">
			<p class="submit">
				<input class="button button-danger" type="submit" value="Tyhjennä tapahtumat">
			</p>
		</form>

		<h2>Tuo automaattisesti</h2>
		<p>
			Kopioi seuraava osoite Eepoksen integraatiosivun kohtaan "Tapahtumien päivitysosoite".<br>
			<strong>Huom!</strong> Tapahtumat on tuotava ensin kerran manuaalisesti yltä.
			<br><br>
			<input type="text"
			       class="regular-text"
			       style="width: 800px; max-width: 100%"
			       readonly
			       value="<?= esc_attr( $importUrl ) ?>">
		</p>
	</div>

	<script>
		(function() {
			document.getElementById('eepos_events_clear_form').addEventListener('submit', function(ev) {
				var result = window.confirm("Tyhjennä kaikki Eepos-tapahtumat?");
				if (!result) {
					ev.preventDefault();
				}
			});
		})();
	</script>
	<?php
}

function eepos_events_import_action() {
	eepos_events_add_import_menu_item();

	$exit = function ( $success = null, $err = null ) {
		if ( $success ) {
			$_SESSION['eepos_events_success'] = $success;
		}
		if ( $err ) {
			$_SESSION['eepos_events_error'] = $err;
		}

		wp_safe_redirect( menu_page_url( 'import-eepos-events', false ) );
		exit;
	};

	$eeposUrl = $_POST['eepos_url'];

	try {
		eepos_events_import( $eeposUrl );
	} catch ( EeposEventsImportException $e ) {
		$exit( null, $e->getMessage() );
	}

	update_option( 'eepos_events_eepos_url', $eeposUrl );
	$exit( 'Tapahtumat haettu!' );
}

add_action( 'admin_post_eepos_events_import', 'eepos_events_import_action' );

function eepos_events_clear_action() {
	eepos_events_add_import_menu_item();

	$exit = function ( $success = null, $err = null ) {
		if ( $success ) {
			$_SESSION['eepos_events_success'] = $success;
		}
		if ( $err ) {
			$_SESSION['eepos_events_error'] = $err;
		}

		wp_safe_redirect( menu_page_url( 'import-eepos-events', false ) );
		exit;
	};

	$eventPosts = get_posts([
		'post_type' => 'eepos_event',
		'numberposts' => 10000,
	]);

	foreach ($eventPosts as $eventPost) {
		wp_delete_post($eventPost->ID);
	}

	$exit( count($eventPosts) . ' tapahtumaa tyhjennetty!' );
}

add_action( 'admin_post_eepos_events_clear', 'eepos_events_clear_action' );

function eepos_events_admin_notices() {
	$error = $_SESSION['eepos_events_error'] ?? null;
	unset( $_SESSION['eepos_events_error'] );

	if ( $error ) {
		?>
		<div class="error notice">
			<p><?= esc_html( $error ) ?></p>
		</div>
		<?php
	}

	$success = $_SESSION['eepos_events_success'] ?? null;
	unset( $_SESSION['eepos_events_success'] );

	if ( $success ) {
		?>
		<div class="updated notice">
			<p><?= esc_html( $success ) ?></p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'eepos_events_admin_notices' );

function eepos_lisa_metabox() {
	add_meta_box('eepos_custom_meta_box', 'Eepos custom kentät', 'eepos_custom_meta_boxes', 'eepos_event', 'normal', 'high');
}

add_action( 'add_meta_boxes_eepos_event', 'eepos_lisa_metabox' );

function eepos_custom_meta_boxes($post_id) {
	$cur_post_box = get_post($post_id);
	$location_inf = get_post_meta(get_the_ID(), 'location', true);
	$room_inf = get_post_meta(get_the_ID(), 'room', true);
	$featured_image = wp_get_attachment_url( get_post_thumbnail_id($cur_post_box));
	echo '<label for="eepos_location">Sijainti</label><br>';
	echo '<input type="text" name="eepos_location" value="' . esc_attr( $location_inf ) . '" />';
	echo '<br><label for="eepos_room">Huone</label><br>';
	echo '<input type="text" name="eepos_room" value="'.esc_attr( $room_inf ).'" />';
	echo '<br><label for="custom_image">Kuva</label><br>';
	echo '<input type="file" name="custom_image" value="" />';
	echo '<input type="hidden" id="custom_image_manual_flag" name="custom_image_manual_flag" value="true" /><br>';
	if ( !empty($featured_image) ) {
		echo '<img src="'.esc_url($featured_image).'" width="200px" height="200px" />';
	}
}

function eepos_add_edit_form_multipart_encoding() {
	echo ' enctype="multipart/form-data"';
}

add_action('post_edit_form_tag', 'eepos_add_edit_form_multipart_encoding');

function eepos_edit_save_image($post_id) {
	$post_type = get_post_type($post_id);
	if ( $post_id && isset($_POST['custom_image_manual_flag']) ) {
		switch($post_type) {
			case 'eepos_event':
				if ( isset($_POST['eepos_room']) ) {
					$eepos_room_val = sanitize_text_field($_POST['eepos_room']);
					update_post_meta($post_id, 'room', $eepos_room_val);
				}
				if ( isset($_POST['eepos_location']) ) {
					$eepos_location_val = sanitize_text_field($_POST['eepos_location']);
					update_post_meta($post_id, 'location', $eepos_location_val);
				}
				if ( isset($_FILES['custom_image']) && ($_FILES['custom_image']['size'] > 0) ) {
					$allowed_file_size = 2000000;
					if ($_FILES['custom_image']['size'] <= $allowed_file_size) {
						$arr_file_type = wp_check_filetype(basename($_FILES['custom_image']['name']));
						$upload_file_type = $arr_file_type['type'];
						$allowed_file_types = array('image/jpg', 'image/jpeg', 'image/png');

						if ( in_array($upload_file_type, $allowed_file_types) ) {
							$upload_overrides = array( 'test_form' => false );
							if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
							$uploaded_file = wp_handle_upload($_FILES['custom_image'], $upload_overrides);

							if ( isset($uploaded_file['file']) ) {
								$file_name_and_location = $uploaded_file['file'];
								$file_title_eepos = 'eepos_image';
								$attachments = array(
									'post_mime_type' => $upload_file_type,
									'post_title' => 'Uploaded image ' . addslashes($file_title_eepos),
									'post_content' => '',
									'post_status' => 'inherit'
								);

								$attach_id = wp_insert_attachment( $attachments, $file_name_and_location, $post_id );
								if (!is_wp_error($attach_id)) {
									require_once(ABSPATH . "wp-admin" . '/includes/image.php');
									$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
									wp_update_attachment_metadata($attach_id, $attach_data);
								}
								set_post_thumbnail ( $post_id, $attach_id );

								$upload_feedback = false;
							} else {
								$upload_feedback = 'Kuvan lataamisen kanssa tapahtui virhe';
								update_post_meta($post_id, 'custom_image', $attach_id);
							}
						} else {
							$upload_feedback = 'Lataa vain kuvatiedostoja (jpg, jpeg tai png)';
							update_post_meta($post_id, 'custom_image', $attach_id);
						}
					} else {
						$upload_feedback = 'Suurin sallittu kuvan koko on 2MB';
					}
				} else {
					$upload_feedback = false;
				}

				update_post_meta($post_id, 'custom_image_feedback', $upload_feedback);
				break;
			default:
				break;
		}
		return;
	}
	return;
}

add_action('save_post_eepos_event', 'eepos_edit_save_image');

