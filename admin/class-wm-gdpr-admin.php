<?php

	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * @link       https://webme.it
	 * @since      1.0.0
	 *
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/admin
	 */

	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the admin-specific stylesheet and JavaScript.
	 *
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/admin
	 * @author     webme.it <info@webme.it>
	 */
	class Wm_Gdpr_Admin {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $plugin_name The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string $version The current version of this plugin.
		 */
		private $version;
		private $api;
		private $bypass_transient;
		
		public $wm_gdpr;
		public $api_qstr;
		public $api_uri;
		public $shop_url;

		public $form_factory;
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 *
		 * @param      string $plugin_name The name of this plugin.
		 * @param      string $version     The version of this plugin.
		 */
		public function __construct ( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;
			$this->wm_gdpr     = get_option ( $this->plugin_name );
			//do_action( 'qm/debug', ['init options', $this->wm_gdpr] );
			$site_url = get_option ( 'siteurl' );
			$domain_name = preg_replace ( '/^https?:(\/\/)/i', '', $site_url );
			$domain_name = preg_replace ( '/\/.*/', '', $domain_name );
			$this->api_qstr = array (
				'f'           => 'api',
				'action'      => 'privacy',
				'domain_name' => $domain_name
			);
			$this->api_uri  = 'https://cache.areaclienti.webme.it/go.php?f=api&action=privacy&domain_name=' . $domain_name;
			$this->shop_url = 'https://shop.webme.it/shop/prodotto/1028378?field_Dominio=' . $domain_name;
			//https://shop.webme.it/shop/grid/34048_PRIVACY+%2B+COOKIE
			$this->form_factory = new WmGdprForms( $this->plugin_name );
			$this->form_factory->presets = $this->wm_gdpr;
		}

		
		
		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles () {
			
			wp_enqueue_style ( $this->plugin_name, plugin_dir_url ( __FILE__ ) . 'css/wm-gdpr-admin.css', array (), $this->version, 'all' );
			//wp_enqueue_style ( $this->plugin_name, plugin_dir_url ( __FILE__ ) . 'css/alpha-color-picker.css', array (), $this->version, 'all' );
			wp_enqueue_style( 'wp-color-picker' );

		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts () {

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wm-gdpr-admin.js', array( 'jquery','wp-color-picker' ), $this->version, false );
			//wp_enqueue_script( 'wp-color-picker-alpha', plugin_dir_url( __FILE__ ) . 'js/alpha-color-picker.js', array( 'jquery', 'wp-color-picker' ), '1.0.0', true );
		}

		/**
		 * Register the administration menu for this plugin into the WordPress Dashboard menu.
		 *
		 * @since    1.1.0
		 */
		public function add_plugin_admin_menu () {

			$parent = 'webme_plugin_panel';

			$this->create_webme_admin_menu ();
			add_submenu_page ( $parent, 'WebMe - GDPR Cookie & Privacy', 'Cookie & Privacy', 'manage_options', $this->plugin_name, array (
				$this,
				'display_plugin_setup_page'
			) );
			remove_submenu_page ( $parent, 'webme_plugin_panel' );

		}

		/**
		 * If not exists, create a WebMe Menu for all plugins
		 *
		 * @since 1.1.0
		 */
		public function create_webme_admin_menu () {
			global $admin_page_hooks;
			if ( ! isset( $admin_page_hooks['webme_plugin_panel'] ) ) {
				add_menu_page ( 'webme_plugin_panel', 'WebMe', 'manage_options', 'webme_plugin_panel', null, 'dashicons-editor-paste-word', 81 );
			}

			return false;
		}

		/**
		 * Add settings action link to the plugins page.
		 *
		 * @since    1.0.0
		 */

		public function add_action_links ( $links ) {
			/*
			*  Documentation : https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
			*/
			$settings_link = array (
				'<a href="' . admin_url ( 'admin.php?page=' . $this->plugin_name ) . '">' . __ ( 'Settings', $this->plugin_name ) . '</a>',
			);

			return array_merge ( $settings_link, $links );

		}


		/**
		 * Render the settings page for this plugin.
		 *
		 * @since    1.0.0
		 */
		public function display_plugin_setup_page () {
			add_action( 'wm_gdpr__form_parser', [ $this, 'form_parser' ]);
			include_once ( 'partials/wm-gdpr-admin-display.php' );
		}
		
		/**
		 * Parse  Form Fields
		 */
		public function form_parser ($form_name) {
			add_action( $this->plugin_name . '-status', [ $this, 'echo_status' ] );
			echo implode( "\n", $this->form_factory->wrapper( $this->main_settings() ) );
			return;
			$fields = array ();
			$class = array ();
			// Passes the form array to the Acme Form Builder functions
			foreach ( $this->$form_name() as $thisForm_fields ) {
				if ( $thisForm_fields['filter'] == 'wm__fbuild__hidden' ) {
					$hidden[] = apply_filters( $thisForm_fields['filter'], $thisForm_fields );
				}
				else {
					$class[] = isset( $thisForm_fields['class'] ) ? $thisForm_fields['class'] : array ();
					$fields[] = apply_filters( $thisForm_fields['filter'], $thisForm_fields );
				}
			}
			// Prepends hidden fields
			foreach ( $hidden as $field ) {
				print( $field );
			}
			// Wraps form filed in html tags
			foreach ( $fields as $index => $field ) {
				printf( '<p class="form-field %s">%s</p>', implode( ' ', $class[$index] ), $field );
			}
		}
		
		/**
		 * main_settings fields
		 * SINCE 1.3.0
		 */
		public function main_settings () {
			$this->api = $this->check_remote_api();
			//do_action( 'qm/debug', $this->api );
			$fieldset = array ();
			$fields = [
				'page_id'                => __FUNCTION__,
				'installation_id'        => get_current_blog_id(),
				'version'                => $this->version,
				'status'                 => isset( $this->api['status'] ) ? $this->api['status'] : '',  //is privacy active
				'id_iubenda'             => isset( $this->api['id_iubenda'] ) ? $this->api['id_iubenda'] : '',
				'cookie_status'          => isset( $this->api['cookie_status'] ) ? $this->api['cookie_status'] : '',
				'cookie_id_iubenda'      => isset( $this->api['cookie_id_iubenda'] ) ? $this->api['cookie_id_iubenda'] : '',
				'cookie_id_iubenda_site' => isset( $this->api['cookie_id_iubenda_site'] ) ? $this->api['cookie_id_iubenda_site'] : '',
			];
			foreach ( $fields as $name => $value ) {
				$fieldset[$name] = [
					'filter'   => 'hidden',
					'name'     => [ $name ],
					'value'    => $value,
					'basename' => $this->plugin_name,
					'validate' => 'sanitize_text_field'
				];
			}
			$name = 'language';
			$fieldset[$name] = array (
				'filter'   => 'select',
				'label'    => __( 'Cookie/Privacy default Language', $this->plugin_name ),
				'name'     => [ $name ],
				'value'    => isset( $this->api[$name] ) ? $this->api[$name] : 'it',
				'options'  => isset( $this->api['available_languages'] ) && !empty( $this->api['available_languages'] ) ? $this->api['available_languages'] : [ 'it' ],
				'validate' => 'sanitize_text_field',
				'basename' => $this->plugin_name
			);
			
			return $fieldset;
		}
		
		
		/**
		 *
		 * some string validation from form data
		 *
		 * @since    1.0.0
		 *
		 * @param   array $input POST data from WP
		 *
		 * @return array
		 **/
		public function validate ( $input ) {
			$valid = $this->wm_gdpr;
			if ( is_array( $input ) && !empty( $input['page_id'] ) ) {
				$collections = call_user_func(  [ $this, $input['page_id'] ] );
				foreach ( $collections as $k => $collection ) {
					$key = is_array( $collection['name'] ) ? $collection['name'][0] : $collection['name'];
					$value = isset( $input[$key] ) ? $input[$key] : null;
					if ( $k !== 'page_id' ) {
						if ( $k == 'delete_transient' ) {
							delete_transient( $this->plugin_name );
						}
						else {
							if ( isset( $collection['validate'] ) ) {
								$valid[$key] = call_user_func( $collection['validate'], $value );
							}
						}
					}
				}
				// All checkboxes inputs
				$valid   = array ();
				$message = __ ( 'Error. No data has been saved.', $this->plugin_name );
				$type    = 'error';
				if ( null != $input ) {
					$message = __ ( 'Settings succesfully saved.', $this->plugin_name );
					$type    = 'updated';
					//Cleanup
					foreach ( $input as $k => $val ) {
						switch ( $k ) {
							case "analytics":
							case "policyurl":
							case "background":
							case "foreground":
							case "language":
								$valid[$k] = sanitize_text_field ( $val );
								break;
							default:
								$valid[$k] = absint ( $val );
								break;
						}
					}
				}
				// add_settings_error( $setting, $code, $message, $type )
				add_settings_error($this->plugin_name, sanitize_title($this->plugin_name), $message, $type);
			}


			return $valid;
		}

		/**
		 *
		 * let's save our options
		 *
		 * @since    1.0.0
		 **/
		public function options_update () {
			$this->form_factory->collections = $this->main_settings();
			register_setting( $this->plugin_name,
				$this->plugin_name,
				[
					'type'              => 'array',
					'sanitize_callback' => [ $this->form_factory, 'validate' ]
				]
			);
		}
		
		
		public function update_frontend_data () {
			if ( !is_admin() ) {
				//$this->bypass_transient = true;
				$this->check_remote_api();
			}
		}
		/**
		 * Check website registration into APIs
		 *
		 * @return array
		 *
		 * @since 1.1.4
		 */
		public function check_remote_api ( ) {
			$options = [];
			if ( !isset( $this->wm_gdpr['version'] ) || $this->wm_gdpr['version'] != $this->version ) {
				$this->bypass_transient = true;
				$options['version'] = $this->version;
			}
			$ar_info = [];
			$ar_api = $this->api_enquire();
			if ( !empty( $ar_api ) && ( isset( $ar_api['status'] ) && $ar_api['status'] == 'ok' ) ) {
				$ar_info['id_iubenda'] = sanitize_text_field( $ar_api['id_iubenda'] );
			}
			if ( isset( $ar_api['cookie_status'] ) && $ar_api['cookie_status'] == 'ok' ) {
				$ar_info['cookie_id_iubenda'] = $ar_api['cookie_id_iubenda'];
				$ar_info['cookie_id_iubenda_site'] = $ar_api['cookie_id_iubenda_site'];
				$ar_info['available_languages'] = $ar_api['available_languages'];
				$ar_info['ids'] = $ar_api['available_languages_ids'];
			}
			
			foreach ( $ar_info as $k => $v ) {
				$options[$k] = $v;
			}
			
			foreach ( $this->wm_gdpr as $k => $v ) {
				if ( !isset( $options[$k] ) ) {
					$options[$k] = $v;
				}
			}
			
			// write new options to wp_options
			if ( is_admin() ) {
				update_option ( $this->plugin_name, $options );
			}
			
			return array_merge( $ar_api, $ar_info );
		}
		
		/**
		 * Check remote api and set transient
		 *
		 *
		 * @return array|mixed|object
		 */
		public function api_enquire () {
			$check_transient = true;
			
			if ( $this->check_permissions() ) {
				$check_transient = false;
			}
			
			if ( $check_transient && $transient = get_transient( $this->plugin_name ) ) {
				return $transient;
			}
			// let's try via https
			$url = add_query_arg( $this->get_api_qstr(), $this->get_api_url() );
			$raw_response = wp_remote_get ( $url, [] );
			if ( is_wp_error( $raw_response ) ) {
				// second try with http
				$unsecure_url = str_replace( 'https://', 'http://', $url );
				$raw_response = wp_remote_get( $unsecure_url, [] );
				if ( is_wp_error( $raw_response ) ) {
					// none of the above working, let's take advantage of manual script
					$raw_response = [];
					$raw_response['body'] = apply_filters( 'wm-gdpr-manual-code', json_encode( ['use_manual_script'] ) );
				}
			}
			$ar_api = [];
			if ( isset( $raw_response['body'] ) && !empty( $raw_response['body'] ) ) {
				$ar_api = json_decode( $raw_response['body'], true );
			}
			
			if ( !empty( $ar_api ) ) {
				delete_transient( $this->plugin_name );
				set_transient( $this->plugin_name, $ar_api, HOUR_IN_SECONDS );
			}
			
			return $ar_api;
		}
		
		/**
		 * Check if current user can edit options and if the current screen is plugin page under wp-admin
		 *
		 * @return bool
		 */
		public function check_permissions () {
			if ( $this->bypass_transient ) {
				return true;
			}
			if ( !is_admin() ) {
				return false;
			}
			
			return is_admin() && ( is_object( get_current_screen() ) && get_current_screen()->id == 'webme_page_webme-cookie-privacy' );
		}
		
		
		public function date_check ( $timestamp = null ) {
			if ( empty( $timestamp ) ) {
				$timestamp = time();
			}
			
			return apply_filters( 'wm_date_check', date( 'Ymd', $timestamp ) );
		}
		
		public function get_api_url ($url = null) {
			if ( empty( $url ) ) {
				$url = 'https://cache.areaclienti.webme.it/go.php';
			}
			
			return apply_filters( 'wm_get_api_url', esc_url($url) );
		}
		
		public function get_api_qstr ($args = null) {
			$domain_name = preg_replace ( '/^https?:(\/\/)/i', '', get_option ( 'siteurl' ) );
			$domain_name = preg_replace ( '/\/.*/', '', $domain_name );
			$args = wp_parse_args( $args, [
					'f'               => 'api',
					'action'          => 'privacy',
					'domain_name'     => $domain_name,
					'installation_id' => get_current_blog_id()
				]
			);
			
			return apply_filters( 'wm_query_string', $args );
		}
		
		
		/**
		 * New Hook to Admin Settings
		 * SINCE 1.3.0
		 *
		 * @return void
		 */
		public function echo_status () {
			$api = $this->api;
			$res[] = sprintf( __( 'Iubenda Pro service is <strong>%s</strong>. %s', $this->plugin_name ),
				$api['status']
					?
					__( 'active', $this->plugin_name )
					:
					__( 'not active', $this->plugin_name ),
				empty( $api['id_iubenda'] ) ? null : sprintf( __('Your Iubenda Code is: <i>%s</i>.', $this->plugin_name), $api['id_iubenda'] )
			);
			$res[] = sprintf( __( 'Iubenda Cookie Solution service is <strong>%s</strong>. %s', $this->plugin_name ),
				isset($api['cookie_status']) && !empty($api['cookie_status'])
					?
					__( 'active', $this->plugin_name )
					:
					__( 'not active', $this->plugin_name ),
				empty( $api['cookie_id_iubenda_site']) ? null : sprintf( __('Your Site ID is: <i>%s</i>.', $this->plugin_name), $api['cookie_id_iubenda_site'] )
			);
			
			printf( '<p>%s</p>', implode( "</p>\n<p>", $res ) );
			
			
		}

	}
