<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WooCommerce_MyParcel_Settings_Callbacks' ) ) :

class WooCommerce_MyParcel_Settings_Callbacks {
	/**
	 * Section null callback.
	 *
	 * @return void.
	 */
	public function section() {
	}

	/**
	 * Checkbox callback.
	 *
	 * args:
	 *   option_name - name of the main option
	 *   id          - key of the setting
	 *   value       - value if not 1 (optional)
	 *   default     - default setting (optional)
	 *   description - description (optional)
	 *
	 * @return void.
	 */
	public function checkbox( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		// output checkbox	
		printf( '<input type="checkbox" id="%1$s" name="%2$s" value="%3$s" %4$s class="%5$s"/>', $id, $setting_name, $value, checked( $value, $current, false ), $class );
	
		// output description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	/**
	 * Text input callback.
	 *
	 * args:
	 *   option_name - name of the main option
	 *   id          - key of the setting
	 *   size        - size of the text input (em)
	 *   default     - default setting (optional)
	 *   description - description (optional)
	 *   type        - type (optional)
	 *
	 * @return void.
	 */
	public function text_input( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		// echo '<pre>';var_dump($this->normalize_settings_args( $args ));echo '</pre>';
		if (empty($type)) {
			$type = 'text';
		}

		if ($type == 'number') {
			$width = ($size * 10) + 25;
			$style = "width: {$width}px";
		} else {
			$style = '';
		}

		printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" size="%5$s" placeholder="%6$s" class="%7$s" style="%8$s"/>', $type, $id, $setting_name, $current, $size, $placeholder, $class, $style );
	
		// output description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	/**
	 * Color picker callback.
	 *
	 * args:
	 *   option_name - name of the main option
	 *   id          - key of the setting
	 *   size        - size of the text input (em)
	 *   default     - default setting (optional)
	 *   description - description (optional)
	 *
	 * @return void.
	 */
	public function color_picker( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		// echo '<pre>';var_dump($this->normalize_settings_args( $args ));echo '</pre>';

		printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" size="%4$s" class="wcmp-color-picker %5$s"/>', $id, $setting_name, $current, $size, $class );
	
		// output description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	/**
	 * Textarea callback.
	 *
	 * args:
	 *   option_name - name of the main option
	 *   id          - key of the setting
	 *   width       - width of the text input (em)
	 *   height      - height of the text input (lines)
	 *   default     - default setting (optional)
	 *   description - description (optional)
	 *
	 * @return void.
	 */
	public function textarea( $args ) {
		extract( $this->normalize_settings_args( $args ) );
	
		printf( '<textarea id="%1$s" name="%2$s" cols="%4$s" rows="%5$s" placeholder="%6$s"/>%3$s</textarea>', $id, $setting_name, $current, $width, $height, $placeholder );
	
		// output description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	/**
	 * Select element callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string	  Select field.
	 */
	public function select( $args ) {
		extract( $this->normalize_settings_args( $args ) );
	
		printf( '<select id="%1$s" name="%2$s" class="%3$s">', $id, $setting_name, $class );

		foreach ( $options as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
		}

		echo '</select>';

		if (isset($custom)) {
			printf( '<div class="%1$s_custom custom">', $id );

			switch ($custom['type']) {
				case 'text_element_callback':
					$this->text_input( $custom['args'] );
					break;		
				case 'multiple_text_element_callback':
					$this->multiple_text_input( $custom['args'] );
					break;		
				default:
					break;
			}
			echo '</div>';
		}
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}

	}

	public function radio_button( $args ) {
		extract( $this->normalize_settings_args( $args ) );
	
		foreach ( $options as $key => $label ) {
			printf( '<input type="radio" class="radio" id="%1$s[%3$s]" name="%2$s" value="%3$s"%4$s />', $id, $setting_name, $key, checked( $current, $key, false ) );
			printf( '<label for="%1$s[%3$s]"> %4$s</label><br>', $id, $setting_name, $key, $label);
		}
		
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}

	}

	/**
	 * Multiple text element callback.
	 * @param  array $args Field arguments.
	 * @return string	   Text input field.
	 */
	public function multiple_text_input( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		if (!empty($header)) {
			echo "<p><strong>{$header}</strong>:</p>";
		}

		foreach ($fields as $name => $field) {
			$label = $field['label'];
			$size = $field['size'];
			$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

			if (isset($field['label_width'])) {
				$style = sprintf( 'style="display:inline-block; width:%1$s;"', $field['label_width'] );
			} else {
				$style = '';
			}

			$suffix = isset($field['suffix']) ? $field['suffix'] : '';

			// output field label
			printf( '<label for="%1$s_%2$s" %3$s>%4$s</label>', $id, $name, $style, $label );

			// output field
			$field_current = isset($current[$name]) ? $current[$name] : '';
			printf( '<input type="text" id="%1$s_%3$s" name="%2$s[%3$s]" value="%4$s" size="%5$s" placeholder="%6$s"/>%7$s<br/>', $id, $setting_name, $name, $field_current, $size, $placeholder, $suffix );

		}
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', $args['description'] );
		}
	}

	public function order_status_select( $args ) {
		// get list of WooCommerce statuses
		if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
			$statuses = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
			foreach ( $statuses as $status ) {
				$order_statuses[esc_attr( $status->slug )] = esc_html__( $status->name, 'woocommerce' );
			}
		} else {
			$statuses = wc_get_order_statuses();
			foreach ( $statuses as $status_slug => $status ) {
				$status_slug   = 'wc-' === substr( $status_slug, 0, 3 ) ? substr( $status_slug, 3 ) : $status_slug;
				$order_statuses[$status_slug] = $status;
			}
		}

		// select order status
		$args['options'] = $order_statuses;
		$this->select( $args );
	}

	public function shipping_methods_package_types( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		foreach ($package_types as $package_type => $package_type_title) {
			printf ('<div class="package_type_title">%s:<div>', $package_type_title);
			$args['package_type'] =  $package_type;
			unset($args['description']);
			$this->shipping_method_search( $args );
		}
		// Displays option description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	// Shipping method search callback.
	public function shipping_method_search( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		if (isset($package_type)) {
			$setting_name = "{$setting_name}[{$package_type}]";
			$current = isset($current[$package_type]) ? $current[$package_type] : '';
		}

		?>
		<select id="<?php echo $id; ?>" name="<?php echo $setting_name; ?>[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php echo $placeholder; ?>">
			<?php
				$shipping_methods_selected = (array) $current;
				$shipping_methods = WC()->shipping->load_shipping_methods();
				if ( $shipping_methods ) foreach ( $shipping_methods as $key => $shipping_method ) {
					$method_title = !empty($shipping_methods[$key]->method_title) ? $shipping_methods[$key]->method_title : $shipping_methods[$key]->title;
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $shipping_methods_selected ), true, false ) . '>' . esc_html( $method_title ) . '</option>';
					// split flat rate by shipping class
					if ( ( $key == 'flat_rate' || $key == 'legacy_flat_rate' ) && version_compare( WOOCOMMERCE_VERSION, '2.4', '>=' ) ) {
						$shipping_classes = WC()->shipping->get_shipping_classes();
						foreach ($shipping_classes as $shipping_class) {
							if ( ! isset( $shipping_class->term_id ) ) {
								continue;
							}
							$id = $shipping_class->term_id;
							$name = esc_html( "{$method_title} - {$shipping_class->name}" );
							$method_class = esc_attr( $key ).":".$id;
							echo '<option value="' . $method_class . '"' . selected( in_array( $method_class, $shipping_methods_selected ), true, false ) . '>' . $name . '</option>';
						}
					}
				}
			?>
		</select>
		<?php
		// Displays option description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	public function enhanced_select( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		?>
		<select id="<?php echo $id; ?>" name="<?php echo $setting_name; ?>[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php echo $placeholder; ?>">
			<?php
			foreach ( $options as $key => $title ) {
				echo '<option value="' . esc_attr( $key ) . '"' . selected( !empty($current) && in_array( $key, (array) $current ), true, false ) . '>' . esc_html( $title ) . '</option>';
			}
			?>
		</select>
		<?php
		// Displays option description.
		if ( isset( $description ) ) {
			printf( '<p class="description">%s</p>', $description );
		}
	}

	public function delivery_option_enable( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		// checkbox (enable)
		$cb_args = array(
			'id'			=> "{$id}_enabled",
			'class'			=> 'wcmp_delivery_option'
		);
		// number (fee)
		$fee_args = array(
			'id'			=> "{$id}_fee",
			'type'			=> 'text',
			'size'			=> '5',
		);					
		// textarea (description)
		$description_args = array(
			'id'			=> "{$id}_description",
			'type'			=> 'text',
			'size'			=> '60',
		);


		?>
		<?php $this->checkbox( array_merge( $args, $cb_args ) ); ?><br/>
		<table class="wcmp_delivery_option_details">
			<!--
			<tr>
				<td><?php _e( 'Description', 'woocommerce-myparcel' )?>:</td>
				<td><?php $this->text_input( array_merge( $args, $description_args ) ); ?></td>
			</tr>
			!-->
			<tr> 
				<td><?php _e( 'Additional fee (ex VAT, optional)', 'woocommerce-myparcel' )?>:</td>
				<td>&euro; <?php $this->text_input( array_merge( $args, $fee_args ) ); ?></td>
			</tr>
		</table>
		<?php
	}


	public function delivery_options_table( $args ) {
		extract( $this->normalize_settings_args( $args ) );
		?>
		<table>

		<table>
			<thead>
				<tr>
					<th style="width: 2.2em"><?php // _e( 'Enabled', 'woocommerce-myparcel' )?></th>
					<th><?php _e( 'Option', 'woocommerce-myparcel' )?></th>
					<th><?php _e( 'Fee (optional)', 'woocommerce-myparcel' )?></th>
					<th><?php _e( 'Description', 'woocommerce-myparcel' )?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($options as $key => $title) {
					// prepare args for input fields
					$common_args = array (
						'option_name'	=> "{$option_name}[{$key}]",
					);
					// checkbox (enable)
					$cb_args = array(
						'id'			=> 'enabled',
					);
					// number (fee)
					$fee_args = array(
						'id'			=> 'fee',
						'type'			=> 'number',
						'size'			=> '5',
					);					
					// textarea (description)
					$description_args = array(
						'id'			=> 'description',
						'width'			=> '50',
						'height'		=> '4',
					);				
					?>
					<tr>
						<td><?php $this->checkbox( array_merge( $common_args, $cb_args ) ); ?></td>
						<td><?php echo $title; ?></td>
						<td><input type="number" min="0"></td>
						<td><input type="text"></td>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Wrapper function to create tabs for settings in different languages
	 * @param  [type] $args     [description]
	 * @param  [type] $callback [description]
	 * @return [type]           [description]
	 */
	public function i18n_wrap ( $args ) {
		extract( $this->normalize_settings_args( $args ) );

		if ( $languages = $this->get_languages() ) {
			printf( '<div id="%s-%s-translations" class="translations">', $option_name, $id)
			?>
				<ul>
					<?php foreach ( $languages as $lang_code => $language_name ) {
						$translation_id = "{$option_name}_{$id}_{$lang_code}";
						printf('<li><a href="#%s">%s</a></li>', $translation_id, $language_name );
					}
					?>
				</ul>
				<?php foreach ( $languages as $lang_code => $language_name ) {
					$translation_id = "{$option_name}_{$id}_{$lang_code}";
					printf( '<div id="%s">', $translation_id );
					$args['lang'] = $lang_code;
					call_user_func( array( $this, $callback ), $args );
					echo '</div>';
				}
				?>
			
			</div>
			<?php
		} else {
			$args['lang'] = 'default';
			call_user_func( array( $this, $callback ), $args );
		}
	}

	public function get_languages () {
		$wpml = class_exists('SitePress');
		// $wpml = true; // for development

		if ($wpml) {
			// use this instead of function call for development outside of WPML
			// $icl_get_languages = 'a:3:{s:2:"en";a:8:{s:2:"id";s:1:"1";s:6:"active";s:1:"1";s:11:"native_name";s:7:"English";s:7:"missing";s:1:"0";s:15:"translated_name";s:7:"English";s:13:"language_code";s:2:"en";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/en.png";s:3:"url";s:23:"http://yourdomain/about";}s:2:"fr";a:8:{s:2:"id";s:1:"4";s:6:"active";s:1:"0";s:11:"native_name";s:9:"Français";s:7:"missing";s:1:"0";s:15:"translated_name";s:6:"French";s:13:"language_code";s:2:"fr";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/fr.png";s:3:"url";s:29:"http://yourdomain/fr/a-propos";}s:2:"it";a:8:{s:2:"id";s:2:"27";s:6:"active";s:1:"0";s:11:"native_name";s:8:"Italiano";s:7:"missing";s:1:"0";s:15:"translated_name";s:7:"Italian";s:13:"language_code";s:2:"it";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/it.png";s:3:"url";s:26:"http://yourdomain/it/circa";}}';
			// $icl_get_languages = unserialize($icl_get_languages);
			
			$icl_get_languages = icl_get_languages('skip_missing=0');
			$languages = array();
			foreach ($icl_get_languages as $lang => $data) {
				$languages[$data['language_code']] = $data['native_name'];
			}
		} else {
			return false;
		}

		return $languages;
	}

	public function normalize_settings_args ( $args ) {
		$args['value'] = isset( $args['value'] ) ? $args['value'] : 1;

		$args['placeholder'] = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$args['class'] = isset( $args['class'] ) ? $args['class'] : '';

		// get main settings array
		$option = get_option( $args['option_name'] );
	
		$args['setting_name'] = "{$args['option_name']}[{$args['id']}]";

		if (isset($args['lang'])) {
			// i18n settings name
			$args['setting_name'] = "{$args['setting_name']}[{$args['lang']}]";
			// copy current option value if set
			
			if ( $args['lang'] == 'default' && !empty($option[$args['id']]) && !isset( $option[$args['id']]['default'] ) ) {
				// we're switching back from WPML to normal
				// try english first
				if ( isset( $option[$args['id']]['en'] ) ) {
					$args['current'] = $option[$args['id']]['en'];
				} else {
					// fallback to the first language if english not found
					$first = array_shift($option[$args['id']]);
					if (!empty($first)) {
						$args['current'] = $first;
					}
				}
			} else {
				if ( isset( $option[$args['id']][$args['lang']] ) ) {
					$args['current'] = $option[$args['id']][$args['lang']];
				} elseif (isset( $option[$args['id']]['default'] )) {
					$args['current'] = $option[$args['id']]['default'];
				}
			}
		} else {
			// copy current option value if set
			if ( isset( $option[$args['id']] ) ) {
				$args['current'] = $option[$args['id']];
			}
		}

		// falback to default or empty if no value in option
		if ( !isset($args['current']) ) {
			$args['current'] = isset( $args['default'] ) ? $args['default'] : '';
		}		

		return $args;
	}

	/**
	 * Validate options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array		validated options.
	 */
	public function validate( $input ) {
		// Create our array for storing the validated options.
		$output = array();

		if (empty($input) || !is_array($input)) {
			return $input;
		}
	
		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {
	
			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[$key] ) ) {
				if ( is_array( $input[$key] ) ) {
					foreach ( $input[$key] as $sub_key => $sub_value ) {
						$output[$key][$sub_key] = $input[$key][$sub_key];
					}
				} else {
					$output[$key] = $input[$key];
				}
			}
		}
	
		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'woocommerce_myparcel_settings_validate_input', $input, $input );
	}
}

endif; // class_exists

return new WooCommerce_MyParcel_Settings_Callbacks();