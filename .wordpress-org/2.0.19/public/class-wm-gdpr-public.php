<?php
	
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * @link       https://webme.it
	 * @since      1.0.0
	 *
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/public
	 */
	
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the public-facing stylesheet and JavaScript.
	 *
	 * @package    Wm_Gdpr
	 * @subpackage Wm_Gdpr/public
	 * @author     webme.it <info@webme.it>
	 */
	class Wm_Gdpr_Public
	{
		
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
		
		
		public $wm_gdpr;
		public $pos_array;
		public $boolean;
		
		
		/**
		 * Initialize the class and set its properties.
		 *
		 * @param string $plugin_name The name of the plugin.
		 * @param string $version     The version of this plugin.
		 *
		 * @since    1.0.0
		 *
		 */
		public function __construct ( $plugin_name, $version ) {
			
			$this->plugin_name = $plugin_name;
			$this->version = $version;
			$this->wm_gdpr = get_option( $this->plugin_name );
			$this->pos_array = array (
				0 => "top",
				1 => "center",
				2 => "bottom"
			);
			$this->boolean = array (
				0 => "false",
				1 => "true"
			);
			
		}
		
		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles () {
			
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wm-gdpr-public.css', array (), $this->version, 'all' );
			
		}
		
		/**
		 * Enqueues JavaScript for the public-facing side of the site.
		 *
		 * This function enqueues and localizes scripts for Iubenda's GDPR compliance features.
		 * It adds inline scripts for configuration and modifies script tags to include specific
		 * class attributes, facilitating custom behavior and styling.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_scripts () {
			$iub_handle = "wm__iub-cs";
			
			// Enqueue Iubenda scripts
			wp_enqueue_script( 'wm__iub-stub', '//cdn.iubenda.com/cs/ccpa/stub.js', [], $this->version, false );
			wp_enqueue_script( $iub_handle, '//cdn.iubenda.com/cs/iubenda_cs.js', [], $this->version, false );
			
			// Initialize _iub object
			//wp_localize_script( $iub_handle, '_iub', [] );
			
			// Add inline configuration script
			$inline_script[] = 'var _iub = _iub || [];';
			$inline_script[] = sprintf('_iub.csConfiguration = %s;' , $this->parse_iubenda_settings() );
			$inline_script[] = 'console.log(_iub);';
			wp_add_inline_script( $iub_handle, implode("\n", $inline_script), 'before' );
			
			// Add custom class to script tags
			$this->skip_cs_script( $iub_handle );
		}
		
		
		/**
		 * Adds a custom class to a specific enqueued script's tag.
		 *
		 * This method modifies the HTML output of a specific enqueued script tag
		 * by adding a custom class. It's useful for applying targeted CSS or JavaScript
		 * behaviors to specific scripts.
		 *
		 * @param string $script_handle The handle of the script to modify.
		 */
		public function skip_cs_script ( $script_handle ) {
			add_filter( 'script_loader_tag', function ( $tag, $handle ) use ( $script_handle ) {
				if ( substr( $handle, 0, strlen( $script_handle ) ) == $script_handle ) {
					// Insert class attribute before the src attribute
					return str_replace( ' id', ' class="_iub_cs_skip" id', $tag );
				}
				return $tag;
			}, 10, 2 );
		}
		
		
		/**
		 * Append an empty meta as a placeholder for the js scripts
		 *
		 * #since   1.1.3
		 */
		public function parse_meta_tag () {
			//echo '<meta name="wm" >';
			echo '<meta name="wm" >';
		}
		
		
		/**
		 * Initialize html output.
		 *
		 * @return void
		 */
		public function output_start () {
			if ( !is_admin() ) {
				ob_start( array ( $this, 'output_callback' ) );
			}
		}
		
		/**
		 * Finish html output.
		 *
		 * @return void
		 */
		public function output_end () {
			if ( !is_admin() && ob_get_level() ) {
				ob_end_flush();
			}
		}
		
		public function eval_iub ( $add_filter = true ) {
			global $wmIub;
			$eval = true;
			if ( true == $wmIub ) {
				switch ( true ) {
					case is_customize_preview():
					case  function_exists( 'et_fb_is_enabled' ) && et_fb_is_enabled():
					case is_user_logged_in() && current_user_can( 'edit_posts' ):
						$eval = false;
						break;
					case $_POST:
					case  iubendaParser::bot_detected():
					case ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ||
						( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
						isset( $_SERVER["HTTP_X_REQUESTED_WITH"] ) || isset( $_GET['iub_no_parse'] ):
					case ( is_feed() ):
					//case strpos( $output, '<html' ) > 200:
					//case '<html' != substr( $output, 0, 5 ):
					case class_exists( 'WPCF7' ) && (int)WPCF7::get_option( 'iqfix_recaptcha' ) === 0 && !iubendaParser::consent_given():
						$eval = true;
						add_filter( 'wm_prepare_scripts', function ( $args ) {
							$args['grecaptcha'] = 2;
							
							return $args;
						} );
						break;
					case class_exists( 'Jetpack' ):
						$eval = true;
						add_filter( 'wm_prepare_scripts', function ( $args ) {
							$args ['stats.wp.com'] = 5;
							
							return $args;
						} );
						break;
				}
			}
			
			add_filter( 'wm-gdpr-bypass', function ( $bool ) use ( $eval ) {
				
				return !$eval;
			} );
			
			return $eval;
		}
		
		public function output_callback ( $output ) {
			$eval = $this->eval_iub( false );
			if ( true === $eval ) {
				$iubenda = new iubendaParser(
					$output,
					[
						'type'    => 'page',
						'amp'     => false,
						'scripts' => $this->prepare_scripts(),
						'iframes' => $this->prepare_iframes()
					]
				);
				$output = $iubenda->parse();
			}
			
			return $output;
		}
		
		public function bypass () {
			//do_action( 'qm/debug', ['bypass called back', $bool] );
			return apply_filters( 'wm-gdpr-bypass', false );
		}
		
		public function prepare_scripts ( $args = [] ) {
			$args = wp_parse_args( $args, [] );
			$res = [];
			if ( !empty( $args ) ) {
				foreach ( $args as $script => $type ) {
					$res[$type][] = $script;
				}
			}
			
			return apply_filters( 'wm_prepare_scripts', $res );
		}
		
		public function prepare_iframes ( $args = null ) {
			$args = wp_parse_args( $args, [] );
			$res = [];
			if ( !empty( $args ) ) {
				foreach ( $args as $script => $type ) {
					$res[$type][] = $script;
				}
			}
			
			return apply_filters( 'wm_prepare_iframes', $res );
		}
		
		
		/**
		 * NEW COOKIE SCRIPTS
		 * SINCE 1.3.0
		 *
		 * @return void
		 */
		public function cookie_policy_2022 () {
			$bypass = $this->bypass();
			
			if ( $bypass ) {
				return;
			}
			if ( empty( get_transient( $this->plugin_name )['cookie_status'] ) ) {
				return;
			}
			
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], PHP_INT_MIN );
			
			//add_action( 'wm-gdpr-cookie-config', [ $this, 'parse_iubenda_settings' ] );
			
			//require_once( plugin_dir_path( __FILE__ ) . 'partials/wm-gdpr-public-display.php' ) ;
		}
		
		/**
		 * retruve site language
		 *
		 * @since 2.0.18
		 *
		 * @return mixed|string
		 */
		private function get_language(){
			$language = 'it';
			$site_lang = strtolower( substr( get_locale(), 0, 2 ) );
			$available = isset( $this->wm_gdpr['available_languages'] ) ? $this->wm_gdpr['available_languages'] : [];
			if ( isset( $this->wm_gdpr['language'] ) && !empty( $this->wm_gdpr['language'] ) ) {
				$language = $this->wm_gdpr['language'];
				if ( in_array( $site_lang, $available ) ) {
					$language = $site_lang;
				}
			}
			if ( $locale = get_locale() ) {
				$language = strtolower( substr( $locale, 0, 2 ) );
			}
			
			return $language;
		}
		
		/**
		 * parse iubenda settings
		 *
		 *
		 *
		 * @param $args
		 * @param $type
		 *
		 * @return false|mixed|string|null
		 */
		 function parse_iubenda_settings ( $args = [], $type = 'json' ) {
			$language = $this->get_language();
			$site_lang= strtolower( substr( get_locale(), 0, 2 ) );
			//do_action( 'qm/debug', $this->wm_gdpr );
			$cookie_policy_id = isset( $this->wm_gdpr['cookie_id_iubenda'] ) ? $this->wm_gdpr['cookie_id_iubenda'] : '';
			if ( isset( $this->wm_gdpr['ids'][$language] ) ) {
				$cookie_policy_id = $this->wm_gdpr['ids'][$language];
			}
			
			$args = wp_parse_args( $args, [
					'wm_info'                          => $site_lang,
					'enableCcpa'                       => true,
					'countryDetection'                 => false,
					'invalidateConsentWithoutLog'      => true,
					'ccpaAcknowledgeOnDisplay'         => true,
					'ccpaApplies'                      => true,
					'whitelabel'                       => false,
					'lang'                             => $language,
					'wm_lang'                          => $language,
					'siteId'                           => isset( $this->wm_gdpr['cookie_id_iubenda_site'] ) ? $this->wm_gdpr['cookie_id_iubenda_site'] : '',
					'perPurposeConsent'                => true,
					'consentOnContinuedBrowsing'       => false,
					'cookiePolicyId'                   => $cookie_policy_id, //isset( $this->wm_gdpr['cookie_id_iubenda'] ) ? $this->wm_gdpr['cookie_id_iubenda'] : '',
					'enableRemoteConsent'              => true,
					'floatingPreferencesButtonDisplay' => false,
					'promptToAcceptOnBlockedElements'  => true,
					'purposes'                         => '1,2,3,4,5',
					'preferenceCookie'                 => [
						'expireAfter' => 180
					],
					'banner'                           => [
						'position'               => 'float-top-center',
						'slideDown'              => false,
						'prependOnBody'          => false,
						'applyStyles'            => true,
						'customizeButtonDisplay' => true,
						'listPurposes'           => true,
						'explicitWithdrawal'     => true,
						'acceptButtonDisplay'    => true,
						'closeButtonDisplay'     => true,
						'rejectButtonDisplay'    => true
					]
				]
			);
			
			$settings = apply_filters( 'wm-gdpr-iubenda-settings', $args );
			if ( $type == 'json' ) {
				
				return json_encode( $settings );
			}
			
			return $settings;
		}
		
		
		/**
		 * Enqueues and inlines scripts for cookie consent.
		 *
		 * This function handles the logic for determining which scripts to load
		 * for cookie consent based on site language and GDPR settings. It enqueues
		 * the necessary scripts and inlines configuration settings.
		 *
		 * @since 1.0.0
		 */
		function parse_cookie_scripts() {
			$script_handle = 'wm-cookie-consent';
			$language = get_bloginfo('language');
			$cookiejsname = substr($language, 0, 2) === 'it' ? 'cookie.min.js' : 'cookie.en.min.js';
			$options = $this->wm_gdpr;
			
			// Determine script parameters
			$analytics = isset( $options['analytics'] )? $options['analytics'] : null;
			$position = isset($options['position']) ?$this->pos_array[$options['position']] : "bottom";
			$overlay = isset($options['overlay'])?$this->boolean[$options['overlay']] : "false";
			$removeiframe = isset($options['removeiframe'])? $this->boolean[$options['removeiframe']] : "false";
			$policyurl = !empty($options['policyurl']) && substr($options['policyurl'], 0, 4) === 'http' ? $options['policyurl'] : "http://{$options['policyurl']}";
			
			// Enqueue scripts
			wp_enqueue_script($script_handle, "//cdn.webme.it/privacy/$cookiejsname", [], null, true);
			if ($options['status'] === 'ok') {
				wp_enqueue_script('iubenda', 'https://cdn.iubenda.com/iubenda.js', [], null, true);
			}
			
			// Inline script for configuration
			$inline_script = "var analytics = '{$analytics}';";
			$inline_script .= "var position = '{$position}';";
			$inline_script .= "var overlay = '{$overlay}';";
			$inline_script .= "var removeiframe = '{$removeiframe}';";
			if (!empty($policyurl)) {
				$inline_script .= "var policyurl = '{$policyurl}';";
			}
			wp_add_inline_script($script_handle, $inline_script, 'before');
			$this->skip_cs_script( $script_handle );
		}
		
		
		
		
		
		/**
		 * Append Buttons to the footer
		 *
		 * @since   1.0.0
		 */
		function parse_buttons () {
			$language = $this->get_language();
			add_filter( 'wm-gdpr-iubenda-settings', function ( $settings ) use ( $language ) {
				$settings['lang'] = $language;
				return $language;
			} );
			$options = $this->wm_gdpr;
			
			$response = sprintf( '<div id="wm-gdpr-cookie-bar" class="privacy_statement" style="text-align:center;position:absolute;padding:0.8em;z-index:999;width:100%% ;%s">
			<a class="cookie-selection iubenda-cs-preferences-link" style="%s" href="%s">Cookie Policy</a>',
				empty( $options['background'] ) ? null : sprintf( 'background-color:%s;', $options['background'] ),
				empty( $options['foreground'] ) ? null : sprintf( 'color:%s;', $options['foreground'] ),
				apply_filters( 'wm-gdpr_ccl', '#' )
			);
			
			
			$cookie_policy_id = isset( $options['cookie_id_iubenda'] ) ? $options['cookie_id_iubenda'] : '';
			
			//do_action( 'qm/debug', [$language, $cookie_policy_id] );
			if ( isset( $options['ids'][$language] ) ) {
				
				$cookie_policy_id = $options['ids'][$language];
			}
			
			if ( !empty( $cookie_policy_id ) ) {
				$response .= sprintf(
					'&nbsp;-&nbsp;
						<a style="%s" href="https://www.iubenda.com/privacy-policy/%s" class="iubenda-nostyle iubenda-embed" title="Privacy Policy ">Privacy Policy</a>
					<script type="text/javascript">(function (w,d) {var loader = function () {var s = d.createElement("script"), tag = d.getElementsByTagName("style")[0]; s.src="https://cdn.iubenda.com/iubenda.js"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener("load", loader, false);}else if(w.attachEvent){w.attachEvent("onload", loader);}else{w.onload = loader;}})(window, document);</script>',
					empty( $options['foreground'] ) ? null : sprintf( 'color:%s;', $options['foreground'] ),
					$cookie_policy_id
				);
			}
			$response .= '</div>';
			$response .= "<script>
const trig = document.getElementById('wm-gdpr-cookie-bar');
    const els = trig.getElementsByTagName('a');
    for (let el of els) {
        el.addEventListener('click', function (event) {
        if (event.target.classList.contains('cookie-selection') && event.target.getAttribute('href')=='#') {
            event.preventDefault();
        }
    });
    }
    </script>
    ";
			
			echo $response;
		}
		
	}
