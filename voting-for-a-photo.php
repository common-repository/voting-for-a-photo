<?php

use VotingPhoto\VotingPhotoPlugin;

/**
 *
 * Plugin Name:       Voting for a Photo
 * Description:       Adding a photo vote to the WordPress Gallery
 * Version:           1.2
 * Author:            Processby
 * Author URI:        https://processby.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       voting-for-photo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

call_user_func( function () {

	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	$main = new VotingPhotoPlugin( __FILE__ );

	register_activation_hook( __FILE__, [ $main, 'activate' ] );

	register_deactivation_hook( __FILE__, [ $main, 'deactivate' ] );

	register_uninstall_hook( __FILE__, [ VotingPhotoPlugin::class, 'uninstall' ] );

	$main->run();
} );