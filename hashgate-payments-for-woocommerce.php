<?php
/**
 * Plugin Name: HashGate Payments
 * Plugin URI: https://github.com/HashGateApp/hashgate-woocommerce
 * Description: Adds the option to accept payment with HashGate
 * Version: 2.1.1
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

    public function __construct() {
        if( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            add_action( 'admin_notices', array( $this, 'missing_woocommerce' ) );
            return;
        }

        // Initialize the plugin
        $this->init();
    }


    /**
     * Plugin bootstrapping.
     */
    public function init()
    {
        $this->includes();
        $this->hooks();
    }

    /**
     * Plugin includes.
     */
    public function includes()
    {
        require_once 'includes/class-hashgate-webhook.php';
        require_once 'includes/class-hashgate-payment-gateway.php';
    }

    public function hooks()
    {
        add_action('woocommerce_blocks_loaded', array($this, 'hashgate_block_support'));

        add_filter( 'plugin_action_links_'. plugin_basename(__FILE__),
            function($links) {
                $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=hashgate">Settings</a>';
                array_unshift($links, $settings_link);
                return $links;
            },
            10
        );
    }

    public function missing_woocommerce() {
        deactivate_plugins(plugin_basename(__FILE__));
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'In order to use HashGate Payments for WooCommerce, make sure WooCommerce is installed and active.', 'hashgate' ); ?></p>
        </div>
        <?php
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
    public function hashgate_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'includes/blocks/class-hashgate-payments-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function ($payment_method_registry) {
                    $payment_method_registry->register(new HashGatePaymentsBlocks);
                }
            );
        }
    }
}

add_action( 'plugins_loaded', function() {
    new HashgatePaymentsPlugin();
});