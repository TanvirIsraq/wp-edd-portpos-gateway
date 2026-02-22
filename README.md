# EDD PortPos Gateway

This plugin adds PortPos as a payment gateway to Easy Digital Downloads (EDD). It allows customers to pay using various local payment methods in Bangladesh through the PortPos platform.

## Features
- **WordPress.org Directory Ready**: Complies with strict security standards (escaping, sanitization, nonces) and includes a validated `readme.txt`.
- **PortPos API v2 Integration**: Uses the latest secure REST API with Bearer token authentication.
- **Redirection Payment**: Securely redirects customers to PortPos for payment.
- **Seamless Verification**: Automatically verifies transactions via server-to-server IPN and return validation.
- **Enhanced Logging**: Automatically records detailed payment info (Method, Card Brand, etc.) in EDD payment notes.
- **Checkout Icons**: Displays supported payment method hints at checkout.
- **Sandbox Mode**: Easy testing with PortPos sandbox credentials.

## Requirements
- WordPress 5.0+
- Easy Digital Downloads 3.0+
- PHP 7.4+
- PortPos App Key and Secret Key
- **Store Currency**: Must be set to **BDT** (Bangladeshi Taka).
- **Customer Phone**: PortPos requires a customer phone number. Ensure your checkout collects phone numbers, or the plugin will use a placeholder.

## Installation
1. Upload the `edd-portpos-gateway` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Downloads > Settings > Payment Gateways**.
4. Enable **PortPos** and enter your API credentials in the PortPos settings section.

## Support
For issues or feature requests, please contact the developer through the GitHub issue tracker.
