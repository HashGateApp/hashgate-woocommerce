# HashGate Payment Gateway for WooCommerce

Accept tokens through HashGate such as HBAR, USDC, USDT, and many more on your WooCommerce store.

## Installation


### From this repository

Within the Github repository, click the Clone or Download button and Download a zip file of the repository, or clone it directly via command line.

Within your WordPress administration panel, go to Plugins > Add New and click the Upload Plugin button on the top of the page.

Alternatively, you can move the zip file into the `wp-content/plugins` folder of your website and unzip.

You will then need to go to your WordPress administration Plugins page, and activate the plugin.


## Configuring HashGate Payments

You will need to set up an account on [HashGate].

Within the WordPress administration area, go to the WooCommerce > Settings > Payments page and you will see HashGate Payments in the table of payment gateways.

Clicking the Manage button on the right hand side will take you into the settings page, where you can configure the plugin for your store.

**Note: If you are running version of WooCommerce older than 3.4.x your HashGate Payment tab will be underneath the WooCommerce > Settings > Checkout tab**

## Settings

### Enable / Disable

Turn the HashGate Payments payment method on / off for visitors at checkout.

### Title

Title of the payment method on the checkout page

### Description

Description of the payment method on the checkout page

### API Key

Your HashGate API key. Available within https://hashgate.app/account/settings/api

Using an API key allows your website to create charges and recieve updates from HashGate on Payment Status.

### Webhook Endpoint

Using webhooks are required for HashGate to be able to send payment confirmation messages to this website. To fill this out:

1. In your HashGate API settings page, scroll to the 'Webhook URL' section.
2. Paste the webhook endpoint into the webhook url. 
3. Click Save.


### Debug log

Whether or not to store debug logs.

If this is checked, these are saved within your `wp-content/uploads/wc-logs/` folder in a .log file prefixed with `hashgate-`

## Prerequisites

To use this plugin with your WooCommerce store you will need:

- [WordPress] (tested up to 4.9.7)
- [WooCommerce] (tested up to 3.4.3)


## License

This project is licensed under the GPLv2 or later