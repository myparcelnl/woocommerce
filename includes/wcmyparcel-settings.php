<?php
class WC_MyParcel_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'menu' ) ); // Add menu.
		add_action( 'admin_init', array( &$this, 'init_settings' ) ); // Registers settings
	}

	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'MyParcel', 'wcmyparcel' ),
			__( 'MyParcel', 'wcmyparcel' ),
			'manage_options',
			'wcmyparcel',
			array( $this, 'settings_page' )
		);	
	}
	
	public function settings_page() {
			?>
	
				<div class="wrap">
					<div class="icon32" id="icon-options-general"><br /></div>
					<h2><?php _e( 'MyParcel export settings', 'wcmyparcel' ); ?></h2>
	
	 
					<form method="post" action="options.php">
						<?php
							settings_fields( 'wcmyparcel_settings' );
							do_settings_sections( 'wcmyparcel_settings' );
	
							submit_button();
						?>
	
					</form>
	
				</div>
	
			<?php
	}
	
	/**
	 * User settings.
	 */
	
	public function init_settings() {
		$option = 'wcmyparcel_settings';
	
		// Create option in wp_options.
		if ( false == get_option( $option ) ) {
			add_option( $option );
		}
	
		// Section.
		add_settings_section(
			'general',
			__( 'Algemene instellingen', 'wcmyparcel' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		add_settings_field(
			'api_username',
			__( 'Gebruikersnaam', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'api_username',
				'size'			=> '50',
				//'description'	=> __( 'Uw MyParcel gebruikersnaam', 'wcmyparcel' ),
			)
		);

		add_settings_field(
			'api_key',
			__( 'API key', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'api_key',
				'size'			=> '50',
				'description'	=> __( 'Uw API key, deze dient u eerst <a href="mailto:info@myparcel.nl">aan te vragen bij MyParcel</a>', 'wcmyparcel' ),
			)
		);

		add_settings_field(
			'download_display',
			__( 'Labelweergave', 'wpo_wcpdf' ),
			array( &$this, 'radio_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'download_display',
				'options' 		=> array(
					'download'	=> __( 'Download PDF' , 'wcmyparcel' ),
					'display'	=> __( 'Open de PDF in een nieuw venster/tab' , 'wcmyparcel' ),
				),
			)
		);

		add_settings_field(
			'auto_complete',
			__( 'Zet orders automatisch op voltooid na (succesvolle) MyParcel export', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'auto_complete',
				'description'	=> __( 'Zorg dat <strong>Verwerk labels direct</strong> staat ingeschakeld als u deze optie gebruikt in combinatie met <strong>Email track&trace code</strong>, anders wordt de track&trace code niet mee gestuurd.', 'wcmyparcel' )
			)
		);		

		add_settings_field(
			'process',
			__( 'Verwerk labels direct', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'process',
				'description'	=> __( 'Wanneer u deze optie ingeschakeld heeft, worden de orders bij het exporteren naar MyParcel direct verwerkt.', 'wcmyparcel' )
			)
		);

		add_settings_field(
			'email_tracktrace',
			__( 'Email track&trace code', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'email_tracktrace',
				'description'	=> __( 'De track&trace code (wanneer beschikbaar) wordt automatisch toegevoegd aan de orderbevestingsmail naar de klant.<br/><strong>Let op!</strong> Wanneer u deze optie selecteert, dient u erop te letten dat u geen track&trace mail vanuit MyParcel verstuurt.', 'wcmyparcel' )
			)
		);

		add_settings_field(
			'myaccount_tracktrace',
			__( 'Laat track&trace code op de <em>Mijn Account</em> pagina zien.', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'myaccount_tracktrace',
			)
		);

		add_settings_field(
			'pakjegemak',
			__( 'Voeg PakjeGemak toe aan de WooCommerce checkout', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'pakjegemak',
				'description'	=> __( 'Steeds meer consumenten kiezen ervoor hun pakket af te halen bij een afhaallocatie in plaats van deze thuis te laten bezorgen. Deze service van PostNL wordt PakjeGemak genoemd. U kunt dit ook aanbieden aan uw klanten.<br/><strong>Let op!</strong> Deze service is niet geoptimaliseerd voor mobiele browsers en werkt daar ook niet zo goed. U zou er voor kunnen kiezen om de pakjegemak knop door middel van CSS te verbergen voor mobiele browsers.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'pakjegemak_description',
			__( 'Omschrijving van PakjeGemak op de checkout pagina', 'wcmyparcel' ),
			array( &$this, 'textarea_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'pakjegemak_description',
				'width'			=> '50',
				'height'		=> '4',
				'default'		=> 'Wilt u uw bestelling liever laten versturen naar een afhaallocatie van PostNL? Maak dan gebruik van de PakjeGemak service.',
			)
		);

		add_settings_field(
			'pakjegemak_button',
			__( 'Tekst op de pakjegemak knop', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'general',
			array(
				'menu'			=> $option,
				'id'			=> 'pakjegemak_button',
				'size'			=> '50',
				'default'		=> 'PakjeGemak afhaallocatie kiezen',
			)
		);

		// Section.
		add_settings_section(
			'default_values',
			__( 'Standaard export instellingen', 'wcmyparcel' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		add_settings_field(
			'shipment_type',
			__( 'Soort zending', 'wcmyparcel' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'shipment_type',
				'default'		=> 'standard',
				'options' 		=> array(
					'standard'		=> __( 'Pakket' , 'wcmyparcel' ),
					'letterbox'		=> __( 'Brievenbuspakje' , 'wcmyparcel' ),
					'unpaid_letter'	=> __( 'Ongefrankeerd label' , 'wcmyparcel' ),
				),
			)
		);

		add_settings_field(
			'email',
			__( 'Koppel emailadres klant', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'email',
				'description'	=> __( 'Wanneer u het emailadres van de klant koppelt, wordt daar een bericht met de Track&Trace link naartoe gemaild vanuit MyParcel. In uw <a href="http://www.myparcel.nl/backend/instellingen/tracktrace">MyParcel instellingen</a> kunt u deze mail opmaken in uw eigen stijl.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'telefoon',
			__( 'Koppel telefoonnummer klant', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'telefoon',
				'description'	=> __( 'Wanneer u het telefoonnummer van de klant koppelt met de zending, kan de koerier dit gebruiken ten behoeve van de aflevering van het pakket. De afleverkans voor buitenlandzendingen wordt hiermee aanzienlijk verhoogd.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'extragroot',
			__( 'Extra groot formaat (+ &euro;2.19)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'extragroot',
				'description'	=> __( 'Vink deze optie aan indien uw pakket groter is dan 100 x 70 x 50 cm, maar kleiner dan 175 x 78 x 58 cm. Er wordt hiervoor een toeslag van &euro;&nbsp;2,00 doorberekend.<br/><strong>Let op!</strong> Indien het pakket groter is dan 175 x 78 x 58 of zwaarder dan 30 kg, dan wordt er een pallettarief van &euro;&nbsp;70,00 in rekening gebracht.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'huisadres',
			__( 'Niet bij buren bezorgen (+ &euro;0.26)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'huisadres',
			)
		);
		
		add_settings_field(
			'handtekening',
			__( 'Handtekening voor ontvangst (+ &euro;0.33)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'handtekening',
				'description'	=> __( 'Hier wordt het pakket allereerst op het huisadres aangeboden. Mocht de geadresseerde niet thuis zijn, dan wordt het pakket bij de buren afgegeven. Er moet in beide gevallen voor worden getekend.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'huishand',
			__( 'Niet bij buren bezorgen + Handtekening voor ontvangst (+ &euro;0.40)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'huishand',
				'description'	=> __( 'Hiermee kiest u voor zekerheid. Het pakket wordt alleen bij de geadresseerde bezorgt die hiervoor tekent. Zo weet u zeker dat het pakket in ontvangst is genomen door de geadresseerde. Een veilig gevoel.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'huishandverzekerd',
			__( 'Niet bij buren bezorgen + Handtekening voor ontvangst + verzekerd tot &euro;50 (+ &euro;0.50)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'huishandverzekerd',
				'description'	=> __( 'Er zit standaard geen verzekering op de zendingen. Indien u uw pakket wilt verzekeren tegen diefstal, schade en verlies kunt u dit bij ons doen voor &euro;&nbsp;0,50. Wij verzekeren uw zending tot een waarde van &euro;&nbsp;50. Wilt u een duurder product verzekeren? Kies dan voor de optie "Verhoogd aansprakelijk".', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'retourbgg',
			__( 'Retour bij geen gehoor', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'retourbgg',
				'description'	=> __( 'Standaard wordt de zending twee keer aangeboden. Na twee mislukte afleverpogingen zal het pakket gedurende drie weken beschikbaar zijn op de dichtstbijzijnde afhaallocatie. Het kan daar door de klant worden opgehaald met het door de koerier achtergelaten briefje. Indien u wilt dat het pakket na twee keer te zijn aangeboden direct retour komt en dus NIET op het postkantoor komt te liggen, vink dan deze optie aan. Let op: Het pakket komt hierdoor eerder retour, waarvoor wij u ook zullen doorbelasten.', 'wcmyparcel' )
			)
		);
		
		add_settings_field(
			'verzekerd',
			__( 'Verhoogd aansprakelijk (+ &euro;1.58 per 500 euro verzekerd)', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'verzekerd',
				'description'	=> __( 'Er zit standaard geen verzekering op de zendingen. Indien u toch wilt verzekeren kunt u dit doen voor &euro;&nbsp;1.45 extra per &euro;&nbsp;500 verzekerd. Wij verzekeren de inkoopwaarde van uw product. Met een maximale verzekerde waarde van &euro;&nbsp;5.000.', 'wcmyparcel' )
			)
		);

		add_settings_field(
			'verzekerdbedrag',
			__( 'Verzekerd bedrag (in euro)', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'verzekerdbedrag',
				'size'			=> '5',
				'description'	=> __( 'Indien u kiest voor verhoogd aansprakelijk, dan kunt u hier de waarde van de inhoud van het pakket vermelden, afgerond op hele euros, zonder kommas punten of valutateken.', 'wcmyparcel' ),
			)
		);

		add_settings_field(
			'kenmerk',
			__( 'Eigen kenmerk', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'kenmerk',
				'description'	=> __( "Met deze optie kunt u een kenmerk aan de zending toevoegen. Deze wordt linksboven op het label geprint en hierop kan later in het overzicht zendingen gezocht of geordend worden. Ook hier kunt u de code <strong>[ORDER_NR]</strong> gebruiken.", 'wcmyparcel' ),
			)
		);

		// Standaard verpakkingsgewicht
		add_settings_field(
			'verpakkingsgewicht',
			__( 'Standaard verpakkingsgewicht (gram)', 'wcmyparcel' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'default_values',
			array(
				'menu'			=> $option,
				'id'			=> 'verpakkingsgewicht',
				'size'			=> '5',
				'description'	=> __( 'Gewicht van uw standaard verpakking, afgerond op hele grammen.', 'wcmyparcel' ),
			)
		);

		// Section.
		add_settings_section(
			'diagnose',
			__( 'Diagnostische opties (alleen inschakelen bij problemen)', 'wcmyparcel' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		/*
		add_settings_field(
			'testmode',
			__( 'Testmodus', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'diagnose',
			array(
				'menu'			=> $option,
				'id'			=> 'testmode',
				'size'			=> '50',
				//'description'	=> __( 'Schakel testmodus in wanneer u de connectie met MyParcel eerst wilt testen', 'wcmyparcel' ),
			)
		);
		*/

		$log_file_url = dirname(plugin_dir_url(__FILE__)) . '/myparcel_log.txt';
		$log_file_path = dirname(dirname(__FILE__)) . '/myparcel_log.txt';

		add_settings_field(
			'error_logging',
			__( 'Log alle server communicatie', 'wcmyparcel' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'diagnose',
			array(
				'menu'			=> $option,
				'id'			=> 'error_logging',
				'description'	=> file_exists($log_file_path)?'<a href="'.$log_file_url.'" target="_blank">Download logbestand</a>':'',
			)
		);

		// Register settings.
		register_setting( $option, $option, array( &$this, 'validate_options' ) );
	}
	
	/**
	 * Set default settings.
	 * 
	 * @return void.
	 */
	public function default_settings() {
		$default = array(
			'process'			=> '1',
			'download_display'	=> 'download',
			'email'				=> '1',
			'telefoon'			=> '1',
			'extragroot'		=> '0',
			'huisadres'			=> '0',
			'handtekening'		=> '0',
			'huishand'			=> '0',
			'huishandverzekerd'	=> '0',
			'retourbgg'			=> '0',
			'verzekerd'			=> '0',
			'verzekerdbedrag'	=> '0',
			'kenmerk'			=> '',
			'bericht'			=> '',
			'verpakkingsgewicht'=> '0',
			);
	
		add_option( 'wcmyparcel_settings', $default );
	}
	
	
	// Text element callback.
	public function text_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
		$size = isset( $args['size'] ) ? $args['size'] : '25';
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
	
		$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"/>', $id, $menu, $current, $size );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		echo $html;
	}

	public function textarea_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
		$width = $args['width'];
		$height = $args['height'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
	
		$html = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" cols="%4$s" rows="%5$s"/>%3$s</textarea>', $id, $menu, $current, $width, $height );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		echo $html;
	}
	
	/**
	 * Checkbox field fallback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string      Checkbox field.
	 */
	public function checkbox_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
	
		$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1"%3$s />', $id, $menu, checked( 1, $current, false ) );
	
		//$html .= sprintf( '<label for="%s"> %s</label><br />', $id, __( 'Activate/Deactivate', 'wcmyparcel' ) );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		echo $html;
	}
	
	/**
	 * Select element fallback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string      Select field.
	 */
	public function select_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = sprintf( '<select id="%1$s" name="%2$s[%1$s]">', $id, $menu );
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
		}
		$html .= '</select>';
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		echo $html;
	}
	
	/**
	 * Displays a radio settings field
	 *
	 * @param array   $args settings field args
	 */
	public function radio_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = '';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
			$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
		}
		
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}

		echo $html;
	}

	/**
	 * Section null fallback.
	 *
	 * @return void.
	 */
	public function section_options_callback() {
	
	}
	
	/**
	 * Validate options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array        validated options.
	 */
	public function validate_options( $input ) {
		// Create our array for storing the validated options.
		$output = array();
	
		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {
	
			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[$key] ) ) {
	
				// Strip all HTML and PHP tags and properly handle quoted strings.
				$output[$key] = strip_tags( stripslashes( $input[$key] ) );
			}
		}
	
		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'wcmyparcel_validate_input', $output, $input );
	}

}