<?php
/**
 * Plugin Name: BDS Hard Conn Guard
 * Version: 1.1.0
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('DISABLE_WP_CRON')) {
    define('DISABLE_WP_CRON', true);
}
if (!defined('EMPTY_TRASH_DAYS')) {
    define('EMPTY_TRASH_DAYS', 30);
}

// Cron and CLI may drain the backlog without launching parallel HTTP workers.
$bds_scheduled_worker = (defined('DOING_CRON') && DOING_CRON)
    || (defined('WP_CLI') && WP_CLI);
add_filter('action_scheduler_allow_async_request_runner', '__return_false', 99);
add_filter('action_scheduler_queue_runner_batch_size', static function () use ($bds_scheduled_worker) {
    return $bds_scheduled_worker ? 5 : 0;
}, 99);
add_filter('action_scheduler_queue_runner_concurrent_batches', static function () use ($bds_scheduled_worker) {
    return $bds_scheduled_worker ? 1 : 0;
}, 99);
add_filter('action_scheduler_queue_runner_time_limit', static function () {
    return 20;
}, 99);

if (!$bds_scheduled_worker) {
    add_filter('pre_http_request', static function ($pre, $args, $url) {
        if (is_string($url) && strpos($url, 'action_scheduler') !== false) {
            return new WP_Error('bds_block', 'blocked');
        }
        return $pre;
    }, 1, 3);
    add_action('plugins_loaded', static function () {
        remove_all_actions('action_scheduler_run_queue');
        remove_all_actions('action_scheduler_run_queue_ranged_cron');
    }, 1);
}
