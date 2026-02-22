<?php
/**
 * PortPos Gateway Implementation for EDD
 * Meets WordPress.org standards for security and coding style.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EDD_PortPos_Gateway
{

    /**
     * Constructor: Initialize hooks
     */
    public function __construct()
    {
        // Settings
        add_filter('edd_settings_sections_gateways', array($this, 'register_section'));
        add_filter('edd_settings_gateways', array($this, 'register_settings'));

        // Checkout form display
        add_action('edd_portpos_cc_form', array($this, 'cc_form'));

        // Payment processing
        add_action('edd_gateway_portpos', array($this, 'process_payment'));

        // IPN / Return listener
        add_action('init', array($this, 'listen_for_callbacks'));

        // Checkout label icons
        add_filter('edd_gateway_checkout_label_portpos', array($this, 'add_checkout_icons'), 10, 1);

        // Enqueue popup JS on checkout
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Whitelist PortPos for safe redirects
        add_filter('allowed_redirect_hosts', array($this, 'whitelist_portpos_domains'));
    }

    /**
     * Whitelist PortPos domains for wp_safe_redirect
     *
     * @param array $hosts Allowed hosts.
     * @return array
     */
    public function whitelist_portpos_domains($hosts)
    {
        $hosts[] = 'portpos.com';
        $hosts[] = 'api.portpos.com';
        $hosts[] = 'api-sandbox.portpos.com';
        $hosts[] = 'payment.portpos.com';
        $hosts[] = 'payment-sandbox.portpos.com';
        return $hosts;
    }

    /**
     * Register the PortPos section under Gateways settings
     *
     * @param array $sections Existing sections.
     * @return array Updated sections.
     */
    public function register_section($sections)
    {
        $sections['portpos'] = esc_html__('PortPos', 'edd-portpos-gateway');
        return $sections;
    }

    /**
     * Register PortPos settings in its own section
     *
     * @param array $settings Existing EDD settings.
     * @return array Updated settings.
     */
    public function register_settings($settings)
    {
        $portpos_settings = array(
            'portpos' => array(
                array(
                    'id' => 'edd_portpos_settings',
                    'name' => '<strong>' . esc_html__('PortPos Settings', 'edd-portpos-gateway') . '</strong>',
                    'type' => 'header',
                ),
                array(
                    'id' => 'portpos_app_key',
                    'name' => esc_html__('App Key', 'edd-portpos-gateway'),
                    'desc' => esc_html__('Enter your PortPos App Key', 'edd-portpos-gateway'),
                    'type' => 'text',
                    'size' => 'regular',
                ),
                array(
                    'id' => 'portpos_secret_key',
                    'name' => esc_html__('Secret Key', 'edd-portpos-gateway'),
                    'desc' => esc_html__('Enter your PortPos Secret Key', 'edd-portpos-gateway'),
                    'type' => 'password',
                    'size' => 'regular',
                ),
                array(
                    'id' => 'portpos_integration_method',
                    'name' => esc_html__('Integration Method', 'edd-portpos-gateway'),
                    'desc' => esc_html__('Redirect: Customer is sent to PortPos. Popup: Payment opens in an overlay on your site.', 'edd-portpos-gateway'),
                    'type' => 'select',
                    'options' => array(
                        'redirect' => esc_html__('Redirect (Standard)', 'edd-portpos-gateway'),
                        'popup' => esc_html__('Popup / Overlay (Iframe)', 'edd-portpos-gateway'),
                    ),
                    'std' => 'redirect',
                ),
            ),
        );

        return array_merge($settings, $portpos_settings);
    }

    /**
     * Enqueue popup JS and pass parameters to it via wp_localize_script
     */
    public function enqueue_scripts()
    {
        if (!function_exists('edd_is_checkout') || !edd_is_checkout()) {
            return;
        }

        if (edd_get_option('portpos_integration_method', 'redirect') !== 'popup') {
            return;
        }

        wp_enqueue_script(
            'edd-portpos-gateway-popup',
            EDD_PORTPOS_PLUGIN_URL . 'assets/js/portpos-popup.js',
            array('jquery'),
            EDD_PORTPOS_VERSION,
            true
        );

        // Pass the popup URL if flagged in the session
        $popup_url = '';
        /**
         * Not using nonce because this is a public query arg used to trigger the modal.
         * The actual invoice ID is validated via server-to-server API call later.
         */
        if (isset($_GET['portpos_popup']) && !empty($_GET['portpos_popup'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $invoice_id = sanitize_text_field(wp_unslash($_GET['portpos_popup'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $is_sandbox = edd_is_test_mode();
            $popup_url = $is_sandbox
                ? 'https://payment-sandbox.portpos.com/payment/?invoice=' . $invoice_id
                : 'https://payment.portpos.com/payment/?invoice=' . $invoice_id;
        }

        wp_localize_script(
            'edd-portpos-gateway-popup',
            'edd_portpos_params',
            array(
                'popup_url' => esc_url_raw($popup_url),
                'i18n' => array(
                    'paying_via' => esc_html__('Pay via PortPos', 'edd-portpos-gateway'),
                    'loading' => esc_html__('Loading payment gateway…', 'edd-portpos-gateway'),
                ),
            )
        );
    }

    /**
     * Add supported payment icons/hints to the checkout label
     *
     * @param string $label Original label.
     * @return string Modified HTML label.
     */
    public function add_checkout_icons($label)
    {
        $html = '<span class="edd-portpos-gateway-label">' . esc_html($label) . '</span>';
        $html .= '<span class="edd-portpos-gateway-icons" style="margin-left: 10px; vertical-align: middle;">';
        $html .= '<small style="color: #666; font-size: 11px;">' . esc_html__('(Cards, bKash, Nagad, etc.)', 'edd-portpos-gateway') . '</small>';
        $html .= '</span>';

        return $html;
    }

    /**
     * Render the checkout form placeholder
     */
    public function cc_form()
    {
        echo '<p>' . esc_html__('You will be redirected to the secure PortPos gateway to complete your purchase.', 'edd-portpos-gateway') . '</p>';
    }

    /**
     * Process the payment request
     *
     * @param array $purchase_data EDD purchase data.
     * @return void
     */
    public function process_payment($purchase_data)
    {
        $app_key = edd_get_option('portpos_app_key');
        $secret_key = edd_get_option('portpos_secret_key');
        $is_sandbox = edd_is_test_mode();
        $method = edd_get_option('portpos_integration_method', 'redirect');

        if (empty($app_key) || empty($secret_key)) {
            edd_set_error('missing_keys', esc_html__('PortPos API keys are not configured.', 'edd-portpos-gateway'));
            edd_send_back_to_checkout('?payment-mode=' . esc_attr($purchase_data['post_data']['edd-gateway']));
        }

        if (edd_get_currency() !== 'BDT') {
            edd_set_error('invalid_currency', esc_html__('PortPos only supports BDT currency. Please update your store settings.', 'edd-portpos-gateway'));
            edd_send_back_to_checkout('?payment-mode=' . esc_attr($purchase_data['post_data']['edd-gateway']));
        }

        // Insert pending payment record
        $payment_data = array(
            'price' => $purchase_data['price'],
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => edd_get_currency(),
            'downloads' => $purchase_data['downloads'],
            'user_info' => $purchase_data['user_info'],
            'cart_details' => $purchase_data['cart_details'],
            'gateway' => 'portpos',
            'status' => 'pending',
        );

        $payment_id = edd_insert_payment($payment_data);

        if (!$payment_id) {
            edd_set_error('payment_error', esc_html__('Could not create payment record. Please contact support.', 'edd-portpos-gateway'));
            edd_send_back_to_checkout('?payment-mode=' . esc_attr($purchase_data['post_data']['edd-gateway']));
        }

        $api = new EDD_PortPos_API($app_key, $secret_key, $is_sandbox);
        $params = $this->prepare_api_params($payment_id, $purchase_data);

        $result = $api->create_invoice($params);

        if (isset($result['status']) && 200 === (int) $result['status'] && !empty($result['data']['invoice_id'])) {
            $invoice_id = $result['data']['invoice_id'];
            edd_update_payment_meta($payment_id, '_portpos_invoice_id', $invoice_id);

            if ('popup' === $method) {
                // Redirect back to checkout page with a query arg to trigger the popup
                $redirect_url = add_query_arg(
                    array(
                        'portpos_popup' => rawurlencode($invoice_id),
                    ),
                    edd_get_checkout_uri()
                );
                wp_safe_redirect(esc_url_raw($redirect_url));
            } else {
                // Standard redirect to PortPos hosted payment page
                $payment_url = $is_sandbox
                    ? 'https://payment-sandbox.portpos.com/payment/?invoice=' . $invoice_id
                    : 'https://payment.portpos.com/payment/?invoice=' . $invoice_id;
                wp_safe_redirect(esc_url_raw($payment_url));
            }
            exit;
        } else {
            $error_msg = isset($result['error']['message']) ? $result['error']['message'] : esc_html__('Unable to connect to PortPos.', 'edd-portpos-gateway');
            edd_record_gateway_error(esc_html__('PortPos API Error', 'edd-portpos-gateway'), wp_json_encode($result), $payment_id);
            edd_update_payment_status($payment_id, 'failed');
            edd_set_error('portpos_api_error', esc_html__('PortPos Error: ', 'edd-portpos-gateway') . esc_html($error_msg));
            edd_send_back_to_checkout('?payment-mode=' . esc_attr($purchase_data['post_data']['edd-gateway']));
        }
    }

    /**
     * Prepare parameters for API v2 invoice creation
     *
     * @param int   $payment_id     EDD payment ID.
     * @param array $purchase_data  EDD purchase data.
     * @return array
     */
    private function prepare_api_params($payment_id, $purchase_data)
    {
        return array(
            'order' => array(
                'amount' => number_format($purchase_data['price'], 2, '.', ''),
                'currency' => 'BDT',
                'redirect_url' => add_query_arg(array('edd-listener' => 'portpos-return', 'payment_id' => $payment_id), home_url('/')),
                'ipn_url' => add_query_arg(array('edd-listener' => 'portpos-ipn', 'payment_id' => $payment_id), home_url('/')),
            ),
            'product' => array(
                /* translators: %s: Order ID */
                'name' => sprintf(esc_html__('Order #%s', 'edd-portpos-gateway'), $payment_id),
                'description' => esc_html__('Purchase from ', 'edd-portpos-gateway') . get_bloginfo('name'),
            ),
            'billing' => array(
                'customer' => array(
                    'name' => esc_html($purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name']),
                    'email' => sanitize_email($purchase_data['user_email']),
                    'phone' => !empty($purchase_data['user_info']['phone']) ? sanitize_text_field($purchase_data['user_info']['phone']) : '01700000000',
                    'address' => sanitize_text_field($purchase_data['user_info']['address']['line1']),
                    'city' => sanitize_text_field($purchase_data['user_info']['address']['city']),
                    'state' => $purchase_data['user_info']['address']['state'] ? sanitize_text_field($purchase_data['user_info']['address']['state']) : sanitize_text_field($purchase_data['user_info']['address']['city']),
                    'zip' => sanitize_text_field($purchase_data['user_info']['address']['zip']),
                    'country' => sanitize_text_field($purchase_data['user_info']['address']['country']),
                ),
            ),
        );
    }

    /**
     * Listen for Return and IPN callbacks
     */
    public function listen_for_callbacks()
    {
        /**
         * External callbacks (IPN/Return) from PortPos do not support WordPress nonces.
         * Verification is handled via server-to-server API request in verify_and_complete().
         */
        if (!isset($_GET['edd-listener'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $listener = sanitize_text_field(wp_unslash($_GET['edd-listener'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ('portpos-return' === $listener) {
            $this->handle_return();
        } elseif ('portpos-ipn' === $listener) {
            $this->handle_ipn();
        }
    }

    /**
     * Handle user redirection back from PortPos
     */
    private function handle_return()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $invoice_id = isset($_GET['invoice']) ? sanitize_text_field(wp_unslash($_GET['invoice'])) : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $payment_id = isset($_GET['payment_id']) ? absint($_GET['payment_id']) : 0;

        if (empty($payment_id) || empty($invoice_id)) {
            wp_safe_redirect(edd_get_checkout_uri());
            exit;
        }

        // Verify that the invoice ID matches our records
        $stored_invoice = edd_get_payment_meta($payment_id, '_portpos_invoice_id', true);

        if ($stored_invoice !== $invoice_id) {
            wp_safe_redirect(edd_get_checkout_uri());
            exit;
        }


        $verified = $this->verify_and_complete($payment_id, $invoice_id);

        if ($verified) {
            wp_safe_redirect(edd_get_success_page_uri());
        } else {
            edd_set_error('portpos_failed', esc_html__('Payment could not be verified. Please contact support.', 'edd-portpos-gateway'));
            wp_safe_redirect(edd_get_checkout_uri());
        }
        exit;
    }

    /**
     * Handle Asynchronous Notification (IPN)
     */
    private function handle_ipn()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
            exit('Method not allowed');
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $invoice_id = isset($_POST['invoice']) ? sanitize_text_field(wp_unslash($_POST['invoice'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $amount = isset($_POST['amount']) ? sanitize_text_field(wp_unslash($_POST['amount'])) : '';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $payment_id = isset($_GET['payment_id']) ? absint($_GET['payment_id']) : 0;

        if (empty($payment_id) || empty($invoice_id)) {
            exit('Missing data');
        }

        // Verify that the invoice ID matches our records
        $stored_invoice = edd_get_payment_meta($payment_id, '_portpos_invoice_id', true);

        if ($stored_invoice !== $invoice_id) {
            exit('Order not found');
        }

        $this->verify_and_complete($payment_id, $invoice_id, $amount);
        exit('OK');
    }

    /**
     * Verify with API and finalize payment status
     *
     * @param int        $payment_id  EDD payment ID.
     * @param string     $invoice_id  PortPos invoice ID.
     * @param float|null $amount      Amount to verify against.
     * @return bool
     */
    private function verify_and_complete($payment_id, $invoice_id, $amount = null)
    {
        $current_status = edd_get_payment_status($payment_id);
        if ('publish' === $current_status || 'complete' === $current_status) {
            return true;
        }

        $app_key = edd_get_option('portpos_app_key');
        $secret_key = edd_get_option('portpos_secret_key');

        if (!$amount) {
            $amount = edd_get_payment_amount($payment_id);
        }

        $api = new EDD_PortPos_API($app_key, $secret_key, edd_is_test_mode());
        $result = $api->verify_transaction($invoice_id, $amount);

        if (isset($result['status']) && 200 === (int) $result['status'] && 'ACCEPTED' === $result['data']['status']) {
            $method = isset($result['data']['gateway']['name']) ? $result['data']['gateway']['name'] : 'PortPos';
            $txn_id = isset($result['data']['gateway']['txn_id']) ? $result['data']['gateway']['txn_id'] : 'N/A';
            $note = sprintf(
                /* translators: 1: Payment method 2: Transaction ID */
                esc_html__('PortPos Payment Verified. Method: %1$s. Transaction ID: %2$s', 'edd-portpos-gateway'),
                esc_html($method),
                esc_html($txn_id)
            );

            edd_insert_payment_note($payment_id, $note);
            edd_set_payment_transaction_id($payment_id, $txn_id);
            edd_update_payment_meta($payment_id, '_portpos_verified_payload', $result['data']);
            edd_update_payment_status($payment_id, 'publish');

            return true;
        } else {
            $reason = isset($result['data']['reason']) ? $result['data']['reason'] : (isset($result['message']) ? $result['message'] : 'Rejected');
            /* translators: %s: Error reason */
            $note_fail = sprintf(esc_html__('PortPos verification failed. Result: %s', 'edd-portpos-gateway'), esc_html($reason));
            edd_insert_payment_note($payment_id, $note_fail);
            edd_update_payment_status($payment_id, 'failed');

            return false;
        }
    }
}
