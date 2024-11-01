<?php

	/**
	 * The plugin bootstrap file
	 *
	 * This file is read by WordPress to generate the plugin information in the plugin
	 * admin area. This file also includes all of the dependencies used by the plugin,
	 * registers the activation and deactivation functions, and defines a function
	 * that starts the plugin.
	 *
	 * @link              https://webme.it
	 * @since             1.0.0
	 * @package           Wm_Gdpr
	 *
	 * @wordpress-plugin
	 * Plugin Name:       WebMe Cookie & Privacy
	 * Plugin URI:        webme-gdpr-plugin
	 * Description:       WebMe-GDPR integrates WebMe cookie consent bar in your site. This plugin is European GDPR compliant
	 * Version:           2.0.20
	 * Author:            webme.it
	 * Author URI:        https://webme.it
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 * Text Domain:       wm-gdpr
	 * Domain Path:       /languages
	 */

// If this file is called directly, abort.
	if ( ! defined ( 'WPINC' ) ) {
		die;
	}

	/**
	 * Currently plugin version.
	 * Start at version 1.0.0 and use SemVer - https://semver.org
	 * Rename this for your plugin and update it as you release new versions.
	 */
	define( 'WM_GDPR_VERSION', '2.0.20' );

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-wm-gdpr-activator.php
	 */
	function activate_wm_gdpr () {
		require_once plugin_dir_path ( __FILE__ ) . 'includes/class-wm-gdpr-activator.php';
		Wm_Gdpr_Activator::activate ();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-wm-gdpr-deactivator.php
	 */
	function deactivate_wm_gdpr () {
		require_once plugin_dir_path ( __FILE__ ) . 'includes/class-wm-gdpr-deactivator.php';
		Wm_Gdpr_Deactivator::deactivate ();
	}

	register_activation_hook ( __FILE__, 'activate_wm_gdpr' );
	register_deactivation_hook ( __FILE__, 'deactivate_wm_gdpr' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path ( __FILE__ ) . 'includes/class-wm-gdpr.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_wm_gdpr () {

		$plugin = new Wm_Gdpr();
		$plugin->run ();

	}
	
	function run_iubenda_class () {
		require plugin_dir_path ( __FILE__ ) . 'includes/iubenda-cookie-class/iubenda.class.php';
		if ( !iubendaParser::consent_given() && !iubendaParser::bot_detected() ) {
			return true;
		}
		return false;
	}
	
	$wmIub = run_iubenda_class();
	
	run_wm_gdpr ();
