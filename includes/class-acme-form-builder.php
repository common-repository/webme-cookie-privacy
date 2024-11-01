<?php
	
	//class Class_Acme_Form_Builder
	
	class WmGdprForms
	{
		private $namespace;
		public $presets;
		public $collections;
		
		public function __construct ( $namespace ) {
			$this->namespace = $namespace;
			//do_action( 'qm/info', 'Form Namespace: ' . $this->plugin_name );
		}
		
		/**
		 * Validates data before saving
		 *
		 * @param $input
		 *
		 * @return mixed|string|void
		 */
		public function validate ( $input, $overwrite = false ) {
			$valid = $this->presets;
			if ( !is_array( $valid ) ) {
				$valid = array ();
			}
			if ( is_array( $input ) /*&& isset( $input['page_id'] )*/ ) {
				foreach ( $this->collections as $k => $collection ) {
					$key = $k;//is_array( $collection['name'] ) ? $collection['name'][0] : $collection['name'];
					if ( $overwrite ) {
						$value = isset( $input[$key] ) ? $input[$key] : null;
					} else {
						$value = isset( $input[$key] ) ? $input[$key] : $collection['value'];
					}
					switch ( true ) {
						case $collection['filter'] == 'textarea':
							$arValue = explode( "\n", $value );
							foreach ( $arValue as $val ) {
								$validity[] = call_user_func( $collection['validate'], $val );
							}
							$valid[$key] = implode( "\n", $validity );
							break;
						case $k !== 'page_id':
						case substr( $k, 0, 4 ) !== '__i_':
						case $collection['filter'] !== 'title':
						case $collection['filter'] !== 'separator':
							$valid[$key] = call_user_func( $collection['validate'], $value );
							break;
					}
					//if ( $c ) {
					//	printf( "field is %s ==> value is %s\n", $key, $valid[$key] );
					//	printf( "input value is %s and %s\n", $input[$key],isset($input[$key])?'is set':'is not set' );
					//}
				}
			}
			
			return $valid;
		}
		
		public function wrapper ( $fieldset = null ) {
			$fields = [];
			$class = [];
			$res = [];
			$output = [];
			$tabbed = apply_filters( $this->namespace.'-tabbed', [] );
			$i = 0;
			if ( is_array( $fieldset ) ) {
				foreach ( $fieldset as $params ) {
					switch ( $params['filter'] ) {
						case 'hidden':
							$hidden[] = $this->hidden( $params );
							break;
						case 'separator':
							$fields[$i] = call_user_func( [ $this, $params['filter'] ] );
							break;
						case 'title':
							$fields[$i] = call_user_func( [ $this, $params['filter'] ], $params );
							break;
						default:
							$class[$i] = isset( $params['class'] ) ? $params['class'] : array ();
							$fields[$i] = call_user_func( [ $this, $params['filter'] ], $params );
							$wrap[$i] = true;
					}
					if ( isset( $params['tabslug'] ) ) {
						$tabslug[$i] = $params['tabslug'];
					}
					$i++;
				}
				// Prepends hidden fields
				if ( isset( $hidden ) && is_array( $hidden ) ) {
					foreach ( $hidden as $field ) {
						$res[] = $field;
						$output[] = $field;
					}
				}
				// Wraps form fields in html tags
				if ( isset( $fields ) && is_array( $fields ) ) {
					foreach ( $fields as $index => $field ) {
						//do_action( 'qm/debug', 'wrapping up' );
						$wrapper = '%s';
						if ( isset( $wrap[$index] ) ) {
							$wrapper = sprintf( '<p class="form-field %s">', implode( ' ', $class[$index] ) ) . '%s</p>';
						}
						
						if ( empty( $tabbed ) ) {
							$res[] = sprintf( $wrapper, $field );
						}
						else {
							$res[$tabslug[$index]][] = sprintf( $wrapper, $field );
						}
					}
				}
			}
			if ( empty( $tabbed ) ) {
				return $res;
			}
			if ( isset( $res['untabbed'] ) ) {
				$dash[] = sprintf( '<section class="untabbed">%s</section>', implode( "\n", $res['untabbed'] ) );
				unset( $res['untabbed'] );
				unset( $tabbed['untabbed'] );
			}
			
			$style[] = sprintf('input[name="tab-%s"] { display:none; }', $this->namespace);
			foreach ( $tabbed as $tabslug => $label ) {
				$style[] = sprintf( '
				input#tab-%1$s + label {
					display: inline-block;
					border: 1px solid #999;
					background: #EEE;
					padding: 4px 12px;
					border-radius: 4px 4px 0 0;
					position: relative;
					top: 1px;
					float:none;
					margin: 0;
				}
				input#tab-%1$s:checked + label {
					background: #FFF;
					border-bottom: 1px solid transparent;
				}
				input#tab-%1$s ~ .tab {
					display: none;
					border-top: 1px solid #999;
					padding: 12px;
					overflow: hidden;
				}
				input#tab-%1$s:checked ~ .tab.%1$s { display: block }
				',
				$tabslug
				);
				$dash[] = sprintf( '<input type="radio" name="tab-%1$s" id="tab-%2$s" %3$s />', $this->namespace, $tabslug, isset( $c ) ? null : 'checked' );
				$dash[] = sprintf( '<label for="tab-%s">%s</label>', $tabslug, $label );
				if ( !isset( $c ) ) {
					$c = true;
				}
				$tabs[] = sprintf( '<div class="tab %s">%s</div>', $tabslug, implode( "\n", $res[$tabslug] ) );
			}
			$output[] = sprintf( '<style>%s</style>', implode( "\n", $style ) );
			$output[] = implode( "\n", $dash );
			$output[] = implode( "\n", $tabs );
			return $output;
		}
		
		public function form ( $fieldset, $args, $nonce = false ) {
			$args = wp_parse_args( $args, array (
				'method' => 'post',
				'action' => '#',
				'id'     => sprintf( '%s-form', $this->namespace ),
				'name'   => sprintf( '%s-form', $this->namespace ),
				'class'  => array ( 'acme_generic_form_class' )
			) );
			
			$res = sprintf( '<form id="%s" name="%s" method="%s" action="%s" class="%s">%s%s</form>',
				$args['id'],
				$args['name'],
				$args['method'],
				$args['action'],
				implode( ' ', $args['class'] ),
				$nonce ? wp_nonce_field( $this->namespace, '_wpnonce', true, false ) : null,
				implode( "\n", $this->wrapper( $fieldset ) )
			);
			
			return $res;
		}
		
		public function pattern ( $basename, $fieldname, $collection ) {
			if ( empty( $basename ) ) {
				if ( is_array( $fieldname ) ) {
					$fn = reset( $fieldname );
					$pattern = '[%s]%s';
				}
				else {
					$fn = $fieldname;
					$pattern = '%s%s';
				}
				$str = sprintf( $pattern, $fn, true == $collection ? '[]' : $collection );
			}
			else {
				if ( is_array( $fieldname ) ) {
					$fn = reset( $fieldname );
					$pattern = '%s[%s]%s';
				}
				else {
					$fn = $fieldname;
					$pattern = '%s-%s%s';
				}
				$str = sprintf( $pattern, $basename, $fn, true == $collection ? '[]' : $collection );
			}
			
			return $str;
		}
		
		public function id_build ( $name ) {
			$strName = is_array( $name ) ? reset( $name ) : $name;
			if ( substr( $strName, 0, 4 ) === '__i_' ) {
				return null;
			}
			if ( is_array( $name ) ) {
				if ( is_numeric( key( $name ) ) ) {
					return reset( $name );
				}
				return sprintf( '%s_%s', current( $name ), key( $name ) );
			}
			
			return $name;
		}
		
		public function custom_attrs ( $args ) {
			$arHtml = [];
			foreach ( $args as $attr => $val ) {
				$arHtml[] = sprintf( '%s="%s"', $attr, $val );
			}
			$html_attr = implode( ' ', $arHtml );
			
			return $html_attr;
		}
		
		public function date_time_html5 ( $timestamp ) {
			return sprintf( '%sT%s',
				date( 'Y-m-d', $timestamp ),
				date( 'H:m', $timestamp )
			);
		}
		
		
		public function title ( $args ) {
			$args = wp_parse_args( $args, [
					'tag'   => 'h2',
					'title' => null,
					'class' => ['acme-section-title'],
					'open'  => false,
					'close' => false
				]
			);
			$res = null;
			if ( true == $args['open'] ) {
				$res .= sprintf( '<%1$s class="%2$s">%3$s',
					$args['tag'],
					implode( ' ', $args['class'] ),
					$args['title']
				);
			}
			if ( true == $args['close'] ) {
				$res .= sprintf( '</%s>', $args['tag'] );
			}
			
			return $res;
		}
		
		public function separator ( ) {
			
			return '<hr>';
		}
		
		
		/**
		 * HTML Input hidden Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function hidden ( $args ) {
			$args = wp_parse_args( $args, [
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			
			$res = sprintf( '<input type="hidden" id="%1$s" name="%2$s" value="%3$s" %4$s>',
				$name,
				$pattern,
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs
			);
			
			return $res;
		}
		
		/**
		 * HTML Checkbox Parser
		 * VERSION 1.0
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function checkbox ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs(  $args['custom_attributes'] );
			
			$res = sprintf( '%s<legend class="screen-reader-text"><span>%s</span></legend>',
				isset( $args['pre'] ) ? sprintf( '<em class="pre">%s</em>', $args['pre'] ) : null,
				$args['label']
			);
			$res .= sprintf( '<label for="%s"><span>%s:</span></label>',
				$name,
				$args['label']
			);
			$res .= sprintf( '<input type="checkbox" id="%1$s" name="%2$s" value=1 %3$s %4$s>',
				$name,
				$pattern,
				checked( $args['value'], 1, false ),
				$attrs
			);
			if ( isset( $args['post'] ) ) {
				$res .= sprintf( '<em class="post">%s</em>', $args['post'] );
			}
			
			return $res;
		}
		
		/**
		 * HTML Checkbox Parser
		 * VERSION 1.0
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function radio ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'show_colon'        => true,
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = sprintf( '<legend class="screen-reader-text"><span>%s</span></legend>', $args['label'] );
			$res .= sprintf( '<input type="radio" id="%1$s" name="%2$s" value="%5$s" %3$s %4$s>',
				$name,
				$pattern,
				checked( $args['value'], $args['compare'], false ),
				$attrs,
				$args['value']
			);
			$res .= sprintf( '<label for="%s">%s%s</label>',
				$name,
				$args['label'],
				$args['show_colon'] ? ':' : null
			);
			
			return $res;
		}
		
		/**
		 * HTML Input BUTTON Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function button ( $args ) {
			$args = wp_parse_args( $args, [
					'type'              => 'button',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			
			$attrs = $this->custom_attrs(  $args['custom_attributes'] );
			$res = sprintf( '<input type="%1$s" name="%1$s" class="%2$s" value="%3$s" %4$s>',
				$args['type'],
				implode( ' ', $args['class'] ),
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs
			);
			
			return $res;
		}
		
		/**
		 * HTML Input TEXT Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function text ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => null,
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = null;
			if ( !empty( $args['pre'] ) ) {
				$res .= sprintf( '<em class="pre">%s</em>', $args['pre'] );
			}
			if ( !empty( $args['label'] ) ) {
				$res .= sprintf( '<legend class="screen-reader-text"><span>%2$s</span></legend><label for="%1$s"><span>%2$s:</span></label>',
					$name,
					$args['label']
				);
			}
			$res .= sprintf( '<input type="%6$s" id="%1$s" name="%2$s" class="%3$s" value="%4$s" %5$s>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs,
				isset( $args['type'] ) ? $args['type'] : 'text'
			);
			if ( isset( $args['post'] ) ) {
				$res .= sprintf( '<em class="post">%s</em>', $args['post'] );
			}
			
			return $res;
		}
		/**
		 * HTML Input TEXT Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function color ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => null,
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = null;
			if ( !empty( $args['pre'] ) ) {
				$res .= sprintf( '<em class="pre">%s</em>', $args['pre'] );
			}
			if ( !empty( $args['label'] ) ) {
				$res .= sprintf( '<legend class="screen-reader-text"><span>%2$s</span></legend><label for="%1$s"><span>%2$s:</span></label>',
					$name,
					$args['label']
				);
			}
			$res .= sprintf( '<input type="%6$s" id="%1$s" name="%2$s" class="%3$s" value="%4$s" %5$s>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs,
				isset( $args['type'] ) ? $args['type'] : 'text'
			);
			if ( isset( $args['post'] ) ) {
				$res .= sprintf( '<em class="post">%s</em>', $args['post'] );
			}
			
			return $res;
		}
		
		/**
		 * HTML Input PASSWORD Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function password ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = sprintf( '<legend class="screen-reader-text"><span>%s</span></legend>', $args['label'] );
			$res .= sprintf( '<label for="%s"><span>%s:</span></label>',
				$name,
				$args['label']
			);
			$res .= sprintf( '<input type="password" id="%1$s" name="%2$s" class="%3$s" value="%4$s" %5$s>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs
			);
			
			return $res;
		}
		
		/**
		 * HTML Input DateTime Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function datetime ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => $this->date_time_html5( time() ),
					'min'               => $this->date_time_html5( time() ),
					'max'               => $this->date_time_html5( time() + ( 60 * 60 * 24 * 365 ) ),
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = sprintf( '<legend class="screen-reader-text"><span>%s</span></legend>', $args['label'] );
			$res .= sprintf( '<label for="%s"><span>%s:</span></label>',
				$name,
				$args['label']
			);
			$res .= sprintf( '<input type="datetime-local" id="%1$s" name="%2$s" class="%3$s" value="%4$s" %5$s>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				empty( $args['value'] ) ? '' : $args['value'],
				$attrs
			);
			
			return $res;
		}
		/**
		 * HTML Input TEXTAREA Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		
		/**
		 * HTML Input TEXTAREA Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function textarea ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			
			$res = sprintf( '<legend class="screen-reader-text"><span>%s</span></legend>', $args['label'] );
			$res .= sprintf( '<label for="%s"><span>%s:</span></label>',
				$name,
				$args['label']
			);
			$res .= sprintf( '<textarea id="%1$s" name="%2$s" class="textarea %3$s" %5$s>%4$s</textarea>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				$args['value'],
				$attrs
			);
			
			return $res;
		}
		
		/**
		 * HTML Input SELECT BOX Parser
		 * VERSION 1.0         *
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function select ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => null,
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'options'           => [],
					'basename'          => null,
					'class'             => array ( 'acme_generic_form_class' ),
					'collection'        => null,
					'custom_attributes' => []
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			
			$attrs = $this->custom_attrs( $args['custom_attributes'] );
			$res = null;
			if ( !empty( $args['pre'] ) ) {
				$res .= sprintf( '<em class="pre">%s</em>', $args['pre'] );
			}
			if ( !empty( $args['label'] ) ) {
				$res .= sprintf( '<legend class="screen-reader-text"><span>%2$s</span></legend><label for="%1$s"><span>%2$s:</span></label>',
					$name,
					$args['label']
				);
			}
			$options = array ();
			if ( is_array( $args['options'] ) ) {
				foreach ( $args['options'] as $curValue => $curLabel ) {
					$options[] = sprintf( '<option value="%s" %s>%s</option>',
						$curValue,
						selected( $args['value'], $curValue, false ),
						$curLabel
					);
				}
			}
			$res .= sprintf( '<select id="%1$s" name="%2$s" class="textarea %3$s" %5$s>%4$s</select>',
				$name,
				$pattern,
				implode( ' ', $args['class'] ),
				implode( "\n", $options ),
				$attrs
			);
			if ( isset( $args['post'] ) ) {
				$res .= sprintf( '<em class="post">%s</em>', $args['post'] );
			}
			
			return $res;
		}
		
		/**
		 * HTML Image Uploader Parser
		 * VERSION 1.0
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function image ( $args ) {
			$args = wp_parse_args( $args, [
					'label'             => 'acme_generic_label',
					'name'              => 'acme_generic_field_name',
					'value'             => null,
					'basename'          => null,
					'collection'        => null
				]
			);
			$pattern = $this->pattern( $args['basename'], $args['name'], $args['collection'] );
			$name = $this->id_build( $args['name'] );
			$res = sprintf( '<legend class="screen-reader-text"><span>%s</span></legend>', $args['label'] );
			$res .= sprintf( '<label for="%s"><span>%s:</span></label>',
				$name,
				$args['label']
			);
			if ( $image = wp_get_attachment_image_src( $args['value'] ) ) {
				$res .= sprintf( '<a href="#" class="%s" data-target="%s"><img src="%s" /></a>',
					sprintf( '%s-img-upl', empty( $args['basename'] ) ? 'acme_basename' : $args['basename'] ),
					$name,
					$image[0]
				);
				$res .= sprintf( '<a href="#" class="%s" data-target="%s">%s',
					sprintf( '%s-img-rmv', empty( $args['basename'] ) ? 'acme_basename' : $args['basename'] ),
					$name,
					__( 'Remove Image', $this->plugin_name_ )
				);
				$res .= sprintf( '<input type="hidden" id="%s" name="%s" value="%s"></div>',
					$name,
					$pattern,
					empty( $args['value'] ) ? : $args['value']
				);
			}
			else {
				$res .= sprintf( '<a href="#" class="%s" data-target="%s">%s</a>',
					sprintf( '%s-img-upl', empty( $args['basename'] ) ? 'acme_basename' : $args['basename'] ),
					$name,
					__( 'Upload Image', $this->namespace )
				);
				$res .= sprintf( '<a href="#" class="%s" data-target="%s">%s</a>',
					sprintf( '%s-img-rmv', empty( $args['basename'] ) ? 'acme_basename' : $args['basename'] ),
					$name,
					__( 'Remove Image', $this->namespace )
				);
				$res .= sprintf( '<input type="hidden" id="%s" name="%s" value="">',
					$name,
					$pattern
				);
			}
			
			return $res;
		}
	}