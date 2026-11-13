<?php
/**
 * Plugin Name: BrandDad AI Video Studio (Staged)
 * Description: Tenant-scoped video projects, deterministic cost quotes, approval gates, and fail-closed render jobs for the existing BrandDad AI Studio.
 * Version: 0.2.0
 * Author: BrandDad Social
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BDS_VIDEO_STUDIO_VERSION', '0.2.0' );

/** Feature flags are deliberately independent and default off. */
function bds_video_flag( $name ) {
	return defined( $name ) && true === constant( $name );
}

function bds_video_enabled() {
	return bds_video_flag( 'BDS_VIDEO_STUDIO_ENABLED' );
}

/** Server-derived tenant boundary. Never accept this value from a request. */
function bds_video_tenant_key( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id ) {
		return '';
	}
	return hash( 'sha256', get_current_blog_id() . '|' . strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ) . '|' . $user_id );
}

function bds_video_tables() {
	global $wpdb;
	return array(
		'projects' => $wpdb->prefix . 'bds_video_projects',
		'jobs'     => $wpdb->prefix . 'bds_video_jobs',
		'usage'    => $wpdb->prefix . 'bds_video_usage',
		'audit'    => $wpdb->prefix . 'bds_video_audit',
		'consent'  => $wpdb->prefix . 'bds_video_consent',
	);
}

/** Install only after an administrator explicitly enables the staged module. */
function bds_video_maybe_install() {
	if ( ! bds_video_enabled() || ! current_user_can( 'manage_options' ) || get_option( 'bds_video_schema_version' ) === BDS_VIDEO_STUDIO_VERSION ) {
		return;
	}
	global $wpdb;
	$tables  = bds_video_tables();
	$charset = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( "CREATE TABLE {$tables['projects']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		public_id char(36) NOT NULL,
		tenant_key char(64) NOT NULL,
		owner_user_id bigint(20) unsigned NOT NULL,
		parent_project_id bigint(20) unsigned NOT NULL DEFAULT 0,
		title varchar(160) NOT NULL,
		source_type varchar(32) NOT NULL,
		status varchar(24) NOT NULL DEFAULT 'draft',
		approval_mode varchar(24) NOT NULL DEFAULT 'approval',
		language varchar(12) NOT NULL DEFAULT 'en',
		aspect_ratio varchar(8) NOT NULL DEFAULT '9:16',
		duration_preset smallint(5) unsigned NOT NULL DEFAULT 30,
		source_summary text NULL,
		scenes_json longtext NULL,
		version_no int(10) unsigned NOT NULL DEFAULT 1,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY public_id (public_id),
		KEY tenant_owner (tenant_key,owner_user_id),
		KEY parent_project (parent_project_id),
		KEY owner_updated (owner_user_id,updated_at)
	) $charset;" );

	dbDelta( "CREATE TABLE {$tables['jobs']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		public_id char(36) NOT NULL,
		project_id bigint(20) unsigned NOT NULL,
		tenant_key char(64) NOT NULL,
		owner_user_id bigint(20) unsigned NOT NULL,
		job_type varchar(32) NOT NULL,
		status varchar(24) NOT NULL DEFAULT 'queued',
		provider varchar(32) NOT NULL DEFAULT '',
		provider_job_id varchar(191) NOT NULL DEFAULT '',
		idempotency_hash char(64) NOT NULL,
		quoted_cost decimal(12,4) NOT NULL DEFAULT 0,
		actual_cost decimal(12,4) NOT NULL DEFAULT 0,
		reserved_credits int(10) unsigned NOT NULL DEFAULT 0,
		attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
		error_code varchar(64) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY public_id (public_id),
		UNIQUE KEY tenant_idempotency (tenant_key,idempotency_hash),
		KEY project_status (project_id,status),
		KEY owner_status (owner_user_id,status)
	) $charset;" );

	dbDelta( "CREATE TABLE {$tables['usage']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		tenant_key char(64) NOT NULL,
		owner_user_id bigint(20) unsigned NOT NULL,
		project_id bigint(20) unsigned NOT NULL DEFAULT 0,
		job_id bigint(20) unsigned NOT NULL DEFAULT 0,
		entry_type varchar(24) NOT NULL,
		credits int(11) NOT NULL DEFAULT 0,
		provider_cost decimal(12,4) NOT NULL DEFAULT 0,
		request_id char(36) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY request_id (request_id),
		KEY owner_created (owner_user_id,created_at)
	) $charset;" );

	dbDelta( "CREATE TABLE {$tables['audit']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		tenant_key char(64) NOT NULL,
		actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		resource_type varchar(24) NOT NULL,
		resource_id char(36) NOT NULL,
		action_name varchar(48) NOT NULL,
		from_state varchar(24) NOT NULL DEFAULT '',
		to_state varchar(24) NOT NULL DEFAULT '',
		request_id char(36) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY resource (resource_type,resource_id),
		KEY tenant_created (tenant_key,created_at)
	) $charset;" );

	dbDelta( "CREATE TABLE {$tables['consent']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		public_id char(36) NOT NULL,
		project_id bigint(20) unsigned NOT NULL,
		tenant_key char(64) NOT NULL,
		owner_user_id bigint(20) unsigned NOT NULL,
		consent_type varchar(32) NOT NULL,
		assertion_version varchar(24) NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY project_consent (project_id,consent_type),
		UNIQUE KEY public_id (public_id)
	) $charset;" );

	update_option( 'bds_video_schema_version', BDS_VIDEO_STUDIO_VERSION, false );
}
add_action( 'admin_init', 'bds_video_maybe_install' );

function bds_video_now() {
	return current_time( 'mysql', true );
}

function bds_video_uuid() {
	return wp_generate_uuid4();
}

function bds_video_audit( $resource_type, $resource_id, $action, $from = '', $to = '', $actor = 0 ) {
	global $wpdb;
	$tables = bds_video_tables();
	$wpdb->insert(
		$tables['audit'],
		array(
			'tenant_key'     => bds_video_tenant_key( $actor ? $actor : get_current_user_id() ),
			'actor_user_id'  => absint( $actor ? $actor : get_current_user_id() ),
			'resource_type'  => sanitize_key( $resource_type ),
			'resource_id'    => sanitize_text_field( $resource_id ),
			'action_name'    => sanitize_key( $action ),
			'from_state'     => sanitize_key( $from ),
			'to_state'       => sanitize_key( $to ),
			'request_id'     => bds_video_uuid(),
			'created_at'     => bds_video_now(),
		),
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
}

function bds_video_find_project( $public_id, $user_id = 0 ) {
	global $wpdb;
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! wp_is_uuid( $public_id ) || ! $user_id ) {
		return null;
	}
	$tables = bds_video_tables();
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$tables['projects']} WHERE public_id=%s AND tenant_key=%s AND owner_user_id=%d LIMIT 1",
			$public_id,
			bds_video_tenant_key( $user_id ),
			$user_id
		),
		ARRAY_A
	);
}

function bds_video_project_response( $row ) {
	$scenes = json_decode( (string) $row['scenes_json'], true );
	if ( ! is_array( $scenes ) ) {
		$scenes = array();
	}
	return array(
		'id'              => (string) $row['public_id'],
		'title'           => (string) $row['title'],
		'sourceType'      => (string) $row['source_type'],
		'status'          => (string) $row['status'],
		'approvalMode'    => (string) $row['approval_mode'],
		'language'        => (string) $row['language'],
		'aspectRatio'     => (string) $row['aspect_ratio'],
		'durationSeconds' => (int) $row['duration_preset'],
		'sourceSummary'   => (string) $row['source_summary'],
		'scenes'          => $scenes,
		'version'         => (int) $row['version_no'],
		'createdAt'       => mysql_to_rfc3339( $row['created_at'] ),
		'updatedAt'       => mysql_to_rfc3339( $row['updated_at'] ),
	);
}

function bds_video_job_response( $row ) {
	return array(
		'id'              => (string) $row['public_id'],
		'type'            => (string) $row['job_type'],
		'status'          => (string) $row['status'],
		'estimatedCost'   => round( (float) $row['quoted_cost'], 4 ),
		'actualCost'      => round( (float) $row['actual_cost'], 4 ),
		'reservedCredits' => (int) $row['reserved_credits'],
		'errorCode'       => (string) $row['error_code'],
		'createdAt'       => mysql_to_rfc3339( $row['created_at'] ),
		'updatedAt'       => mysql_to_rfc3339( $row['updated_at'] ),
	);
}

function bds_video_error( $code, $message, $status ) {
	return new WP_Error( sanitize_key( $code ), $message, array( 'status' => absint( $status ) ) );
}

function bds_video_rest_permission() {
	return bds_video_enabled() && is_user_logged_in() && current_user_can( 'read' );
}

function bds_video_rate_limit( $bucket, $max, $window ) {
	$user_id = get_current_user_id();
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$keys    = array( 'bdsv_ru_' . md5( $bucket . '|' . $user_id ), 'bdsv_ri_' . md5( $bucket . '|' . $ip ) );
	foreach ( $keys as $key ) {
		if ( absint( get_transient( $key ) ) >= $max ) {
			return false;
		}
	}
	foreach ( $keys as $key ) {
		set_transient( $key, absint( get_transient( $key ) ) + 1, absint( $window ) );
	}
	return true;
}

function bds_video_request_has_only( WP_REST_Request $request, $allowed ) {
	$body = $request->get_json_params();
	if ( ! is_array( $body ) ) {
		return false;
	}
	return ! array_diff( array_keys( $body ), $allowed );
}

function bds_video_rest_create_project( WP_REST_Request $request ) {
	if ( ! bds_video_rate_limit( 'create-project', 20, HOUR_IN_SECONDS ) ) {
		return bds_video_error( 'rate_limited', 'Please wait before creating another project.', 429 );
	}
	if ( ! bds_video_request_has_only( $request, array( 'title', 'sourceType', 'sourceSummary', 'language', 'aspectRatio', 'durationSeconds' ) ) ) {
		return bds_video_error( 'invalid_fields', 'The request contains unsupported fields.', 400 );
	}
	$title        = sanitize_text_field( (string) $request->get_param( 'title' ) );
	$source_type  = sanitize_key( (string) $request->get_param( 'sourceType' ) );
	$summary      = sanitize_textarea_field( (string) $request->get_param( 'sourceSummary' ) );
	$language     = strtolower( sanitize_text_field( (string) ( $request->get_param( 'language' ) ?: 'en' ) ) );
	$aspect       = sanitize_text_field( (string) ( $request->get_param( 'aspectRatio' ) ?: '9:16' ) );
	$duration     = absint( $request->get_param( 'durationSeconds' ) ?: 30 );
	$source_types = array( 'prompt', 'original', 'website', 'blog', 'product', 'directory', 'upload', 'smart_mix' );
	if ( '' === $title || strlen( $title ) > 160 || ! in_array( $source_type, $source_types, true ) || strlen( $summary ) > 5000 || ! preg_match( '/\A[a-z]{2}(?:-[a-z]{2})?\z/', $language ) || ! in_array( $aspect, array( '9:16', '16:9', '1:1', '4:5' ), true ) || ! in_array( $duration, array( 15, 30, 60, 90 ), true ) ) {
		return bds_video_error( 'invalid_project', 'Review the title, source, language, aspect ratio, and duration.', 400 );
	}
	global $wpdb;
	$tables  = bds_video_tables();
	$user_id = get_current_user_id();
	$id      = bds_video_uuid();
	$now     = bds_video_now();
	$ok      = $wpdb->insert(
		$tables['projects'],
		array(
			'public_id'       => $id,
			'tenant_key'      => bds_video_tenant_key( $user_id ),
			'owner_user_id'   => $user_id,
			'parent_project_id'=> 0,
			'title'           => $title,
			'source_type'     => $source_type,
			'status'          => 'draft',
			'approval_mode'   => 'approval',
			'language'        => $language,
			'aspect_ratio'    => $aspect,
			'duration_preset' => $duration,
			'source_summary'  => $summary,
			'scenes_json'     => '[]',
			'version_no'      => 1,
			'created_at'      => $now,
			'updated_at'      => $now,
		),
		array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
	);
	if ( ! $ok ) {
		return bds_video_error( 'storage_failed', 'The project could not be created.', 500 );
	}
	bds_video_audit( 'project', $id, 'create', '', 'draft' );
	return new WP_REST_Response( bds_video_project_response( bds_video_find_project( $id ) ), 201 );
}

function bds_video_rest_list_projects( WP_REST_Request $request ) {
	global $wpdb;
	$tables  = bds_video_tables();
	$user_id = get_current_user_id();
	$limit   = min( 50, max( 1, absint( $request->get_param( 'limit' ) ?: 20 ) ) );
	$rows    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$tables['projects']} WHERE tenant_key=%s AND owner_user_id=%d ORDER BY updated_at DESC,id DESC LIMIT %d",
			bds_video_tenant_key( $user_id ),
			$user_id,
			$limit
		),
		ARRAY_A
	);
	return array( 'projects' => array_map( 'bds_video_project_response', $rows ), 'limit' => $limit );
}

function bds_video_rest_get_project( WP_REST_Request $request ) {
	$row = bds_video_find_project( (string) $request['id'] );
	return $row ? bds_video_project_response( $row ) : bds_video_error( 'not_found', 'Project not found.', 404 );
}

function bds_video_rest_list_jobs( WP_REST_Request $request ) {
	$project = bds_video_find_project( (string) $request['id'] );
	if ( ! $project ) {
		return bds_video_error( 'not_found', 'Project not found.', 404 );
	}
	global $wpdb;
	$tables = bds_video_tables();
	$rows   = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$tables['jobs']} WHERE project_id=%d AND tenant_key=%s AND owner_user_id=%d ORDER BY created_at DESC,id DESC LIMIT 25",
			(int) $project['id'],
			$project['tenant_key'],
			get_current_user_id()
		),
		ARRAY_A
	);
	return array( 'jobs' => array_map( 'bds_video_job_response', $rows ) );
}

function bds_video_rest_create_version( WP_REST_Request $request ) {
	if ( ! bds_video_rate_limit( 'create-version', 20, HOUR_IN_SECONDS ) ) {
		return bds_video_error( 'rate_limited', 'Please wait before creating another version.', 429 );
	}
	if ( ! bds_video_request_has_only( $request, array() ) ) {
		return bds_video_error( 'invalid_fields', 'The version request contains unsupported fields.', 400 );
	}
	$project = bds_video_find_project( (string) $request['id'] );
	if ( ! $project ) {
		return bds_video_error( 'not_found', 'Project not found.', 404 );
	}
	if ( 'approved' !== $project['status'] ) {
		return bds_video_error( 'invalid_state', 'Create a new version only after the current version is approved.', 409 );
	}
	global $wpdb;
	$tables  = bds_video_tables();
	$user_id = get_current_user_id();
	$id      = bds_video_uuid();
	$now     = bds_video_now();
	$ok      = $wpdb->insert(
		$tables['projects'],
		array(
			'public_id'        => $id,
			'tenant_key'       => $project['tenant_key'],
			'owner_user_id'    => $user_id,
			'parent_project_id'=> (int) $project['id'],
			'title'            => $project['title'],
			'source_type'      => $project['source_type'],
			'status'           => 'draft',
			'approval_mode'    => 'approval',
			'language'         => $project['language'],
			'aspect_ratio'     => $project['aspect_ratio'],
			'duration_preset'  => (int) $project['duration_preset'],
			'source_summary'   => $project['source_summary'],
			'scenes_json'      => $project['scenes_json'],
			'version_no'       => (int) $project['version_no'] + 1,
			'created_at'       => $now,
			'updated_at'       => $now,
		),
		array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
	);
	if ( ! $ok ) {
		return bds_video_error( 'storage_failed', 'The new version could not be created.', 500 );
	}
	bds_video_audit( 'project', $id, 'create_version', 'approved', 'draft' );
	return new WP_REST_Response( bds_video_project_response( bds_video_find_project( $id ) ), 201 );
}

function bds_video_normalize_scenes( $scenes ) {
	if ( ! is_array( $scenes ) || count( $scenes ) > 50 ) {
		return null;
	}
	$clean = array();
	foreach ( $scenes as $index => $scene ) {
		if ( ! is_array( $scene ) || array_diff( array_keys( $scene ), array( 'durationSeconds', 'narration', 'onScreenText', 'visualDirection' ) ) ) {
			return null;
		}
		$duration = absint( $scene['durationSeconds'] ?? 0 );
		$narration = sanitize_textarea_field( (string) ( $scene['narration'] ?? '' ) );
		$on_screen = sanitize_textarea_field( (string) ( $scene['onScreenText'] ?? '' ) );
		$visual = sanitize_textarea_field( (string) ( $scene['visualDirection'] ?? '' ) );
		if ( $duration < 1 || $duration > 90 || strlen( $narration ) > 1200 || strlen( $on_screen ) > 500 || strlen( $visual ) > 1200 ) {
			return null;
		}
		$clean[] = array( 'index' => (int) $index, 'durationSeconds' => $duration, 'narration' => $narration, 'onScreenText' => $on_screen, 'visualDirection' => $visual );
	}
	return $clean;
}

function bds_video_rest_update_project( WP_REST_Request $request ) {
	if ( ! bds_video_request_has_only( $request, array( 'title', 'sourceSummary', 'scenes' ) ) ) {
		return bds_video_error( 'invalid_fields', 'The request contains unsupported fields.', 400 );
	}
	$project = bds_video_find_project( (string) $request['id'] );
	if ( ! $project ) {
		return bds_video_error( 'not_found', 'Project not found.', 404 );
	}
	if ( ! in_array( $project['status'], array( 'draft', 'preview_ready', 'changes_requested' ), true ) ) {
		return bds_video_error( 'invalid_state', 'Approved projects are immutable; create a new version to make changes.', 409 );
	}
	$fields = array( 'updated_at' => bds_video_now(), 'version_no' => (int) $project['version_no'] + 1, 'status' => 'changes_requested' );
	$formats = array( '%s', '%d', '%s' );
	if ( null !== $request->get_param( 'title' ) ) {
		$title = sanitize_text_field( (string) $request->get_param( 'title' ) );
		if ( '' === $title || strlen( $title ) > 160 ) {
			return bds_video_error( 'invalid_title', 'The title is invalid.', 400 );
		}
		$fields['title'] = $title;
		$formats[] = '%s';
	}
	if ( null !== $request->get_param( 'sourceSummary' ) ) {
		$summary = sanitize_textarea_field( (string) $request->get_param( 'sourceSummary' ) );
		if ( strlen( $summary ) > 5000 ) {
			return bds_video_error( 'invalid_summary', 'The source summary is too long.', 400 );
		}
		$fields['source_summary'] = $summary;
		$formats[] = '%s';
	}
	if ( null !== $request->get_param( 'scenes' ) ) {
		$scenes = bds_video_normalize_scenes( $request->get_param( 'scenes' ) );
		if ( null === $scenes ) {
			return bds_video_error( 'invalid_scenes', 'Scenes are invalid or exceed their limits.', 400 );
		}
		$fields['scenes_json'] = wp_json_encode( $scenes );
		$formats[] = '%s';
	}
	global $wpdb;
	$tables = bds_video_tables();
	$wpdb->update( $tables['projects'], $fields, array( 'id' => (int) $project['id'], 'tenant_key' => $project['tenant_key'], 'owner_user_id' => get_current_user_id() ), $formats, array( '%d', '%s', '%d' ) );
	bds_video_audit( 'project', $project['public_id'], 'edit', $project['status'], 'changes_requested' );
	return bds_video_project_response( bds_video_find_project( $project['public_id'] ) );
}

function bds_video_rest_approve_project( WP_REST_Request $request ) {
	$project = bds_video_find_project( (string) $request['id'] );
	if ( ! $project ) {
		return bds_video_error( 'not_found', 'Project not found.', 404 );
	}
	if ( 'preview_ready' !== $project['status'] ) {
		return bds_video_error( 'invalid_state', 'Only a completed preview can be approved.', 409 );
	}
	global $wpdb;
	$tables = bds_video_tables();
	$wpdb->update( $tables['projects'], array( 'status' => 'approved', 'updated_at' => bds_video_now() ), array( 'id' => (int) $project['id'], 'tenant_key' => $project['tenant_key'], 'owner_user_id' => get_current_user_id() ), array( '%s', '%s' ), array( '%d', '%s', '%d' ) );
	bds_video_audit( 'project', $project['public_id'], 'approve', 'preview_ready', 'approved' );
	return bds_video_project_response( bds_video_find_project( $project['public_id'] ) );
}

function bds_video_quote( $project ) {
	$seconds      = (int) $project['duration_preset'];
	$render_cost  = round( ( $seconds / 60 ) * 0.20, 4 );
	$voice_chars  = max( 180, $seconds * 13 );
	$voice_cost   = round( ( $voice_chars / 1000 ) * 0.05, 4 );
	$model_cost   = 0.08;
	$storage_cost = 0.01;
	$total        = round( $render_cost + $voice_cost + $model_cost + $storage_cost, 4 );
	return array(
		'currency'          => 'USD',
		'estimatedCost'     => $total,
		'reservedCredits'   => max( 1, (int) ceil( $total * 10 ) ),
		'durationSeconds'   => $seconds,
		'costModelVersion'  => 'template-short-v1',
		'includesPremiumAi' => false,
		'breakdown'         => array( 'render' => $render_cost, 'voice' => $voice_cost, 'planning' => $model_cost, 'storage' => $storage_cost ),
	);
}

/** Monthly safety ceiling. Subscription integration may lower or raise it explicitly. */
function bds_video_usage_summary( $user_id = 0 ) {
	global $wpdb;
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$tables  = bds_video_tables();
	$start   = gmdate( 'Y-m-01 00:00:00' );
	$net     = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(credits),0) FROM {$tables['usage']} WHERE tenant_key=%s AND owner_user_id=%d AND created_at >= %s",
			bds_video_tenant_key( $user_id ),
			$user_id,
			$start
		)
	);
	$used = max( 0, 0 - $net );
	$cap  = (int) apply_filters( 'bds_video_monthly_credit_cap', 100, $user_id );
	$cap  = min( 100000, max( 0, $cap ) );
	return array(
		'periodStart'     => mysql_to_rfc3339( $start ),
		'usedCredits'     => $used,
		'monthlyCap'      => $cap,
		'remainingCredits'=> max( 0, $cap - $used ),
	);
}

function bds_video_rest_usage() {
	return bds_video_usage_summary();
}

function bds_video_rest_quote( WP_REST_Request $request ) {
	$row = bds_video_find_project( (string) $request['id'] );
	if ( ! $row ) {
		return bds_video_error( 'not_found', 'Project not found.', 404 );
	}
	return bds_video_quote( $row );
}

function bds_video_rest_create_render( WP_REST_Request $request ) {
	if ( ! bds_video_rate_limit( 'render', 5, HOUR_IN_SECONDS ) ) {
		return bds_video_error( 'rate_limited', 'The hourly render limit has been reached.', 429 );
	}
	if ( ! bds_video_flag( 'BDS_VIDEO_PROVIDER_ENABLED' ) ) {
		return bds_video_error( 'provider_disabled', 'Video rendering is staged and not enabled.', 503 );
	}
	if ( ! bds_video_request_has_only( $request, array( 'idempotencyKey' ) ) ) {
		return bds_video_error( 'invalid_fields', 'The request contains unsupported fields.', 400 );
	}
	$project = bds_video_find_project( (string) $request['id'] );
	$key     = sanitize_text_field( (string) $request->get_param( 'idempotencyKey' ) );
	if ( ! $project || ! preg_match( '/\A[A-Za-z0-9._:-]{8,64}\z/', $key ) ) {
		return bds_video_error( 'invalid_render', 'Project or idempotency key is invalid.', 400 );
	}
	if ( ! in_array( $project['status'], array( 'draft', 'preview_ready', 'changes_requested' ), true ) ) {
		return bds_video_error( 'invalid_state', 'This project cannot be rendered in its current state.', 409 );
	}
	global $wpdb;
	$tables = bds_video_tables();
	$hash   = hash( 'sha256', $project['tenant_key'] . '|' . get_current_user_id() . '|' . $key );
	$prior  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE tenant_key=%s AND idempotency_hash=%s LIMIT 1", $project['tenant_key'], $hash ), ARRAY_A );
	if ( $prior ) {
		return array( 'jobId' => $prior['public_id'], 'status' => $prior['status'], 'idempotentReplay' => true );
	}
	$quote  = bds_video_quote( $project );
	$lock_name = 'bdsv_' . substr( hash( 'sha256', $project['tenant_key'] . '|' . get_current_user_id() ), 0, 40 );
	$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock_name ) );
	if ( 1 !== $locked ) {
		return bds_video_error( 'usage_busy', 'Usage is being updated. Please try again.', 503 );
	}
	$prior = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE tenant_key=%s AND idempotency_hash=%s LIMIT 1", $project['tenant_key'], $hash ), ARRAY_A );
	if ( $prior ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		return array( 'jobId' => $prior['public_id'], 'status' => $prior['status'], 'idempotentReplay' => true );
	}
	$usage  = bds_video_usage_summary();
	if ( (int) $quote['reservedCredits'] > (int) $usage['remainingCredits'] ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		return bds_video_error( 'credit_limit_reached', 'The monthly video credit limit has been reached.', 429 );
	}
	$job_id = bds_video_uuid();
	$now    = bds_video_now();
	$wpdb->query( 'START TRANSACTION' );
	$job_created = $wpdb->insert(
		$tables['jobs'],
		array(
			'public_id'        => $job_id,
			'project_id'       => (int) $project['id'],
			'tenant_key'       => $project['tenant_key'],
			'owner_user_id'    => get_current_user_id(),
			'job_type'         => 'preview_render',
			'status'           => 'dispatching',
			'provider'         => 'configured_adapter',
			'idempotency_hash' => $hash,
			'quoted_cost'      => $quote['estimatedCost'],
			'reserved_credits' => $quote['reservedCredits'],
			'created_at'       => $now,
			'updated_at'       => $now,
		),
		array( '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s' )
	);
	$job_database_id = (int) $wpdb->insert_id;
	$usage_reserved  = $job_created ? $wpdb->insert( $tables['usage'], array( 'tenant_key' => $project['tenant_key'], 'owner_user_id' => get_current_user_id(), 'project_id' => (int) $project['id'], 'job_id' => $job_database_id, 'entry_type' => 'reserve', 'credits' => 0 - (int) $quote['reservedCredits'], 'provider_cost' => 0, 'request_id' => bds_video_uuid(), 'created_at' => bds_video_now() ), array( '%s', '%d', '%d', '%d', '%s', '%d', '%f', '%s', '%s' ) ) : false;
	if ( ! $job_created || ! $usage_reserved ) {
		$wpdb->query( 'ROLLBACK' );
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		return bds_video_error( 'reservation_failed', 'The render credit reservation could not be created.', 500 );
	}
	$wpdb->query( 'COMMIT' );
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
	$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE public_id=%s LIMIT 1", $job_id ), ARRAY_A );
	$result = apply_filters( 'bds_video_dispatch_render', null, $job, $project, $quote );
	if ( ! is_array( $result ) || empty( $result['providerJobId'] ) ) {
		$wpdb->update( $tables['jobs'], array( 'status' => 'blocked', 'error_code' => 'adapter_unavailable', 'updated_at' => bds_video_now() ), array( 'id' => (int) $job['id'] ), array( '%s', '%s', '%s' ), array( '%d' ) );
		$wpdb->insert( $tables['usage'], array( 'tenant_key' => $project['tenant_key'], 'owner_user_id' => get_current_user_id(), 'project_id' => (int) $project['id'], 'job_id' => (int) $job['id'], 'entry_type' => 'refund', 'credits' => (int) $quote['reservedCredits'], 'provider_cost' => 0, 'request_id' => bds_video_uuid(), 'created_at' => bds_video_now() ), array( '%s', '%d', '%d', '%d', '%s', '%d', '%f', '%s', '%s' ) );
		bds_video_audit( 'job', $job_id, 'dispatch_blocked', 'dispatching', 'blocked' );
		return bds_video_error( 'adapter_unavailable', 'The approved render adapter is not configured.', 503 );
	}
	$provider_id = sanitize_text_field( (string) $result['providerJobId'] );
	$wpdb->update( $tables['jobs'], array( 'status' => 'queued', 'provider_job_id' => $provider_id, 'updated_at' => bds_video_now() ), array( 'id' => (int) $job['id'] ), array( '%s', '%s', '%s' ), array( '%d' ) );
	bds_video_audit( 'job', $job_id, 'dispatch', 'dispatching', 'queued' );
	return new WP_REST_Response( array( 'jobId' => $job_id, 'status' => 'queued', 'quote' => $quote ), 202 );
}

function bds_video_verify_webhook( WP_REST_Request $request ) {
	if ( ! bds_video_flag( 'BDS_VIDEO_PROVIDER_ENABLED' ) || ! defined( 'BDS_VIDEO_WEBHOOK_SECRET' ) || strlen( (string) BDS_VIDEO_WEBHOOK_SECRET ) < 32 ) {
		return false;
	}
	$timestamp = (string) $request->get_header( 'x-bds-timestamp' );
	$signature = strtolower( (string) $request->get_header( 'x-bds-signature' ) );
	$delivery  = sanitize_text_field( (string) $request->get_header( 'x-bds-delivery' ) );
	if ( ! ctype_digit( $timestamp ) || abs( time() - (int) $timestamp ) > 300 || ! preg_match( '/\A[a-f0-9]{64}\z/', $signature ) || ! preg_match( '/\A[A-Za-z0-9._:-]{8,100}\z/', $delivery ) ) {
		return false;
	}
	$expected = hash_hmac( 'sha256', $timestamp . '.' . $request->get_body(), (string) BDS_VIDEO_WEBHOOK_SECRET );
	if ( ! hash_equals( $expected, $signature ) ) {
		return false;
	}
	$replay_key = 'bdsv_wh_' . md5( $delivery );
	if ( get_transient( $replay_key ) ) {
		return false;
	}
	set_transient( $replay_key, 1, 10 * MINUTE_IN_SECONDS );
	return true;
}

function bds_video_rest_webhook( WP_REST_Request $request ) {
	if ( ! bds_video_rate_limit( 'render-webhook', 120, HOUR_IN_SECONDS ) ) {
		return bds_video_error( 'rate_limited', 'Webhook rate limit reached.', 429 );
	}
	if ( ! bds_video_verify_webhook( $request ) ) {
		return bds_video_error( 'invalid_webhook', 'Webhook verification failed.', 401 );
	}
	$body = $request->get_json_params();
	if ( ! is_array( $body ) || array_diff( array_keys( $body ), array( 'jobId', 'status', 'providerJobId', 'actualCost', 'errorCode' ) ) ) {
		return bds_video_error( 'invalid_payload', 'Webhook payload is invalid.', 400 );
	}
	$job_id = sanitize_text_field( (string) ( $body['jobId'] ?? '' ) );
	$status = sanitize_key( (string) ( $body['status'] ?? '' ) );
	if ( ! wp_is_uuid( $job_id ) || ! in_array( $status, array( 'processing', 'completed', 'failed' ), true ) ) {
		return bds_video_error( 'invalid_payload', 'Webhook payload is invalid.', 400 );
	}
	global $wpdb;
	$tables = bds_video_tables();
	$job    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE public_id=%s LIMIT 1", $job_id ), ARRAY_A );
	if ( ! $job || ! in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {
		return bds_video_error( 'not_found', 'Job not found.', 404 );
	}
	$callback_provider_id = sanitize_text_field( (string) ( $body['providerJobId'] ?? '' ) );
	if ( '' !== $job['provider_job_id'] && ! hash_equals( (string) $job['provider_job_id'], $callback_provider_id ) ) {
		return bds_video_error( 'invalid_provider_job', 'Provider job verification failed.', 401 );
	}
	$actual     = isset( $body['actualCost'] ) ? max( 0, round( (float) $body['actualCost'], 4 ) ) : 0;
	$cost_limit = max( (float) $job['quoted_cost'] * 2, (float) $job['quoted_cost'] + 5 );
	if ( $actual > $cost_limit ) {
		return bds_video_error( 'cost_mismatch', 'Provider cost exceeded the approved safety bound.', 409 );
	}
	$wpdb->update(
		$tables['jobs'],
		array( 'status' => $status, 'provider_job_id' => $callback_provider_id, 'actual_cost' => $actual, 'error_code' => sanitize_key( (string) ( $body['errorCode'] ?? '' ) ), 'updated_at' => bds_video_now() ),
		array( 'id' => (int) $job['id'] ),
		array( '%s', '%s', '%f', '%s', '%s' ),
		array( '%d' )
	);
	if ( in_array( $status, array( 'completed', 'failed' ), true ) ) {
		$refund = 'failed' === $status ? (int) $job['reserved_credits'] : 0;
		$wpdb->insert( $tables['usage'], array( 'tenant_key' => $job['tenant_key'], 'owner_user_id' => (int) $job['owner_user_id'], 'project_id' => (int) $job['project_id'], 'job_id' => (int) $job['id'], 'entry_type' => 'failed' === $status ? 'refund' : 'settle', 'credits' => $refund, 'provider_cost' => $actual, 'request_id' => bds_video_uuid(), 'created_at' => bds_video_now() ), array( '%s', '%d', '%d', '%d', '%s', '%d', '%f', '%s', '%s' ) );
	}
	if ( 'completed' === $status ) {
		$wpdb->update( $tables['projects'], array( 'status' => 'preview_ready', 'updated_at' => bds_video_now() ), array( 'id' => (int) $job['project_id'], 'tenant_key' => $job['tenant_key'], 'owner_user_id' => (int) $job['owner_user_id'] ), array( '%s', '%s' ), array( '%d', '%s', '%d' ) );
	}
	bds_video_audit( 'job', $job_id, 'provider_update', $job['status'], $status, (int) $job['owner_user_id'] );
	return array( 'accepted' => true );
}

function bds_video_register_rest() {
	register_rest_route( 'bds-video/v1', '/usage', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'bds_video_rest_usage', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/projects', array(
		array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'bds_video_rest_list_projects', 'permission_callback' => 'bds_video_rest_permission' ),
		array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_create_project', 'permission_callback' => 'bds_video_rest_permission' ),
	) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})', array(
		array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'bds_video_rest_get_project', 'permission_callback' => 'bds_video_rest_permission' ),
		array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => 'bds_video_rest_update_project', 'permission_callback' => 'bds_video_rest_permission' ),
	) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})/render-quote', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_quote', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})/jobs', array( 'methods' => WP_REST_Server::READABLE, 'callback' => 'bds_video_rest_list_jobs', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})/versions', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_create_version', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})/renders', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_create_render', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/projects/(?P<id>[a-f0-9-]{36})/approve', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_approve_project', 'permission_callback' => 'bds_video_rest_permission' ) );
	register_rest_route( 'bds-video/v1', '/webhooks/render', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => 'bds_video_rest_webhook', 'permission_callback' => '__return_true' ) );
}
add_action( 'rest_api_init', 'bds_video_register_rest' );

function bds_video_admin_menu() {
	add_submenu_page( 'bds-studio-workspace', 'AI Video Studio', 'Video Studio', 'manage_options', 'bds-video-studio', 'bds_video_render_admin' );
}
add_action( 'admin_menu', 'bds_video_admin_menu', 20 );

function bds_video_render_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have access to Video Studio.', 'branddad' ), '', array( 'response' => 403 ) );
	}
	$flags = array(
		'Studio'    => bds_video_enabled(),
		'Rendering' => bds_video_flag( 'BDS_VIDEO_PROVIDER_ENABLED' ),
		'Uploads'   => bds_video_flag( 'BDS_VIDEO_UPLOADS_ENABLED' ),
		'Publishing'=> bds_video_flag( 'BDS_VIDEO_PUBLISHING_ENABLED' ),
	);
	?>
	<div class="wrap bds-video-studio"><h1>BrandDad AI Video Studio</h1>
		<p>Create an approval-ready video plan, organize its scenes, and review a cost estimate. Rendering and publishing remain locked until their providers, limits, and permissions are approved.</p>
		<table class="widefat striped" style="max-width:760px"><thead><tr><th>Subsystem</th><th>Status</th></tr></thead><tbody>
		<?php foreach ( $flags as $label => $active ) : ?><tr><td><?php echo esc_html( $label ); ?></td><td><strong><?php echo esc_html( $active ? 'Enabled' : 'Disabled' ); ?></strong></td></tr><?php endforeach; ?>
		</tbody></table>
		<?php if ( ! bds_video_enabled() ) : ?>
			<p><strong>The Video Studio workspace is safely locked.</strong> Enable only the Studio flag in a staging environment after database backup and validation. Provider, upload, and publishing flags can remain disabled.</p>
	</div>
			<?php return; ?>
		<?php endif; ?>

		<div id="bds-video-notice" class="notice" hidden><p></p></div>
		<p id="bds-video-usage" class="bds-video-usage" aria-live="polite">Loading monthly usage…</p>
		<section class="bds-video-panel" aria-labelledby="bds-video-create-heading">
			<h2 id="bds-video-create-heading">Create a video project</h2>
			<form id="bds-video-create-form">
				<div class="bds-video-grid">
					<label>Project title<input name="title" type="text" maxlength="160" required></label>
					<label>Content source<select name="sourceType" required><option value="prompt">Prompt</option><option value="original">Original idea</option><option value="website">Website</option><option value="blog">Blog or article</option><option value="product">Product or service</option><option value="directory">Directory listing</option><option value="smart_mix">Smart Mix</option></select></label>
					<label>Format<select name="aspectRatio"><option value="9:16">Vertical 9:16</option><option value="16:9">Horizontal 16:9</option><option value="1:1">Square 1:1</option><option value="4:5">Portrait 4:5</option></select></label>
					<label>Length<select name="durationSeconds"><option value="15">15 seconds</option><option value="30" selected>30 seconds</option><option value="60">60 seconds</option><option value="90">90 seconds</option></select></label>
					<label>Language code<input name="language" type="text" value="en" maxlength="5" pattern="[a-zA-Z]{2}(-[a-zA-Z]{2})?"></label>
				</div>
				<label>Verified source facts<textarea name="sourceSummary" maxlength="5000" rows="5" placeholder="Enter only facts the script may use. Never include passwords, API keys, or private customer data."></textarea></label>
				<p><button class="button button-primary" type="submit">Create project</button></p>
			</form>
		</section>

		<section class="bds-video-panel" aria-labelledby="bds-video-projects-heading">
			<h2 id="bds-video-projects-heading">Your video projects</h2>
			<p id="bds-video-empty" hidden>No video projects yet.</p>
			<div id="bds-video-projects" class="bds-video-projects" aria-live="polite"></div>
		</section>

		<template id="bds-video-project-template">
			<article class="bds-video-card">
				<div class="bds-video-card-head"><div><h3></h3><p class="bds-video-meta"></p></div><span class="bds-video-status"></span></div>
				<p class="bds-video-summary"></p>
				<div class="bds-video-actions"><button type="button" class="button bds-video-edit">Edit scenes</button><button type="button" class="button bds-video-quote">Review cost estimate</button><button type="button" class="button bds-video-jobs">Render history</button><button type="button" class="button button-primary bds-video-version" hidden>Create editable version</button></div>
				<form class="bds-video-editor" hidden>
					<h4>Scene plan</h4><div class="bds-video-scenes"></div>
					<p><button type="button" class="button bds-video-add-scene">Add scene</button> <button type="submit" class="button button-primary">Save scene plan</button> <button type="button" class="button bds-video-cancel">Cancel</button></p>
				</form>
				<div class="bds-video-quote-result" role="status" hidden></div>
				<div class="bds-video-job-history" role="status" hidden></div>
			</article>
		</template>

		<template id="bds-video-scene-template">
			<fieldset class="bds-video-scene"><legend>Scene <span></span></legend><div class="bds-video-grid">
				<label>Seconds<input data-field="durationSeconds" type="number" min="1" max="90" required></label>
				<label>On-screen text<textarea data-field="onScreenText" maxlength="500" rows="2"></textarea></label>
				<label>Narration<textarea data-field="narration" maxlength="1200" rows="3"></textarea></label>
				<label>Visual direction<textarea data-field="visualDirection" maxlength="1200" rows="3"></textarea></label>
			</div><button type="button" class="button-link-delete bds-video-remove-scene">Remove scene</button></fieldset>
		</template>

		<p class="description">No provider purchase, file upload, automatic publishing, or subscription-price change is performed by this staged module.</p>
	</div>
	<style>
		.bds-video-studio{max-width:1100px}.bds-video-usage{display:inline-block;margin:16px 0 0;padding:10px 12px;background:#f0f6fc;border-left:4px solid #2271b1}.bds-video-panel{margin-top:22px;padding:20px;background:#fff;border:1px solid #c3c4c7;border-radius:8px}.bds-video-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.bds-video-panel label{display:flex;flex-direction:column;gap:5px;font-weight:600}.bds-video-panel input,.bds-video-panel select,.bds-video-panel textarea{width:100%;max-width:none}.bds-video-projects{display:grid;gap:14px}.bds-video-card{padding:16px;border:1px solid #dcdcde;border-radius:8px}.bds-video-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.bds-video-card h3{margin:0}.bds-video-meta,.bds-video-summary{color:#50575e}.bds-video-status{padding:4px 8px;border-radius:999px;background:#f0f0f1;font-weight:600}.bds-video-actions{display:flex;flex-wrap:wrap;gap:8px}.bds-video-editor{margin-top:16px}.bds-video-scene{margin:12px 0;padding:14px;border:1px solid #c3c4c7}.bds-video-quote-result,.bds-video-job-history{margin-top:12px;padding:12px;background:#f0f6fc;border-left:4px solid #2271b1}.bds-video-job-history ul{margin:8px 0 0 18px}.bds-video-studio button:focus-visible,.bds-video-studio input:focus-visible,.bds-video-studio select:focus-visible,.bds-video-studio textarea:focus-visible{outline:3px solid #2271b1;outline-offset:2px}@media(max-width:782px){.bds-video-grid{grid-template-columns:1fr}.bds-video-card-head{display:block}.bds-video-status{display:inline-block;margin-top:8px}}
	</style>
	<script>
	(function(){
		'use strict';
		const root = document.querySelector('.bds-video-studio');
		if (!root) return;
		const apiBase = <?php echo wp_json_encode( rest_url( 'bds-video/v1' ) ); ?>;
		const nonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;
		const list = document.getElementById('bds-video-projects');
		const empty = document.getElementById('bds-video-empty');
		const notice = document.getElementById('bds-video-notice');
		const projectTemplate = document.getElementById('bds-video-project-template');
		const sceneTemplate = document.getElementById('bds-video-scene-template');

		function message(text, error){ notice.hidden=false; notice.className='notice '+(error?'notice-error':'notice-success'); notice.querySelector('p').textContent=text; notice.scrollIntoView({behavior:'smooth',block:'nearest'}); }
		async function request(path, options={}){
			const response = await fetch(apiBase+path,{credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},...options});
			let body={}; try{ body=await response.json(); }catch(e){ body={message:'The server returned an unreadable response.'}; }
			if(!response.ok) throw new Error(body.message||'The request could not be completed.');
			return body;
		}
		function statusLabel(value){ return String(value||'draft').replaceAll('_',' '); }
		function addScene(container, scene={}){
			if(container.children.length>=50){ message('A project can contain up to 50 scenes.',true); return; }
			const node=sceneTemplate.content.firstElementChild.cloneNode(true);
			node.querySelector('legend span').textContent=String(container.children.length+1);
			for(const input of node.querySelectorAll('[data-field]')) input.value=scene[input.dataset.field]??(input.dataset.field==='durationSeconds'?5:'');
			node.querySelector('.bds-video-remove-scene').addEventListener('click',()=>{node.remove(); renumberScenes(container);});
			container.appendChild(node);
		}
		function renumberScenes(container){ [...container.children].forEach((item,index)=>item.querySelector('legend span').textContent=String(index+1)); }
		function collectScenes(container){ return [...container.children].map(item=>({durationSeconds:Number(item.querySelector('[data-field="durationSeconds"]').value),narration:item.querySelector('[data-field="narration"]').value,onScreenText:item.querySelector('[data-field="onScreenText"]').value,visualDirection:item.querySelector('[data-field="visualDirection"]').value})); }
		function renderProject(project){
			const card=projectTemplate.content.firstElementChild.cloneNode(true);
			card.dataset.projectId=project.id;
			card.querySelector('h3').textContent=project.title;
			card.querySelector('.bds-video-meta').textContent=project.durationSeconds+' seconds · '+project.aspectRatio+' · '+project.language+' · version '+project.version;
			card.querySelector('.bds-video-status').textContent=statusLabel(project.status);
			card.querySelector('.bds-video-summary').textContent=project.sourceSummary||'No verified source facts added yet.';
			card.querySelector('.bds-video-edit').hidden=project.status==='approved';
			card.querySelector('.bds-video-version').hidden=project.status!=='approved';
			const editor=card.querySelector('.bds-video-editor'); const scenes=card.querySelector('.bds-video-scenes');
			card.querySelector('.bds-video-edit').addEventListener('click',()=>{scenes.replaceChildren(); (project.scenes||[]).forEach(scene=>addScene(scenes,scene)); if(!scenes.children.length)addScene(scenes); editor.hidden=false;});
			card.querySelector('.bds-video-add-scene').addEventListener('click',()=>addScene(scenes));
			card.querySelector('.bds-video-cancel').addEventListener('click',()=>{editor.hidden=true;});
			editor.addEventListener('submit',async(event)=>{event.preventDefault(); const button=editor.querySelector('[type="submit"]'); button.disabled=true; try{await request('/projects/'+encodeURIComponent(project.id),{method:'PATCH',body:JSON.stringify({scenes:collectScenes(scenes)})}); message('Scene plan saved.',false); await loadProjects();}catch(error){message(error.message,true);}finally{button.disabled=false;}});
			card.querySelector('.bds-video-quote').addEventListener('click',async(event)=>{event.currentTarget.disabled=true; try{const quote=await request('/projects/'+encodeURIComponent(project.id)+'/render-quote',{method:'POST',body:'{}'}); const output=card.querySelector('.bds-video-quote-result'); output.textContent='Estimated provider cost: '+new Intl.NumberFormat(undefined,{style:'currency',currency:quote.currency}).format(quote.estimatedCost)+' · Reserved credits: '+quote.reservedCredits+' · Premium AI footage not included.'; output.hidden=false;}catch(error){message(error.message,true);}finally{event.currentTarget.disabled=false;}});
			card.querySelector('.bds-video-jobs').addEventListener('click',async(event)=>{event.currentTarget.disabled=true; try{const data=await request('/projects/'+encodeURIComponent(project.id)+'/jobs'); const output=card.querySelector('.bds-video-job-history'); output.replaceChildren(); const heading=document.createElement('strong'); heading.textContent='Render history'; output.appendChild(heading); if(!data.jobs.length){output.appendChild(document.createTextNode(' — no render attempts yet.'));}else{const items=document.createElement('ul'); for(const job of data.jobs){const item=document.createElement('li'); item.textContent=statusLabel(job.status)+' · '+job.reservedCredits+' credits · '+new Date(job.createdAt).toLocaleString()+(job.errorCode?' · '+statusLabel(job.errorCode):''); items.appendChild(item);} output.appendChild(items);} output.hidden=false;}catch(error){message(error.message,true);}finally{event.currentTarget.disabled=false;}});
			card.querySelector('.bds-video-version').addEventListener('click',async(event)=>{event.currentTarget.disabled=true; try{await request('/projects/'+encodeURIComponent(project.id)+'/versions',{method:'POST',body:'{}'}); message('A new editable version was created. The approved version remains unchanged.',false); await loadProjects();}catch(error){message(error.message,true);}finally{event.currentTarget.disabled=false;}});
			return card;
		}
		async function loadProjects(){ try{const data=await request('/projects?limit=50'); list.replaceChildren(...data.projects.map(renderProject)); empty.hidden=data.projects.length>0;}catch(error){message(error.message,true);} }
		async function loadUsage(){ try{const usage=await request('/usage'); document.getElementById('bds-video-usage').textContent='Monthly video credits: '+usage.usedCredits+' used · '+usage.remainingCredits+' remaining · '+usage.monthlyCap+' safety cap';}catch(error){message(error.message,true);} }
		document.getElementById('bds-video-create-form').addEventListener('submit',async(event)=>{event.preventDefault(); const form=event.currentTarget; const button=form.querySelector('[type="submit"]'); const data=Object.fromEntries(new FormData(form)); data.durationSeconds=Number(data.durationSeconds); data.language=String(data.language).toLowerCase(); button.disabled=true; try{await request('/projects',{method:'POST',body:JSON.stringify(data)}); form.reset(); form.elements.language.value='en'; message('Video project created.',false); await loadProjects();}catch(error){message(error.message,true);}finally{button.disabled=false;}});
		loadProjects(); loadUsage();
	})();
	</script>
	<?php
}
