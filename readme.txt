=== HashGate Payment Gateway for WooCommerce ===
Contributors: hashgate
Plugin URL: https://hashgate.app/
Tags: woo, woocommerce, ecommerce, blockchain, hashgraph, crypto, cryptocurrency, payments, gateway, tokens
Requires at least: 3.0
Requires PHP: 5.6+
Tested up to: 6.4.1
Stable tag: 1.0
License: GPLv2 or later

== Description ==

Accept tokens through HashGate such as HBAR, USDC, USDT, and many more on your WooCommerce store.

== Installation ==

= From this repo =
1. Download HashGate Payment Gateway on the releases section.
2. Upload to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate HashGate Payments from your Plugins page.

= Once Activated =

1. Go to WooCommerce > Settings > Payments
2. Configure the plugin for your store

= Configuring HashGate Payments =

* You will need to set up an account on https://hashgate.app/
* Within the WordPress administration area, go to the WooCommerce > Settings > Payments page and you will see HashGate Payments in the table of payment gateways.
* Clicking the Manage button on the right hand side will take you into the settings page, where you can configure the plugin for your store.

**Note: If you are running version of WooCommerce older than 3.4.x your HashGate Payments tab will be underneath the WooCommerce > Settings > Checkout tab**

= Enable / Disable =

Turn the HashGate Payments payment method on / off for visitors at checkout.

= Title =

Title of the payment method on the checkout page

= Description =

Description of the payment method on the checkout page

= API Key =

Your HashGate API key. Available within https://hashgate.app/account/settings/api

Using an API key allows your website to create charges and recieve updates from HashGate on Payment Status.

= Webhook Endpoint =

Using webhooks are required for HashGate to be able to send payment confirmation messages to this website. To fill this out:

1. In your HashGate API settings page, scroll to the 'Webhook URL' section.
2. Paste the webhook endpoint into the webhook url. 
3. Click Save.

= Debug log =

Whether or not to store debug logs.

If this is checked, these are saved within your `wp-content/uploads/wc-logs/` folder in a .log file prefixed with `hashgate-`

= Prerequisites=

To use this plugin with your WooCommerce store you will need:
* WooCommerce plugin