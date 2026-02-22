=== EDD PortPos Gateway ===
Contributors: tanvirisraq
Tags: edd, portpos, payment gateway, bangladesh, bkash
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept payments in Bangladesh using the PortPos payment gateway and Easy Digital Downloads.

== Description ==

This plugin integrates the PortPos payment gateway with Easy Digital Downloads (EDD), allowing merchants in Bangladesh to accept payments via various local methods including credit/debit cards, bKash, Nagad, and Rocket through the PortPos platform.

= Key Features =
* **PortPos API v2 Integration**: Uses the latest secure REST API with Bearer token authentication.
* **Dual Integration Mode**: Choose between Redirect (standard) or Popup/Overlay (iframe modal) per your checkout UX preference.
* **Seamless Verification**: Automatically verifies transactions via server-to-server IPN and return validation.
* **Detailed Logs**: Records specific payment info (Method, Card Brand, etc.) in EDD payment notes.
* **Trust Badges**: Displays supported payment method hints at checkout to increase customer confidence.
* **Sandbox Mode**: Easy testing using PortPos sandbox credentials.

= Requirements =
* Easy Digital Downloads 3.0 or higher.
* Store currency set to BDT (Bangladeshi Taka).
* A valid PortPos account and API keys.

== Installation ==

1. Upload the `edd-portpos-gateway` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Downloads > Settings > Payment Gateways**.
4. Enable **PortPos** in the list of gateways.
5. Enter your PortPos App Key and Secret Key in the settings section.
6. (Optional) Check **Test Mode** to use the PortPos Sandbox environment.

== Frequently Asked Questions ==

= Does it support mobile banking? =
Yes, it supports all mobile banking methods provided by PortPos, including bKash, Nagad, and Rocket.

= Can I use it for international currencies? =
PortPos primarily focuses on BDT. While the API supports other currencies, this plugin is optimized and restricted for BDT to ensure compliance with local banking regulations.

== Screenshots ==

1. PortPos settings in the Easy Digital Downloads gateway settings.
2. PortPos selected as the payment method on the checkout page.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added support for PortPos API v2.
* Implemented IPN and Return validation.
* Added dual integration method: Redirect and Popup/Overlay.
