<?php
class HashgateWebhook
{
    protected $gateway;

    protected $name = 'hashgate';
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function register_rest_routes()
    {
        $this->gateway = WC()->payment_gateways->payment_gateways()[$this->name];

        register_rest_route('hashgate/v1', '/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_webhook'),
            'permission_callback' => function() { return true; }
        ));
    }

    public function process_webhook()
    {
        $body = @file_get_contents("php://input");

        if (!$this->validate_webhook($body)) {
            wp_send_json_error(array (
                'message' => "Unable to verify message signature"
            ), 403);
        };

        $payload = json_decode($body);

        try {
            $order_id = $payload->event->data->encrypted_data->wc_order_id;

            $order = new WC_Order($order_id);

            switch ($payload->event->type) {
                case "charge:paid":
                    $order->update_status('processing', __('HashGate payment was successfully processed.', 'hashgate'));
                    $order->payment_complete();
                    break;
                case "charge:expired":
                    $order->update_status('cancelled', __('HashGate payment expired.', 'hashgate'));
                    break;
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ), 400);
        }
    }

    private function validate_webhook($body)
    {
        if (is_null($body)) return false;

        if (!isset($_SERVER['HTTP_X_MESSAGE_DIGEST'])) {
            return false;
        }

        $serverSignature = $_SERVER['HTTP_X_MESSAGE_DIGEST'];

        $calculatedSignature = hash_hmac('sha256', $body, $this->gateway->webhook_secret);

        return $serverSignature == $calculatedSignature;
    }
}