<?php
/**
 * PortPos API Wrapper v2
 * Meets WordPress.org standards for security and coding style.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class EDD_PortPos_API
{

    /**
     * @var string
     */
    private $app_key;

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var bool
     */
    private $is_sandbox;

    /**
     * Constructor
     */
    public function __construct($app_key, $secret_key, $is_sandbox = false)
    {
        $this->app_key = $app_key;
        $this->secret_key = $secret_key;
        $this->is_sandbox = $is_sandbox;
    }

    /**
     * Get the API base URL for v2
     *
     * @return string
     */
    private function get_api_url()
    {
        return $this->is_sandbox ? 'https://api-sandbox.portpos.com/' : 'https://api.portpos.com/';
    }

    /**
     * Generate Bearer Token for v2
     * Format: base64(APPKEY:md5(SECRETKEY.TIMESTAMP))
     *
     * @return string
     */
    private function get_auth_header()
    {
        $timestamp = time();
        $token = md5($this->secret_key . $timestamp);
        $auth_str = base64_encode($this->app_key . ':' . $token);
        return 'Bearer ' . $auth_str;
    }

    /**
     * Create a new invoice (v2)
     *
     * @param array $params Invoice parameters.
     * @return array|WP_Error
     */
    public function create_invoice($params)
    {
        $endpoint = $this->get_api_url() . 'payment/v2/invoice';

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($params),
            'timeout' => 45,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Verify a transaction (v2)
     *
     * @param string $invoice_id PortPos Invoice ID.
     * @param string $amount Total amount to verify.
     * @return array|WP_Error
     */
    public function verify_transaction($invoice_id, $amount)
    {
        $endpoint = $this->get_api_url() . 'payment/v2/invoice/ipn-validate';

        $body = array(
            'invoice' => $invoice_id,
            'amount' => (string) $amount,
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'timeout' => 45,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Retrieve full invoice details (v2)
     *
     * @param string $invoice_id PortPos Invoice ID.
     * @return array|WP_Error
     */
    public function get_invoice($invoice_id)
    {
        $endpoint = $this->get_api_url() . 'payment/v2/invoice/' . $invoice_id;

        $response = wp_remote_get($endpoint, array(
            'headers' => array(
                'Authorization' => $this->get_auth_header(),
            ),
            'timeout' => 45,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
