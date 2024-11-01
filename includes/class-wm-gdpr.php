<?php

	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition that includes attributes and functions used across both the
	 * public-facing side of the site and the admin area.
	 *
	 * @link       https://webme.it
	 * @since      1.0.0
	 *
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/includes
	 */

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/includes
	 * @author     webme.it <info@webme.it>
	 */
	class Wm_Gdpr {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Wm_Gdpr_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $plugin_name The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $version The current version of the plugin.
		 */
		protected $version;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct () {
			if ( defined ( 'WM_GDPR_VERSION' ) ) {
				$this->version = WM_GDPR_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = 'webme-cookie-privacy';

			$this->load_dependencies ();
			$this->set_locale ();
			$this->define_admin_hooks ();
			$this->define_public_hooks ();

		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Wm_Gdpr_Loader. Orchestrates the hooks of the plugin.
		 * - Wm_Gdpr_i18n. Defines internationalization functionality.
		 * - Wm_Gdpr_Admin. Defines all hooks for the admin area.
		 * - Wm_Gdpr_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies () {
			
			/**
			 * The formbuiilder functions for cleaner forms
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-acme-form-builder.php';
			
			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path ( dirname ( __FILE__ ) ) . 'includes/class-wm-gdpr-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path ( dirname ( __FILE__ ) ) . 'includes/class-wm-gdpr-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path ( dirname ( __FILE__ ) ) . 'admin/class-wm-gdpr-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path ( dirname ( __FILE__ ) ) . 'public/class-wm-gdpr-public.php';

			$this->loader = new Wm_Gdpr_Loader();

		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Wm_Gdpr_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale () {

			$plugin_i18n = new Wm_Gdpr_i18n();

			$this->loader->add_action ( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks () {

			$plugin_admin = new Wm_Gdpr_Admin( $this->get_plugin_name (), $this->get_version () );

			$this->loader->add_action ( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action ( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );


			/**
			 * Add menu item
			 */
			$this->loader->add_action ( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

			/**
			 * Add Settings link to the plugin
			 */
			$plugin_basename = plugin_basename ( plugin_dir_path ( __DIR__ ) . $this->plugin_name . '.php' );
			$this->loader->add_filter ( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

			/**
			 * Save/Update plugin options
			 */
			$this->loader->add_action ( 'admin_init', $plugin_admin, 'options_update' );
			$this->loader->add_action( 'shutdown', $plugin_admin, 'update_frontend_data' );

			/**
			 * Add Plugin Filters
			 */
			//$this->loader->add_filter ( 'webme_check_remote', $plugin_admin, 'check_remote_api', 10, 0 );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks () {

			if( is_admin() && !wp_doing_ajax() ) {
				return;
			}
			$plugin_public = new Wm_Gdpr_Public( $this->get_plugin_name (), $this->get_version () );

			$this->loader->add_action ( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			//$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts', PHP_INT_MIN );

			/**
			 * Parse scripts in the head section
			 */
			$this->loader->add_action( 'init', $plugin_public, 'eval_iub' );
			$this->loader->add_action ( 'wp_head', $plugin_public, 'parse_meta_tag', PHP_INT_MIN );
			//$this->loader->add_action ( 'wp_head', $plugin_public, 'parse_cookie_scripts', 1 );
			
			$this->loader->add_action ( 'init', $plugin_public, 'cookie_policy_2022', PHP_INT_MIN );
			/**
			 * Parse Iubenda privacy in footer
			 */
			$this->loader->add_action ( 'wp_footer', $plugin_public, 'parse_buttons', 90 );
			
			$this->loader->add_action( 'template_redirect', $plugin_public, 'output_start' );
			$this->loader->add_action( 'shutdown', $plugin_public, 'output_end' );
			
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run () {
			$this->loader->run ();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name () {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Wm_Gdpr_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader () {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
		public function get_version () {
			return $this->version;
		}

	}
