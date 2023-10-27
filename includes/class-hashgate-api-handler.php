<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sends API requests to HashGate.
 */
class HashGate_API_Handler
{

    /**
     * Log variable function
     *
     * @var string/array Log variable function.
     * */
    public static $log;

    /**
     * Call the $log variable function.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     */
    public static function log($message, $level = 'info')
    {
        return call_user_func(self::$log, $message, $level);
    }

    public static $api_url = 'https://api.hashgate.app';
    public static $api_key;
    public static $access_token;

    public static function call(
        $method,
        $endpoint,
        $payload = [],
        $headers = []
    ) {
        self::log('------====[ HashGate Payments: Start ]====------');
        self::log('Method: ' . $method);
        self::log("Endpoint: " . $endpoint);
        self::log("Headers: " . print_r($headers, true));
        self::log("Payload: " . print_r($payload, true));

        $headers['Content-Type'] = 'application/json';

        $options = [
            'method' => $method,
            'headers' => $headers,
        ];

        $url = self::$api_url . $endpoint;

        self::log("Endpoint: " . $url);

        if ($method == "GET") {
           $url = add_query_arg($payload, $url);
        }

        if ($method == "POST") {
            $options['body'] = json_encode($payload);
        }

        $url = esc_url_raw($url);

        self::log("Full URL: " . $url);

        // Send the request to the server
        $response = wp_remote_request($url, $options);

        // Check for any exceptions
        if (is_wp_error($response)) {
            self::log("Request threw error: " . $response->get_error_message());
            return null;
        }

        // Parse the response body
        $data = json_decode($response['body'], true);

        // Get the response code
        $code = $response['response']['code'];

        // Handle errors
        if (!in_array($code, array(200, 201), true)) {
            self::log("Error: " . print_r($data, true));

            $errors = array(
                400 => 'Error response from API: ' . $data['message'],
                401 => 'Authentication error, please check your API key.',
                405 => 'Invalid method sent',
                422 => 'The request was malformed.',
                429 => 'API rate limit exceeded.'
            );

            if($code == 401) {
                delete_transient('_hashgate_access_token');
            }

            if (array_key_exists($code, $errors)) {
                $msg = $errors[$code];
            } else {
                $msg = 'Unknown response from API: ' . $code;
            }

            self::log($msg);

            return null;
        }

        self::log('Response: ' . print_r($data, true));
        self::log('------====[ HashGate Payments: Complete ]====------');

        return $data;
    }

    /**
     * Authenticate the application with HashGate
     *
     * @return array
     */
    public static function authenticate()
    {
        $endpoint = '/v1/auth/token';

        $headers = [
            'X-Authorization' => self::$api_key
        ];

        return self::call('GET', $endpoint, [], $headers);
    }


    /**
     * Create a new charge request.
     *
     * @param int $amount
     * @param string $currency
     * @param array $metadata
     * @param string $redirect
     * @param string $name
     * @param string $desc
     * @param string $cancel
     * @return array
     */
    public static function create_charge(
        $amount = null,
        $currency = null,
        $metadata = null,
        $redirect = null,
        $name = null,
        $desc = null,
        $cancel = null
    ) {
        return self::call(
            'POST',
            '/v1/charges',
            [
                'name' => sanitize_text_field(is_null($name) ? get_bloginfo('name') : $name),
                'description' => sanitize_text_field(is_null($desc) ? get_bloginfo('description') : $desc),
                'currency' => $currency,
                'currency_amount' => $amount,
                'encrypted_data' => $metadata,
                'success_url' => $redirect ?? null,
                'cancel_url' => $cancel ?? null
            ],
            [
                'Authorization' => 'Bearer ' . self::$access_token
            ]
        );
    }
}
