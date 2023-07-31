<?php
/**
 * Plugin Name: DM MuLoader
 * Plugin URI: https://github.com/DeliciousMedia/DM-MuLoader
 * Description: Automatically load must use plugins from subfolders.
 * Version: 2.2.0
 * Author: Delicious Media Limited
 * Author URI: https://www.deliciousmedia.co.uk
 * Text Domain: dm-muloader
 * License: GPLv3 or later
 * Contributors: davepullig
 *
 * Copy this file to your mu-plugins folder.
 *
 * @package dm-muloader
 **/


 // Don't run if we're installing WordPress.
if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING === true ) {
	return;
}

// Include the main plugin file if it exists, otherwise fail.
$mumuload_plugin_file = WPMU_PLUGIN_DIR . '/dm-muloader/dm-muloader-plugin.php';

if ( is_file( $mumuload_plugin_file ) ) {
	require_once $mumuload_plugin_file;
} else {
	die( 'dm-muloader plugin file is missing.' );
}

unset( $mumuload_plugin_file );
