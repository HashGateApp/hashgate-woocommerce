<?php
/**
 * HashGate Payment Gateway.
 *
 * Provides a HashGate Payment Gateway.
 *
 * @class       WC_Gateway_HashGate
 * @extends     WC_Payment_Gateway
 * @since       1.0.0
 * @package     WooCommerce/Classes/Payment
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_HashGate Class.
 */
class WC_Gateway_HashGate extends WC_Payment_Gateway
{

    /**
     * Log_enabled - whether or not logging is enabled
     *
     * @var bool    Whether or not logging is enabled
     */
    public static $log_enabled = false;

    /**
     * WC_Logger Logger instance
     *
     * @var WC_Logger Logger instance
     * */
    public static $log = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'hashgate';
        $this->has_fields = false;
        $this->order_button_text = __('Pay with HashGate', 'hashgate');
        $this->method_title = __('HashGate Payments', 'hashgate');
        $this->method_description = '<p>' .
            __('A payment gateway that sends your customers to HashGate to pay with hbar.', 'hashgate')
            . '</p><p>' .
            sprintf(
                __('If you do not currently have a HashGate account, you can set one up here: %s', 'hashgate'),
                '<a target="_blank" href="https://hashgate.app/">https://hashgate.app/</a>'
            );

        // Checkouts expire after 1 hour
        $this->timeout = (new WC_DateTime())->sub(new DateInterval('PT1H'));

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->update_option('webhook_secret', add_query_arg('wc-api', 'WC_Gateway_HashGate', home_url('/', 'https')));

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->debug = 'yes' === $this->get_option('debug', 'no');

        self::$log_enabled = $this->debug;

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Register the webhook
        add_action('woocommerce_api_wc_gateway_hashgate', array($this, 'handle_webhook'));
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     */
    public static function log($message, $level = 'info')
    {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }

            self::$log->log($level, $message, array('source' => 'hashgate'));
        }
    }

    /**
     * Retrieve the access token from the cache
     *
     * @return string Access Token
     */
    public function get_access_token()
    {
        return get_transient('_hashgate_access_token');
    }

    /**
     * Store the access token in the cache
     *
     * @param $token
     * @param $expiration
     * @return void
     */
    public function set_access_token($token, $expiration)
    {
        set_transient('_hashgate_access_token', $token, $expiration);
    }

    /**
     * Get gateway icon.
     *
     * @return string
     */
    public function get_icon()
    {
        if ($this->get_option('show_icons') === 'no') {
            return '';
        }

        $image_path = plugin_dir_path(__FILE__) . 'assets/images';
        $icon_html = '';
        $methods = get_option('hashgate_payment_methods', array('hbar'));

        // Load icon for each available payment method.
        foreach ($methods as $m) {
            $path = realpath($image_path . '/' . $m . '.png');
            if ($path && dirname($path) === $image_path && is_file($path)) {
                $url = WC_HTTPS::force_https_url(plugins_url('/assets/images/' . $m . '.png', __FILE__));
                $icon_html .= '<img width="26" src="' . esc_attr($url) . '" alt="' . esc_attr__($m, 'hashgate') . '" />';
            }
        }

        /** DOCBLOCK - Makes linter happy.
         *
         * @since today
         */
        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable HashGate Payments', 'hashgate'),
                'default' => 'yes',
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('HashGate Payments', 'hashgate'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default' => __('Pay with Hbar', 'hashgate'),
            ),
            'api_key' => array(
                'title' => __('API Key', 'hashgate'),
                'type' => 'text',
                'default' => '',
                'description' => sprintf(
                    __(
                        'You can manage your API keys within the HashGate Settings page, available here: %s',
                        'hashgate'
                    ),
                    esc_url('https://hashgate.app/account/settings/api')
                ),
            ),
            'webhook_secret' => array(
                'title' => __('Webhook Endpoint', 'hashgate'),
                'type' => 'text',
                'value' => add_query_arg('wc-api', 'WC_Gateway_HashGate', home_url('/', 'https')),
                'description' =>

                // translators: Instructions for setting up 'webhook shared secrets' on settings page.
                    __('Using webhooks allows HashGate to send payment confirmation messages to the website.', 'hashgate')

                    . '<br /><br />' .

                    // translators: Step 1 of the instructions for 'webhook shared secrets' on settings page.
                    __('1. In your HashGate settings page, click the  \'API Settings\' section', 'hashgate')

                    . '<br />' .

                    // translators: Step 2 of the instructions for 'webhook shared secrets' on settings page. Includes webhook URL.
                    __('2. Under \'Webhook Endpoint\', copy and paste the Webhook Endpoint from the box above', 'hashgate')

                    . '<br />' .

                    // translators: Step 3 of the instructions for 'webhook shared secrets' on settings page.
                    __('3. Click Save', 'hashgate')

            ),
            'debug' => array(
                'title' => __('Debug log', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'woocommerce'),
                'default' => 'no',
                // translators: Description for 'Debug log' section of settings page.
                'description' => sprintf(__('Log HashGate API events inside %s', 'hashgate'), '<code>' . WC_Log_Handler_File::get_log_file_path('hashgate') . '</code>'),
            ),
        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Create description for charge based on order's products. Ex: 1 x Product1, 2 x Product2
        try {
            $order_items = array_map(function ($item) {
                return $item['quantity'] . ' x ' . $item['name'];
            }, $order->get_items());

            $description = mb_substr(implode(', ', $order_items), 0, 200);
        } catch (Exception $e) {
            $description = null;
        }

        $this->init_api();

        // Set the order metadata
        $metadata = array(
            'order_id' => $order->get_id(),
            'order_key' => $order->get_order_key(),
            'source' => 'woocommerce'
        );

        // Create the charge
        $result = HashGate_API_Handler::create_charge(
            $order->get_total(),
            get_woocommerce_currency(),
            $metadata,
            $this->get_return_url($order),
            null,
            $description,
            $this->get_cancel_url($order)
        );

        if (!$result) {
            return array('result' => 'fail');
        }

        // Add the charge_id to the order
        $order->update_meta_data('_hashgate_charge_id', $result['id']);
        $order->update_meta_data('_hashgate_status', 'PENDING');

        $order->save();

        return array(
            'result' => 'success',
            'redirect' => $result['charge_url'],
        );
    }

    /**
     * Get the cancel url.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    public function get_cancel_url($order)
    {
        $return_url = $order->get_cancel_order_url();

        if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
            $return_url = str_replace('http:', 'https:', $return_url);
        }

        return apply_filters('woocommerce_get_cancel_url', $return_url, $order);
    }


    /**
     * Handle requests sent to webhook.
     */
    public function handle_webhook()
    {
        $payload = file_get_contents('php://input');

        // Make sure the payload isn't empty
        if (empty($payload)) {
            self::log('Webhook Payload Empty');
            exit;
        }

        // Make sure the webhook is valid
        if (!$this->validate_webhook($payload)) {
            self::log('Webhook Invalid Signature Empty');
            exit;
        }

        $data = json_decode($payload, true);

        // Log the webhook event
        self::log('Webhook received event: ' . print_r($data, true));

        // Check to see if the order id exists
        if (!isset($data['event']['data']['encrypted_data']['order_id'])) {
            exit;
        }

        // Retrieve the order_id
        $order_id = $data['event']['data']['encrypted_data']['order_id'];

        // Update the order information
        $this->update_order_status(wc_get_order($order_id), $data);

        exit;
    }

    /**
     * Check webhook request is valid.
     *
     * @param string $payload
     */
    public function validate_webhook($payload)
    {
        self::log('Checking Webhook response is valid');

        if (!isset($_SERVER['HTTP_X_MESSAGE_DIGEST'])) {
            return false;
        }

        $sig = $_SERVER['HTTP_X_MESSAGE_DIGEST'];

        $sig2 = hash_hmac('sha256', $payload, hash('sha256', $this->get_option('api_key')));

        if ($sig === $sig2) {
            return true;
        }

        return false;
    }

    /**
     * Init the API class and set the API key etc.
     */
    protected function init_api()
    {
        // Retrieve the API Handler
        include_once dirname(__FILE__) . '/includes/class-hashgate-api-handler.php';

        // Set the variables
        HashGate_API_Handler::$log = get_class($this) . '::log';

        // Bootstrap the access token
        if (!$this->get_access_token()) {
            // Set the API Key
            HashGate_API_Handler::$api_key = $this->get_option('api_key');

            // Request a new access_token
            $response = HashGate_API_Handler::authenticate();

            // Check to see if the call was successful
            if(!$response) {
                self::log('HashGate API: Unable to authenticate. Check your API Key');
                return;
            }

            // Store the access token
            $this->set_access_token($response['access_token'], $response['expires_in']-5);
        }

        // Set the access token
        HashGate_API_Handler::$access_token = $this->get_access_token();
    }

    /**
     * @param WC_Order $order
     * @param $payload
     * @return void
     */
    public function update_order_status($order, $payload)
    {
        // Update the meta
        $order->update_meta_data('_hashgate_status', $payload['event']['type']);

        self::log("Event Type: " . $payload['event']['type']);

        switch ($payload['event']['type']) {
            case 'charge:create':
                break;
            case 'charge:paid':
                self::log("Charge Paid");
                $order->update_status('processing', __('HashGate payment was successfully processed.', 'hashgate'));
                $order->payment_complete();
                break;
            case 'charge:expired':
                $order->update_status('cancelled', __('HashGate payment expired.', 'hashgate'));
                break;
            case 'charge:refunded':
                $order->update_status('refunded', __('Payment was refunded on HashGate.', 'hashgate'));
                break;
        }

        $order->save();
    }
}
