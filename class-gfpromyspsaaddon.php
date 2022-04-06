<?php

GFForms::include_feed_addon_framework();

class GFPromysPSAAddOn extends GFFeedAddOn {

	protected $_version = GF_PROMYS_PSA_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9.16';
	protected $_slug = 'promyspsaaddon';
	protected $_path = 'promyspsaaddon/promyspsaaddon.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Promys PSA Add-On';
	protected $_short_title = 'Promys PSA Add-On';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFPromysPSAAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFPromysPSAAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	/*
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to service x only when payment is received.', 'promyspsaaddon' )
			)
		);

	}
	*/

	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed e.g. subscribe the user to a list.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool|void
	 */
	public function process_feed( $feed, $entry, $form ) {
		$feedName  = $feed['meta']['feedName'];
		/*
		$mytextbox = $feed['meta']['mytextbox'];
		$checkbox  = $feed['meta']['mycheckbox'];
		*/
		// Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = array();
		foreach ( $field_map as $name => $field_id ) {

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $this->get_field_value( $form, $entry, $field_id );

		}

		$settings = $this->get_plugin_settings();

		// Access a specific setting e.g. an api key
		$baseurl = rgar( $settings, 'baseurl' );
		$username = rgar( $settings, 'username' );
		$password = rgar( $settings, 'password' );

		// The data to send to the API
		$postData = array(
			'Username' => $username,
			'Password' => $password,
		);

		$cookieFile = tempnam( '/tmp', 'CURLCOOKIE' );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookieFile );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookieFile );

		curl_setopt( $ch, CURLOPT_URL, $baseurl . '/Account/LogOn' );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		# Setup request to send json via POST.
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($postData) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json'] );
		# Return response instead of printing.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		# Send request.
		$response = curl_exec($ch);
		curl_close($ch);

		// Decode the response
		$responseData = json_decode($response, TRUE);
		if (!is_null($responseData) && !is_null($responseData['Messages']))
		{
			foreach ( $responseData['Messages'] as $message )
			{
				if ($message['MsgType'] == 'Error')
				{
					die($message['MsgType'] . '. ' . $message['MsgText']);
				}
			}
		}

		// Create the context for the request
		$postData = array(
			'CompanyName' => $merge_vars['company'],
			'ContactName1' => $merge_vars['name'],
			'StreetAddress' => $merge_vars['street'],
			'CityProvinceCountry' => $merge_vars['city'] . ', '
				. $merge_vars['state_province_region'] . ', '
				. $merge_vars['zip_postal_code'] . ', '
				. $merge_vars['country'],
			'ContactTitle1' => $merge_vars['title'],
			'ContactPhone1' => $merge_vars['phone'],
			'ContactEmail1' => $merge_vars['email'],
			'IsActive' => true,
		);
		# print 'Post data: ';
		# print_r(json_encode($postData));

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookieFile );
		curl_setopt( $ch, CURLOPT_URL, $baseurl . '/Sales/Lead/Add' );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		# Setup request to send json via POST.
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($postData) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json'] );
		# Return response instead of printing.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		# Send request.
		$response = curl_exec($ch);
		curl_close($ch);

		// Decode the response
		$responseData = json_decode($response, TRUE);
		if (!is_null($responseData) && !is_null($responseData['Messages']))
		{
			foreach ( $responseData['Messages'] as $message )
			{
				if ($message['MsgType'] == 'Error')
				{
					die($message['MsgType'] . '. ' . $message['MsgText']);
				}
			}
		}
	}

	/**
	 * Custom format the phone type field values before they are returned by $this->get_field_value().
	 *
	 * @param array $entry The Entry currently being processed.
	 * @param string $field_id The ID of the Field currently being processed.
	 * @param GF_Field_Phone $field The Field currently being processed.
	 *
	 * @return string
	 */
	/*
	public function get_phone_field_value( $entry, $field_id, $field ) {

		// Get the field value from the Entry Object.
		$field_value = rgar( $entry, $field_id );

		// If there is a value and the field phoneFormat setting is set to standard reformat the value.
		if ( ! empty( $field_value ) && $field->phoneFormat == 'standard' && preg_match( '/^\D?(\d{3})\D?\D?(\d{3})\D?(\d{4})$/', $field_value, $matches ) ) {
			$field_value = sprintf( '%s-%s-%s', $matches[1], $matches[2], $matches[3] );
		}

		return $field_value;
	}
	*/
	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'promys_script_js',
				'src'     => $this->get_base_url() . '/js/promys_script.js',
				'version' => $this->_version,
				/*
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'promyspsaaddon' ),
					'second' => esc_html__( 'Second Choice', 'promyspsaaddon' ),
					'third'  => esc_html__( 'Third Choice', 'promyspsaaddon' ),
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'promyspsaaddon',
					),
				),
				*/
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => 'promys_styles_css',
				'src'     => $this->get_base_url() . '/css/promys_styles.css',
				'version' => $this->_version,
				/*
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) ),
				),
				*/
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Creates a custom page for this add-on.
	 */
	public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Promys PSA Add-On Settings', 'promyspsaaddon' ),
				'fields' => array(
					array(
						'name'    => 'baseurl',
						'tooltip' => esc_html__( 'Promys PSA base URL', 'promyspsaaddon' ),
						'label'   => esc_html__( 'Promys PSA base URL', 'promyspsaaddon' ),
						'type'    => 'text',
						'class'   => 'small',
					),
					array(
						'name'    => 'username',
						'tooltip' => esc_html__( 'Promys PSA user name', 'promyspsaaddon' ),
						'label'   => esc_html__( 'Promys PSA user name', 'promyspsaaddon' ),
						'type'    => 'text',
						'class'   => 'small',
					),
					array(
						'name'    => 'password',
						'tooltip' => esc_html__( 'Promys PSA user password', 'promyspsaaddon' ),
						'label'   => esc_html__( 'Promys PSA user password', 'promyspsaaddon' ),
						'type'    => 'text',
						'class'   => 'small',
					),
				),
			),
		);
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Simple Feed Add-On area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Promys Lead Settings', 'promyspsaaddon' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Feed name', 'promyspsaaddon' ),
						'type'    => 'text',
						'name'    => 'feedName',
						'tooltip' => esc_html__( 'This is the tooltip', 'promyspsaaddon' ),
						'class'   => 'small',
					),
					/*
					array(
						'label'   => esc_html__( 'Textbox', 'promyspsaaddon' ),
						'type'    => 'text',
						'name'    => 'mytextbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'promyspsaaddon' ),
						'class'   => 'small',
					),
					array(
						'label'   => esc_html__( 'My checkbox', 'promyspsaaddon' ),
						'type'    => 'checkbox',
						'name'    => 'mycheckbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'promyspsaaddon' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'promyspsaaddon' ),
								'name'  => 'mycheckbox',
							),
						),
					),
					*/
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'promyspsaaddon' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'     => 'company',
								'label'    => esc_html__( 'Company', 'promyspsaaddon' ),
								'required' => 1,
							),
							array(
								'name'     => 'name',
								'label'    => esc_html__( 'Name', 'promyspsaaddon' ),
								'required' => 1,
							),
							array(
								'name'     => 'title',
								'label'    => esc_html__( 'Title', 'promyspsaaddon' ),
								'required' => 0,
							),
							array(
								'name'       => 'street',
								'label'      => esc_html__( 'Street', 'promyspsaaddon' ),
								'required'   => 0,
							),
							array(
								'name'       => 'city',
								'label'      => esc_html__( 'City', 'promyspsaaddon' ),
								'required'   => 0,
							),
							array(
								'name'       => 'state_province_region',
								'label'      => esc_html__( 'State / Province / Region', 'promyspsaaddon' ),
								'required'   => 0,
							),
							array(
								'name'       => 'zip_postal_code',
								'label'      => esc_html__( 'ZIP / Postal Code', 'promyspsaaddon' ),
								'required'   => 0,
							),
							array(
								'name'       => 'country',
								'label'      => esc_html__( 'Country', 'promyspsaaddon' ),
								'required'   => 0,
							),
							array(
								'name'       => 'phone',
								'label'      => esc_html__( 'Phone', 'promyspsaaddon' ),
								'required'   => 0,
								'field_type' => 'phone',
							),
							array(
								'name'       => 'email',
								'label'      => esc_html__( 'Email', 'promyspsaaddon' ),
								'required'   => 0,
								'field_type' => 'email',
								'tooltip' => esc_html__( 'Email address', 'promyspsaaddon' ),
							),
						),
						'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'promyspsaaddon' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective Promys lead fields.', 'promyspsaaddon' )
					),
					array(
						'name'           => 'condition',
						'label'          => esc_html__( 'Condition', 'promyspsaaddon' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'promyspsaaddon' ),
						'instructions'   => esc_html__( 'Process Promys Lead feed if', 'promyspsaaddon' ),
					),
				),
			),
		);
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__( 'Name', 'promyspsaaddon' ),
			/*
			'mytextbox' => esc_html__( 'My Textbox', 'promyspsaaddon' ),
			*/
		);
	}

	/**
	 * Format the value to be displayed in the mytextbox column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	/*
	 public function get_column_value_mytextbox( $feed ) {
		return '<b>' . rgars( $feed, 'meta/mytextbox' ) . '</b>';
	}
	*/
	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// Access a specific setting e.g. an api key
		$baseurl = rgar( $settings, 'baseurl' );
		$username = rgar( $settings, 'username' );
		$password = rgar( $settings, 'password' );

		return $baseurl != '' && $username != '' && $password != '';
	}

}
