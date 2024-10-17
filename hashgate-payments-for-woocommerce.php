<?php
/**
 * Plugin Name: HashGate Payments
 * Plugin URI: https://github.com/HashGateApp/hashgate-woocommerce
 * Description: Adds the option to accept payment with HashGate
 * Version: 1.0.1
 *
 * Author: HashGate
 * Author URI: https://hashgate.app/
 *
 * Text Domain: woocommerce-gateway-hashgate
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 6.6
 *
 * Copyright: © 2009-2024 Automattic.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC Dummy Payment gateway plugin class.
 *
 * @class HashGatePaymentsPlugin
 */
class HashgatePaymentsPlugin
{

    /**
     * Plugin bootstrapping.
     */
    public static function init()
    {

        // Dummy Payments gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // Make the Dummy Payments gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', array(__CLASS__, 'woocommerce_gateway_hashgate_woocommerce_block_support'));

    }

    /**
     * Add the Dummy Payment gateway to the list of available gateways.
     *
     * @param array
     */
    public static function add_gateway($gateways)
    {
        $gateways[] = 'HashGatePaymentGateway';

        return $gateways;
    }

    /**
     * Plugin includes.
     */
    public static function includes()
    {
        require_once 'includes/class-hashgate-payment-gateway.php';
        require_once 'includes/class-hashgate-webhook.php';
        new HashgateWebhook();
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath()
    {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function woocommerce_gateway_hashgate_woocommerce_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'includes/blocks/class-hashgate-payments-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new HashGatePaymentsBlocks());
                }
            );
        }
    }
}

HashgatePaymentsPlugin::init();