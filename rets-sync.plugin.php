<?php
/**
 * Plugin Name:			RETS Sync
 * Version:				1.0.1
 * Description: 		Sync Your RETS data.
 * Author:				Brian DiChiara
 * Author URI:			https://briandichiara.com
 * Text Domain:			rets-sync
 */

if( ! defined( 'ABSPATH' ) )
	exit;

define( 'RETSSYNC_VERSION', '1.0.1' );
define( 'RETSSYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'RETSSYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'RETSSYNC_META_PREFIX', '_listing_' );

require_once( RETSSYNC_PATH . 'lib/wp-background-processing/wp-background-processing.php' );

require_once( RETSSYNC_PATH . 'classes/rets-sync-db.class.php' );
require_once( RETSSYNC_PATH . 'classes/rets-cloud-adapter.class.php' );

/** Sync Files (Jobs) */
require_once( RETSSYNC_PATH . 'classes/rets-sync-worker.class.php' );
require_once( RETSSYNC_PATH . 'classes/rets-sync-listing-job.class.php' );
require_once( RETSSYNC_PATH . 'classes/rets-sync-listing-images-job.class.php' );
require_once( RETSSYNC_PATH . 'classes/rets-sync-listing-details-job.class.php' );

require_once( RETSSYNC_PATH . 'classes/rets-sync.class.php' );

// Add WP CLI commands
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( RETSSYNC_PATH . 'lib/wp-background-processing/classes/cli-command.php' );

	WP_CLI::add_command( 'queue', 'CLI_Command' );
}

if ( ! function_exists( 'wp_queue' ) ) {
	function wp_queue( WP_Job $job, $delay = 0 ) {
		$queue = WP_Queue::get_instance();

		$queue->push( $job, $delay );

		do_action( 'wp_queue_job_pushed', $job );
	}
}

// Instantiate HTTP queue worker
new WP_Http_Worker();

// Instantiate RETS_Sync
$rets_sync = new RETS_Sync();
$rets_sync_db;
