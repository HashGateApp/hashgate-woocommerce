<?php
/**
 * WC_Gateway_Dummy class
 *
 * @author   SomewhereWarm <info@somewherewarm.gr>
 * @package  WooCommerce Dummy Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dummy Gateway.
 *
 * @class    HashGatePaymentGateway
 * @version  1.10.0
 */
class HashGatePaymentGateway extends WC_Payment_Gateway {

	/**
	 * Payment gateway instructions.
	 * @var string
	 *
	 */
	protected $instructions;

	/**
	 * Unique id for the gateway.
	 * @var string
	 *
	 */
	public $id = 'hashgate';

    /**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		
		$this->icon               = apply_filters( 'hashgate_icon', HashgatePaymentsPlugin::plugin_abspath() . '\assets\images\logo.png', 'hashgate');
		$this->has_fields         = true;
		$this->supports           = array(
			'products'
		);

		$this->method_title       = _x( 'HashGate', 'HashGate payment method', 'hashgate' );
		$this->method_description = __( 'HashGate redirects to off-site checkout', 'hashgate' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->instructions             = $this->get_option( 'instructions', $this->description );
		$this->personal_access_token    = $this->get_option( 'personal_access_token' );
        $this->webhook_secret           = $this->get_option( 'webhook_secret' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        // Register the webhook
        new HashgateWebhook();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'hashgate' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable HashGate Payments', 'hashgate' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'hashgate' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'hashgate' ),
				'default'     => _x( 'HashGate', 'HashGate payment method', 'hashgate' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'hashgate' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'hashgate' ),
				'default'     => __( 'Complete your payment with HashGate', 'hashgate' ),
				'desc_tip'    => true,
			),
            'personal_access_token' => array(
                'title'       => 'Personal Access Token',
                'type'        => 'password'
            ),
            'webhook_url' => array(
                'title'             =>  'Webhook URL',
                'type'              =>  'text',
                'default'           =>  site_url() . '?rest_route=/hashgate/v1/callback',
                'custom_attributes' =>  array( 'readonly' => 'readonly' )
            ),
            'webhook_secret' => array(
                'title' => 'Webhook Secret',
                'type' => 'password'
            )
		);
	}


    public function process_admin_options() {
        parent::process_admin_options();
        $error = false;

        if(empty($_POST['woocommerce_hashgate_personal_access_token'])) {
            $error = true;
            WC_Admin_Settings::add_error( 'You must include a personal access token for HashGate Payments to work.' );
        }

        if(empty($_POST['woocommerce_hashgate_webhook_secret'])) {
            $error = true;
            WC_Admin_Settings::add_error( 'Provide your webhook secret so HashGate Payments can update your processed orders.' );
        }

        return $error;
    }

    public function is_available()
    {
        return parent::is_available() && !empty($this->personal_access_token) && !empty($this->webhook_secret);
    }

    /**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

        $description = "";
        foreach ($order->get_items() as $item) {
            $description .= $item->get_quantity() . "x " . $item->get_name() . PHP_EOL;
        }

        $payload = array(
            'name' => get_bloginfo('name') . ' - Order #' . $order_id,
            'description' => $description,
            'currency' => $order->get_currency(),
            'currency_amount' => $order->get_total(),
            'success_url' => $this->get_return_url($order),
            'encrypted_data' => array(
                'wc_order_id' => $order_id,
                'wc_order_key' => $order->get_order_key()
            )
        );

        $response = wp_remote_post(
            "https://api.hashgate.app/v1/charges",
            array(
                'method' => "POST",
                'headers' => array(
                    'Authorization' => "Bearer " . $this->personal_access_token
                ),
                'body' => $payload,
                'timeout' => 45
            )
        );

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == 201) {
            $charge = json_decode($response['body']);

            $order->update_status('on-hold', __( 'Awaiting HashGate payment', 'hashgate' ));

            $order->add_meta_data('hashgate_charge_id', $charge->id);
            $order->save_meta_data();

            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $charge->charge_url
            );
        }

        return array(
            'result' => 'failure',
            'message' => 'Unable to create a payment with HashGate [Response Code: ' .$response_code . ']',
            'data' => json_encode($response)
        );
	}

}


add_filter( 'woocommerce_payment_gateways', function ($gateways) {
    // Register our gateway
    $gateways[] = "HashGatePaymentGateway";

    return $gateways;
});