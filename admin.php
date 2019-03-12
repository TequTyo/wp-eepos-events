<?php

function eepos_events_define_event_list_columns() {
	return [
		'cb'             => '<input type="checkbox">',
		'title'          => 'Tapahtuman nimi',
		'event_category' => 'Kategoria',
		'event_date'     => 'Pvm'
	];
}

add_filter( 'manage_eepos_event_posts_columns', 'eepos_events_define_event_list_columns' );

function eepos_events_get_event_list_column_values( $column, $post_id ) {
	if ( $column === 'event_category' ) {
		$terms = wp_get_post_terms( $post_id, 'eepos_event_category' );
		echo implode(', ', array_map(function($term) { return $term->name; }, $terms));
	} else if ( $column === 'event_date' ) {
		$startDate   = get_post_meta( $post_id, 'event_start_date', true );
		$currentYear = date( 'Y' );

		if ( $startDate ) {
			$startDateDT = new DateTime( $startDate );
			if ( $startDateDT->format( 'Y' ) === $currentYear ) {
				$startDateFormatted = $startDateDT->format( 'd.n. \k\l\o G.i' );
			} else {
				$startDateFormatted = $startDateDT->format( 'd.n.Y \k\l\o G.i' );
			}
		} else {
			$startDateFormatted = null;
		}

		echo $startDateFormatted;
	} else {
		echo '-';
	}
}

add_filter( 'manage_eepos_event_posts_custom_column', 'eepos_events_get_event_list_column_values', 10, 2 );

function eepos_events_add_import_menu_item() {
	add_menu_page( 'Tuo tapahtumat Eepoksesta', 'Tuo tapahtumat Eepoksesta', 'manage_options', 'import-eepos-events', 'eepos_events_import_page' );
}

add_action( 'admin_menu', 'eepos_events_add_import_menu_item' );

function eepos_events_import_page() {
	$formAction = admin_url( 'admin-post.php' );

	$eeposUrl = get_option( 'eepos_events_eepos_url', '' );
	$eeposUrl = esc_attr( $eeposUrl );

	$importKey = get_option('eepos_events_import_key', null);
	if (!$importKey) {
		$importKey = bin2hex(openssl_random_pseudo_bytes(16));
		update_option('eepos_events_import_key', $importKey);
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

		<h2>Tuo automaattisesti</h2>
		<p>
			Kopioi seuraava osoite Eepoksen integraatiosivun kohtaan "Tapahtumien päivitysosoite".<br>
			<strong>Huom!</strong> Tapahtumat on tuotava ensin kerran manuaalisesti yltä.
			<br><br>
			<input type="text"
			       class="regular-text"
			       style="width: 800px; max-width: 100%"
			       readonly
			       value="<?= esc_attr($importUrl) ?>">
		</p>
	</div>
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
	} catch (EeposEventsImportException $e) {
		$exit( null, $e->getMessage() );
	}

	update_option( 'eepos_events_eepos_url', $eeposUrl );
	$exit( 'Tapahtumat haettu!' );
}

add_action( 'admin_post_eepos_events_import', 'eepos_events_import_action' );

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
