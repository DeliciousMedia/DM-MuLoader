<?php
/**
 * All functionality goes in here, included by dm-muloader.php which is placed directly in the mu-plugins folder.
 *
 * @package dm-muloader
 */

/**
 * Find any WordPress plugins in subfolders of the the mu-plugins directory.
 *
 * @return array List of plugin file names.
 */
function dmmuloader_find_muplugins() {
	// We want to use get_plugins, but plugin.php isn't always included.
	if ( ! function_exists( 'get_plugins' ) ) {
		require ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$mu_plugins = [];

	foreach ( get_plugins( '/../mu-plugins/' ) as $muplugin_file => $plugin_data ) {
		// Files within the mu-plugins folder will already be loaded, so skip them.
		if ( '.' === dirname( $muplugin_file ) ) {
			continue;
		}

		// No need to include this file.
		if ( 'dm-muloader' === dirname( $muplugin_file ) ) {
			continue;
		}

		$mu_plugins[] = $muplugin_file;

	}

	// Allow the site-config plugin to be loaded before all other mu-plugins; this is desirable as
	// it allows us to configure other mu-plugins, to avoid issues with previous sites we won't make this a default.
	if ( defined( 'DMMULOADER_SITE_CONFIG_FIRST' ) && true == DMMULOADER_SITE_CONFIG_FIRST ) {

		// If we have a site-config plugin, load it first.
		$site_config_key = array_search( 'site-config/site-config.php', $mu_plugins, true );

		if ( false !== $site_config_key ) {
			$mu_plugins = array_merge( [ 0 => 'site-config/site-config.php' ], $mu_plugins );
		}
	}

	return $mu_plugins;

}

/**
 * Check if the file used to load this plugin in the root muplugins folder is up-to-date,
 * and update it if required.
 *
 * @return void
 */
function dmmuloader_update_loader() {

	// No need to update if we have a transient set; as we flush transients on deployment.
	$updated = get_transient( 'dmmuloader_updated' );
	if ( 'yes' === $updated ) {
		return;
	}

	$plugin_folder_file = untrailingslashit( WPMU_PLUGIN_DIR ) . '/dm-muloader/dm-muloader.php';
	$muplugin_file   = untrailingslashit( WPMU_PLUGIN_DIR ) . '/dm-muloader.php';

	// If the file is already there, and it matches the version in this directory we don't need to do anything.
	if ( file_exists( $muplugin_file ) ) {
		$installed_plugin_data = get_plugin_data( $muplugin_file, false, false );
		$this_plugin_data = get_plugin_data( $plugin_folder_file, false, false );
		if ( $installed_plugin_data['Version'] === $this_plugin_data['Version'] ) {
			set_transient( 'dmmuloader_updated', 'yes' );
			return;
		}
	}

	@copy( $plugin_folder_file, $muplugin_file );
	set_transient( 'dmmuloader_updated', 'yes' );
}
add_action( 'admin_init', 'dmmuloader_update_loader', 1, 0 );


/**
 * Get a list of mu-plugins from transients, if possible - otherwise build & cache it.
 *
 * @param  boolean $force_rebuild Force rebuilding the cache.
 *
 * @return array                  List of mu-plugin file names.
 */
function dmmuloader_get_muplugins( $force_rebuild = false ) {

	$mu_plugins = [];

	// Attempt to get a cached list of mu-plugins.
	$mu_plugins = get_site_transient( 'dmmuloader_muplugins' );

	// If we get a list of mu-plugins back, then check that they are readable still.
	if ( $mu_plugins ) {
		foreach ( $mu_plugins as $muplugin_file ) {
			if ( ! is_readable( WPMU_PLUGIN_DIR . '/' . $muplugin_file ) ) {
				$mu_plugins = [];
				break;
			}
		}
	}

	// If there's no cached list, it was invalidate above or or we've forced a reload get the list and cache it.
	if ( ! $mu_plugins || $force_rebuild ) {
		$mu_plugins = dmmuloader_find_muplugins();
		set_site_transient( 'dmmuloader_muplugins', $mu_plugins );
	}

	return $mu_plugins;

}

/**
 * Actually include the mu-plugins.
 */
add_action(
	'muplugins_loaded',
	function() {

		$mu_plugins = dmmuloader_get_muplugins();

		// No plugins?
		if ( ! $mu_plugins ) {
			return;
		}

		// Loop through and include each mu-plugin.
		foreach ( $mu_plugins as $muplugin_file ) {
			require_once WPMU_PLUGIN_DIR . '/' . $muplugin_file;
		}

	}
);

/**
 * Show the plugins on the Must-Use 'tab' of the plugins page.
 */
add_action(
	'pre_current_active_plugins',
	function () {
		global $plugins, $wp_list_table;

		// Get a list of mu-plugins, force cache invalidation so we know it's up to date.
		$mu_plugins = dmmuloader_get_muplugins( true );

		// Add our own mu-plugins to the page.
		foreach ( $mu_plugins as $plugin_file ) {

			$plugin_data = get_plugin_data( WPMU_PLUGIN_DIR . "/$plugin_file", false, false );

			if ( empty( $plugin_data['Name'] ) ) {
				$plugin_data['Name'] = $plugin_file;
			}
			$plugins['mustuse'][ $plugin_file ] = $plugin_data;
		}

		$GLOBALS['totals']['mustuse'] = count( $plugins['mustuse'] );

		if ( 'mustuse' !== $GLOBALS['status'] ) {
			return;
		}

		// Reset the list table's data.
		$wp_list_table->items = $plugins['mustuse'];
		foreach ( $wp_list_table->items as $plugin_file => $plugin_data ) {
			$wp_list_table->items[ $plugin_file ] = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, false, true );
		}

		$total_this_page = $GLOBALS['totals']['mustuse'];
		if ( $GLOBALS['orderby'] ) {
			uasort( $wp_list_table->items, [ $wp_list_table, '_order_callback' ] );
		}

		$plugins_per_page = $total_this_page;
		$wp_list_table->set_pagination_args(
			[
				'total_items' => $total_this_page,
				'per_page'    => $plugins_per_page,
			]
		);
	}
);

/**
 * Show the plugin filename in the network admin.
 */
add_action(
	'plugin_action_links',
	function ( $actions, $plugin_file, $plugin_data, $context ) {

		// Get a list of mu-plugins.
		$mu_plugins = dmmuloader_get_muplugins( false );

		if ( 'mustuse' !== $context || ! in_array( $plugin_file, $mu_plugins ) ) {
			return $actions;
		}
		$actions[] = sprintf( '<span style="color:#333">File: <code>%s</code></span>', $plugin_file );

		if ( defined( 'DMMULOADER_SITE_CONFIG_FIRST' ) && true == DMMULOADER_SITE_CONFIG_FIRST && 'site-config/site-config.php' === $plugin_file ) {
			$actions[] = sprintf( ' Loaded First.', $plugin_file );
		}

		return $actions;
	},
	10,
	4
);
