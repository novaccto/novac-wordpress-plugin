=== Novac ===
Contributors: engineeringnovac
Tags: payments, mastercard, visa
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.3
Stable tag: 1.0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Accept both international and local payments on from your store.

== Description ==


= Plugin Features =
* Collections: Card, Bank Transfer


= Requirements =

1. WordPress 6.0 or newer.
2. Novac Merchant Account [API Keys](https://www.app.novacpayment.com/settings/api-keys)
4. Supported PHP version: 7.4.0 or newer is recommended.

== Installation ==
= Manual Installation =
1.  Download the plugin zip file.
2.  Login to your WordPress Admin. Click on "Plugins > Add New" from the left menu.
3.  Click on the "Upload" option, then click "Choose File" to select the zip file you downloaded. Click "OK" and "Install Now" to complete the installation.
4.  Activate the plugin.
7.  Configure your Novac settings accordingly.

== Source Code & Build Process ==
This plugin uses modern build tools to generate production-ready JavaScript and CSS. The original source code is available in a public repository:

**Source Code Repository:**
[GitHub - novac](https://github.com/novaccto/novac-wordpress-plugin)

** Build Process **
The full, unminified source code for our plugin is  publicly available at: https://github.com/novaccto/novac-wordpress-plugin
Our plugin uses modern build tools, including Webpack (wp-scripts) and UglifyJS, to generate production-ready JavaScript and CSS.

For FTP manual installation, [check here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Webhooks =

= 1.0.1 =
* Fixed bug that incorrectly transitioned transactions from Pending/Failed to Successful.
= 1.0.0 =
*   First release

== External Services ==

This plugin integrates with external services to process payments and provide a seamless checkout experience. Below is a detailed explanation of the services used:

1. **Novac Payment Integration**
   - **Data Sent**: The plugin sends transaction details, such as the amount, currency, and user-specific identifiers, to Novac's API endpoints.
   - **API Endpoint**: `https://api.novacpayment.com/api/v1/`
   - **Webhook Endpoint**: `https://yourwebsite.com/?novac-webhook=1`
   - **Purpose**: To process payments securely and efficiently.
   - **When data is sent**:
     - Data is sent to Novac's API when a user initiates a payment.
     - The inline script is loaded on the checkout page for payment functionality.
   - **Links**:
     - [Novac Terms of Service](https://www.novacpayment.com/uk/terms-of-use)
     - [Novac Privacy Policy](https://www.novacpayment.com/uk/privacy-policy)

= Contribution =

We love to get your input. you can also include or suggest feature via Github [here](https://github.com/novaccto/novac-wordpress-plugin/issues)

== Screenshots ==



== Other Notes ==