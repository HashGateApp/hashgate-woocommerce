<?php
/*
Plugin Name:  HashGate Payments
Plugin URI:   https://github.com/HashGateApp/hashgate-woocommerce/
Description:  A payment gateway that allows your customers to pay with Hbar via HashGate (https://hashgate.app)
Version:      1.0
Author:       HashGate, LLC
Author URI:   https://hashgate.app/
License:      GPLv3+
License URI:  https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:  hashgate
Domain Path:  /languages

WC requires at least: 3.0.9
WC tested up to: 6.5.1

HashGate is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

HashGate is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with HashGate. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/


add_action('plugins_loaded', function () {
    // Validate that woocommerce is loaded
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        require_once 'class-wc-gateway-hashgate.php';

        // Register the Payment Gateway
        add_filter('woocommerce_payment_gateways', function ($methods) {
            $methods[] = 'WC_Gateway_HashGate';
            return $methods;
        });

        // Add the HashGate into to emails
        add_filter('woocommerce_email_order_meta_fields',
            function ($fields, $sent_to_admin, $order) {
                if ($order->get_payment_method() == 'hashgate') {
                    $fields['hashgate_reference'] = array(
                        'label' => __('HashGate Reference #'),
                        'value' => $order->get_meta('_hashgate_charge_id'),
                    );
                }

                return $fields;
            },
            10,
            3
        );

        add_action('woocommerce_admin_order_data_after_order_details', 'hg_order_meta_general');
        add_action('woocommerce_order_details_after_order_table', 'hg_order_meta_general');
    }
});


/**
 * Add order meta after General and before Billing
 *
 * @param WC_Order $order WC order instance
 */
function hg_order_meta_general($order)
{
    if ($order->get_payment_method() == 'hashgate') {
        ?>

        <br class="clear"/>
        <h3>HashGate Payment Information</h3>
        <div class="">
            <p>Reference # <?php echo esc_html($order->get_meta('_hashgate_charge_id')); ?></p>
        </div>

        <?php
    }
}
