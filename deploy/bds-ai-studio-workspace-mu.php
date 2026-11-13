<?php
/**
 * Plugin Name: BrandDad AI Studio Workspace
 * Description: Private Brand Kits, saved content drafts, and a planning calendar for BrandDad administrators.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bds_studio_register_content() {
	$capabilities = array_fill_keys(
		array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ),
		'manage_options'
	);
	$private = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => false,
		'show_in_rest'        => false,
		'show_ui'             => true,
		'show_in_menu'        => 'bds-studio-workspace',
		'can_export'          => false,
		'rewrite'             => false,
		'query_var'           => false,
		'map_meta_cap'        => false,
		'capabilities'        => $capabilities,
		'supports'            => array( 'title', 'editor', 'revisions' ),
	);
	register_post_type( 'bds_studio_kit', array( 'labels' => array( 'name' => 'Brand Kits', 'singular_name' => 'Brand Kit', 'add_new_item' => 'Add Brand Kit' ) ) + $private );
	register_post_type( 'bds_studio_draft', array( 'labels' => array( 'name' => 'Saved Drafts', 'singular_name' => 'Saved Draft', 'add_new_item' => 'Add Saved Draft' ) ) + $private );
}
add_action( 'init', 'bds_studio_register_content' );

function bds_studio_keep_content_private( $data ) {
	if ( in_array( $data['post_type'], array( 'bds_studio_kit', 'bds_studio_draft' ), true ) && in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
		$data['post_status'] = 'private';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'bds_studio_keep_content_private' );

function bds_studio_add_menu() {
	add_menu_page( 'AI Studio Calendar', 'AI Studio', 'manage_options', 'bds-studio-workspace', 'bds_studio_calendar', 'dashicons-calendar-alt' );
}
add_action( 'admin_menu', 'bds_studio_add_menu', 9 );

function bds_studio_add_schedule_box() {
	add_meta_box( 'bds-studio-schedule', 'Content planning', 'bds_studio_schedule_box', 'bds_studio_draft', 'side' );
}
add_action( 'add_meta_boxes_bds_studio_draft', 'bds_studio_add_schedule_box' );

function bds_studio_schedule_box( $post ) {
	$planned_date = get_post_meta( $post->ID, '_bds_studio_planned_date', true );
	$selected_kit = (int) get_post_meta( $post->ID, '_bds_studio_kit_id', true );
	$kits         = get_posts( array( 'post_type' => 'bds_studio_kit', 'post_status' => array( 'draft', 'pending', 'private' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	wp_nonce_field( 'bds_studio_schedule_' . $post->ID, 'bds_studio_nonce' );
	?>
	<p><label for="bds-studio-date">Planned date</label><br><input type="date" id="bds-studio-date" name="bds_studio_date" value="<?php echo esc_attr( $planned_date ); ?>" min="2000-01-01" max="2099-12-31"></p>
	<p class="description">This date appears on the calendar. Posting is manual.</p>
	<p><label for="bds-studio-kit">Brand Kit</label><br>
		<select id="bds-studio-kit" name="bds_studio_kit">
			<option value="0">No Brand Kit</option>
			<?php foreach ( $kits as $kit ) : ?>
				<option value="<?php echo esc_attr( $kit->ID ); ?>" <?php selected( $selected_kit, $kit->ID ); ?>><?php echo esc_html( get_the_title( $kit ) ?: '(Untitled Brand Kit)' ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<?php if ( in_array( $selected_kit, wp_list_pluck( $kits, 'ID' ), true ) ) : ?>
		<p><a href="<?php echo esc_url( get_edit_post_link( $selected_kit, 'raw' ) ); ?>">Open linked Brand Kit</a></p>
	<?php endif; ?>
	<?php
}

function bds_studio_valid_date( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/\A(20[0-9]{2})-([0-9]{2})-([0-9]{2})\z/', $value, $parts ) ) {
		return false;
	}
	return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
}

function bds_studio_save_schedule( $post_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$nonce = $_POST['bds_studio_nonce'] ?? null;
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'bds_studio_schedule_' . $post_id ) ) {
		return;
	}

	$planned_date = $_POST['bds_studio_date'] ?? null;
	if ( '' === $planned_date ) {
		delete_post_meta( $post_id, '_bds_studio_planned_date' );
	} elseif ( bds_studio_valid_date( $planned_date ) ) {
		update_post_meta( $post_id, '_bds_studio_planned_date', $planned_date );
	}

	$kit_id = $_POST['bds_studio_kit'] ?? null;
	if ( '0' === $kit_id ) {
		delete_post_meta( $post_id, '_bds_studio_kit_id' );
	} elseif ( is_string( $kit_id ) && ctype_digit( $kit_id ) && (string) (int) $kit_id === $kit_id ) {
		$kit = get_post( (int) $kit_id );
		if ( $kit && 'bds_studio_kit' === $kit->post_type && in_array( $kit->post_status, array( 'draft', 'pending', 'private' ), true ) && current_user_can( 'read_post', $kit->ID ) ) {
			update_post_meta( $post_id, '_bds_studio_kit_id', $kit->ID );
		}
	}
}
add_action( 'save_post_bds_studio_draft', 'bds_studio_save_schedule' );

function bds_studio_calendar() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have access to the AI Studio workspace.', '', array( 'response' => 403 ) );
	}
	$today     = current_datetime();
	$requested = $_GET['studio_month'] ?? null;
	$first     = is_string( $requested ) && bds_studio_valid_date( $requested . '-01' )
		? new DateTimeImmutable( $requested . '-01', wp_timezone() )
		: $today->modify( 'first day of this month' )->setTime( 0, 0 );
	$last      = $first->modify( 'last day of this month' );
	$start_day = (int) get_option( 'start_of_week', 0 );
	$offset    = ( (int) $first->format( 'w' ) - $start_day + 7 ) % 7;
	$days      = array();
	$draft_ids = get_posts( array(
		'post_type'   => 'bds_studio_draft',
		'post_status' => array( 'draft', 'pending', 'private' ),
		'numberposts' => -1,
		'fields'      => 'ids',
		'orderby'     => 'title',
		'order'       => 'ASC',
		'meta_query'  => array( array(
			'key'     => '_bds_studio_planned_date',
			'value'   => array( $first->format( 'Y-m-d' ), $last->format( 'Y-m-d' ) ),
			'compare' => 'BETWEEN',
		) ),
	) );
	foreach ( $draft_ids as $draft_id ) {
		$days[ get_post_meta( $draft_id, '_bds_studio_planned_date', true ) ][] = $draft_id;
	}
	$previous = $first->modify( '-1 month' )->format( 'Y-m' );
	$next     = $first->modify( '+1 month' )->format( 'Y-m' );
	$page_url = admin_url( 'admin.php?page=bds-studio-workspace' );
	?>
	<div class="wrap bds-studio-workspace">
		<h1>AI Studio Calendar</h1>
		<p>Plan content with saved drafts and reusable Brand Kits. Dates are for planning. Publish from the approved social account when the draft is ready.</p>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=bds_studio_draft' ) ); ?>">Add draft</a> <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=bds_studio_kit' ) ); ?>">Brand Kits</a> <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=bds_studio_draft' ) ); ?>">All drafts</a><?php if ( function_exists( 'bds_video_enabled' ) ) : ?> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bds-video-studio' ) ); ?>">Video Studio</a><?php endif; ?></p>
		<h2><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp() ) ); ?></h2>
		<p>
			<?php if ( bds_studio_valid_date( $previous . '-01' ) ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'studio_month', $previous, $page_url ) ); ?>">Previous month</a><?php endif; ?>
			<a class="button" href="<?php echo esc_url( $page_url ); ?>">This month</a>
			<?php if ( bds_studio_valid_date( $next . '-01' ) ) : ?><a class="button" href="<?php echo esc_url( add_query_arg( 'studio_month', $next, $page_url ) ); ?>">Next month</a><?php endif; ?>
		</p>
		<?php if ( ! $draft_ids ) : ?><p>No drafts are planned for this month.</p><?php endif; ?>
		<style>
			.bds-studio-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:6px;padding:0;list-style:none}
			.bds-studio-day{min-height:85px;margin:0;padding:8px;background:#fff;border:1px solid #c3c4c7;overflow-wrap:anywhere}
			.bds-studio-day:first-child{grid-column:var(--bds-first-day)}
			.bds-studio-day[aria-current=date]{border:2px solid #2271b1}
			.bds-studio-day time{display:block;margin-bottom:4px;font-weight:600}
			.bds-studio-day ul{margin:0}
			@media(max-width:782px){.bds-studio-days{grid-template-columns:1fr}.bds-studio-day:first-child{grid-column:1}.bds-studio-day{min-height:0}.bds-studio-day--empty:not([aria-current]){display:none}}
		</style>
		<ol class="bds-studio-days" aria-label="Planned drafts">
			<?php for ( $day = $first; $day <= $last; $day = $day->modify( '+1 day' ) ) : ?>
				<?php $date = $day->format( 'Y-m-d' ); $scheduled = $days[ $date ] ?? array(); ?>
				<li class="bds-studio-day<?php echo $scheduled ? '' : ' bds-studio-day--empty'; ?>"<?php if ( $day === $first ) : ?> style="--bds-first-day:<?php echo esc_attr( $offset + 1 ); ?>"<?php endif; ?><?php if ( $date === $today->format( 'Y-m-d' ) ) : ?> aria-current="date"<?php endif; ?>>
					<time datetime="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( wp_date( 'D j', $day->getTimestamp() ) ); ?></time>
					<?php if ( $scheduled ) : ?>
						<ul>
							<?php foreach ( $scheduled as $draft_id ) : ?>
								<li><a href="<?php echo esc_url( get_edit_post_link( $draft_id, 'raw' ) ); ?>"><?php echo esc_html( get_the_title( $draft_id ) ?: '(Untitled draft)' ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endfor; ?>
		</ol>
	</div>
	<?php
}
