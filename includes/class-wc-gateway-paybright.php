<?php
/**
 * Paybright Payment Gateway
 *
 *  *
 *
 * @class    WC_Gateway_Paybright
 * @package  WooCommerce
 */

/**
 * Class WooCommerce_Gateway_Paybright
 * Load Paybright
 *
 * @class    WC_Gateway_Paybright
 * @package  WooCommerce
 */
class WC_Gateway_Paybright extends WC_Payment_Gateway {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = 'paybright';
		$this->method_title       = __( 'PayBright', 'woocommerce-paybright-payment-gateway' );
		$this->method_description = __( 'Pay with PayBright to finalize your payment plan and complete your purchase.', 'woocommerce-paybright-payment-gateway' );
		$this->icon               = $this->get_icon();
		$this->has_fields         = function_exists( 'is_checkout_pay_page' ) ? is_checkout_pay_page() : is_page( woocommerce_get_page_id( 'pay' ) );
		$this->title              = __( 'PayBright', 'woocommerce-paybright-payment-gateway' );
		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array( 'products', 'refunds' );
		// Get setting values.
		$this->testmode          = 'yes' === $this->get_option( 'testmode' );
		$this->test_api_key      = $this->testmode ? $this->get_option( 'test_api_key' ) : $this->get_option( 'prod_api_key' );
		$this->test_api_token    = $this->testmode ? $this->get_option( 'test_api_token' ) : $this->get_option( 'prod_api_token' );
		$this->paybright_pay_url = $this->testmode ? 'https://sandbox.paybright.com/CheckOut/ApplicationForm.aspx' : 'https://app.paybright.com/CheckOut/ApplicationForm.aspx';
		$this->enabled           = $this->get_option( 'enabled' );
		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->notify_url        = home_url( '/' ) . '?wc-api=WC_Gateway_Paybright';
		$this->callback_url      = home_url( '/' ) . 'wc-api/CALLBACK';

		// What does this do?
		// add_action('woocommerce_refund_created', 'receipt_refund_page_pb', 10, 2);.
		add_action(
			'woocommerce_receipt_' . $this->id,
			array(
				$this,
				'receipt_page',
			)
		);
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action(
			'woocommerce_api_' . strtolower( get_class( $this ) ),
			array(
				$this,
				'check_pb_response',
			)
		);
		add_filter(
			'woocommerce_api_paybright',
			array(
				$this,
				'check_pb_response',
			)
		);

		// what does this do?
		add_action(
			'woocommerce_api_refund_pb_callback',
			array(
				$this,
				'check_pb_refund',
			)
		);
		add_filter( 'template_redirect', 'ssl_redirect_pb', 1 );
		add_action( 'template_redirect', 'ssl_redirect_pb', 1 );
		add_action(
			'admin_notices',
			array(
				$this,
				'paybright_apikeys_check',
			)
		);
	}


	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-paybright-payment-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayBright', 'woocommerce-paybright-payment-gateway' ),
				'default' => 'yes',
			),
			'title'          => array(
				'title'       => __( 'Title', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which users sees during checkout', 'woocommerce-paybright-payment-gateway' ),
				'default'     => __( 'PayBright', 'woocommerce-paybright-payment-gateway' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which users sees during checkout', 'woocommerce-paybright-payment-gateway' ),
				'default'     => __( 'You will be redirected to PayBright to complete your transaction securely.', 'woocommerce-paybright-payment-gateway' ),
				'desc_tip'    => true,
			),
			'testmode'       => array(
				'title'       => __( 'Test mode', 'woocommerce-paybright-payment-gateway' ),
				'label'       => __( 'Enable Test Mode', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woocommerce-paybright-payment-gateway' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'test_api_key'   => array(
				'title'       => __( 'Test API Key', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your PayBright.', 'woocommerce-paybright-payment-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'test_api_token' => array(
				'title'       => __( 'Test API Token', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Get your API Token from your PayBright.', 'woocommerce-paybright-payment-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'prod_api_key'   => array(
				'title'       => __( 'Production API Key', 'woocommerce-other-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your PayBright.', 'woocommerce-other-payment-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'prod_api_token' => array(
				'title'       => __( 'Production API Token', 'woocommerce-paybright-payment-gateway' ),
				'type'        => 'text',
				'description' => __( 'Get your API Token from your PayBright.', 'woocommerce-paybright-payment-gateway' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Receipt Page.
	 *
	 * @param  WC_order $order order.
	 */
	public function receipt_page( $order ) {
		echo esc_attr( $this->generate_pb_payment_request( $order ) );
	}

	/**
	 * Check paybright response.
	 */
	public function check_pb_response() {
		try {
			global $woocommerce;
			$data     = wc_clean( $_GET ); //phpcs:ignore
			$order_id = $data['x_reference'];
			$order    = new WC_Order( (int) $order_id );
			// Check Signature.
			$signature_pb = $data['x_signature'];

			$signature_url_pb = '';
			if ( isset( $data['x_account_id'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_account_id' . sanitize_text_field( $data['x_account_id'] );
			}
			if ( isset( $data['x_amount'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_amount' . sanitize_text_field( $data['x_amount'] );
			}
			if ( isset( $data['x_currency'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_currency' . sanitize_text_field( $data['x_currency'] );
			}
			if ( isset( $data['x_description'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_description' . sanitize_text_field( $data['x_description'] );
			}
			if ( isset( $data['x_gateway_reference'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_gateway_reference' . sanitize_text_field( $data['x_gateway_reference'] );
			}
			if ( isset( $data['x_invoice'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_invoice' . sanitize_text_field( $data['x_invoice'] );
			}
			if ( isset( $data['x_message'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_message' . sanitize_text_field( $data['x_message'] );
			}
			if ( isset( $data['x_reference'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_reference' . sanitize_text_field( $data['x_reference'] );
			}
			if ( isset( $data['x_result'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_result' . sanitize_text_field( $data['x_result'] );
			}
			if ( isset( $data['x_test'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_test' . sanitize_text_field( $data['x_test'] );
			}
			if ( isset( $data['x_timestamp'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_timestamp' . sanitize_text_field( $data['x_timestamp'] );
			}
			if ( isset( $data['x_transaction_type'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_transaction_type' . sanitize_text_field( $data['x_transaction_type'] );
			}
			if ( isset( $data['x_contract_group_key'] ) ) {
				$signature_url_pb = $signature_url_pb . 'x_contract_group_key' . sanitize_text_field( $data['x_contract_group_key'] );
			}

			$signature_url1_pb = hash_hmac( 'sha256', $signature_url_pb, $this->get_pb_token() );
			if ( $signature_url1_pb === $signature_pb ) {
				if ( isset( $data['x_reference'] ) ) {
					$order_status = $data['x_result'];
					if ( 'Completed' === $order_status ) {
						version_compare( WC_VERSION, '3.0.0', '<' ) ? add_post_meta( $order->id, '_transaction_id', sanitize_text_field( $data['x_gateway_reference'] ), true ) : $order->set_transaction_id( sanitize_text_field( $data['x_gateway_reference'] ) );
						$order->payment_complete();
						$order->update_status( 'processing' );
						$get_transaction_id = version_compare( WC_VERSION, '3.0.0', '<' ) ? $data['x_gateway_reference'] : $order->get_transaction_id();
						$order->add_order_note(
							sprintf(
								/* translators: 1: transaction ID */
								__(
									'Your PayBright Transaction ID is - %1$s',
									'woocommerce'
								),
								$get_transaction_id
							)
						);
						$woocommerce->cart->empty_cart();
					} elseif ( 'Failed' === $order_status ) {
						$order->update_status( 'failed' );
					}
					$url = $this->get_return_url( $order );
					header( 'Location:' . $url );
				}
			} else {
				if ( isset( $data['x_reference'] ) ) {
					$order_status = sanitize_text_field( $data['x_result'] );
					$url          = $this->get_return_url( $order );
					$order->update_status( 'failed', sprintf( __( 'Invalid Request to PayBright.', 'woocommerce-paybright-payment-gateway' ), strtolower( sanitize_text_field( $order_id ) ) ) );
					header( 'Location:' . $url );
				} else {
					$url = $this->get_return_url( $order );
					$order->update_status( 'failed' );
				}
			}
			exit;
		} catch ( Exception $e ) {
			echo 'Caught exception: ', esc_attr( $e->getMessage() ), "\n";
			echo '<script>console.log($e->getMessage());</script>';
		}
	}

	/**
	 * Redirect to SSL Paybright.
	 */
	private function ssl_redirect_pb() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] );
		$http_host   = isset( $_SERVER['HTTP_HOST'] );
		if ( ! is_ssl() ) {
			if ( is_checkout() || is_account_page() || is_woocommerce() ) {
				if ( 0 === strpos( wp_unslash( $request_uri ), 'http' ) ) {
					wp_safe_redirect( preg_replace( '|^http://|', 'https://', $request_uri ) );
					exit;
				} else {
					wp_safe_redirect( 'https://' . wp_unslash( $http_host ) . wp_unslash( $request_uri ) );
					exit;
				}
			}
		} elseif ( is_ssl() && wp_unslash( $request_uri ) && ! is_checkout() && ! is_page( woocommerce_get_page_id( 'thanks' ) ) && ! is_account_page() ) { // Break out of SSL if we leave woocommerce pages or custom pages.
			if ( 0 === strpos( wp_unslash( $request_uri ), 'http' ) ) {
				wp_safe_redirect( preg_replace( '|^https://|', 'http://', wp_unslash( $request_uri ) ) );
				exit;
			} else {
				wp_safe_redirect( 'http://' . wp_unslash( $http_host ) . wp_unslash( $request_uri ) );
				exit;
			}
		}
	}

	/**
	 * Check if SSL is enabled and notify the user.
	 */
	public function paybright_apikeys_check() {
		if ( '' === $this->test_api_key || '' === $this->test_api_token ) {
			$admin_url = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
			echo esc_attr(
				'<div class="notice notice-warning"><p>' . sprintf(
					__(
						'PayBright is almost ready. Please set up your PayBright keys in Woocommerce -> Settings -> Checkout.',
						'woocommerce-paybright-payment-gateway'
					),
					esc_url( $admin_url )
				) . '</p></div>'
			);
		}
	}

	/**
	 * Post meta data
	 *
	 * @param string $order_id post id.
	 * @param string $amount amount.
	 * @param bool   $reason reason.
	 */
	public function process_refund( $order_id, $amount = null, $reason = null ) {
		$order                       = wc_get_order( $order_id );
		$pb_refund_account_id        = $this->test_api_key;
		$pb_refund_amount            = wc_format_decimal( $amount, wc_get_price_decimals() );
		$pb_refund_currency          = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
		$pb_refund_gateway_reference = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->transaction_id : $order->get_transaction_id();
		$pb_refund_reference         = version_compare( WC_VERSION, '3.0.0', '<' ) ? trim( str_replace( '#', '', $order->id ) ) : trim( str_replace( '#', '', $order->get_order_number() ) );
		$pb_refund_test              = $this->testmode ? 'true' : 'false';

		$signature_refund_url  = "x_account_id $pb_refund_account_id x_amount $pb_refund_amount x_currency $pb_refund_currency x_gateway_reference $pb_refund_gateway_reference x_reference $pb_refund_reference x_test $pb_refund_test x_transaction_type refund x_url_callback $this->callback_url";
		$signature_refund_url1 = hash_hmac( 'sha256', $signature_refund_url, $this->get_pb_token() );
		if ( ! $amount ) {
			$amount = $order_total;
		}
		$pb_obj = array(
			'x_account_id'        => $this->test_api_key,
			'x_amount'            => $pb_refund_amount,
			'x_reference'         => version_compare( WC_VERSION, '3.0.0', '<' ) ? trim( str_replace( '#', '', $order->id ) ) : trim( str_replace( '#', '', $order->get_order_number() ) ),
			'x_currency'          => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency(),
			'x_gateway_reference' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->transaction_id : $order->get_transaction_id(),
			'x_test'              => $this->testmode ? 'true' : 'false',
			'x_url_callback'      => $this->callback_url,
			'x_transaction_type'  => 'refund',
			'x_signature'         => $signature_refund_url1,
			'x_nonce'             => wp_create_nonce( 'refund' ),
		);

		$pb_refund_url = $this->testmode ? 'https://sandbox.paybright.com/CheckOut/api2.aspx' : 'https://app.paybright.com/CheckOut/api2.aspx';
		$response = wp_remote_post(
			$pb_refund_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'method'  => 'POST',
				'body'    => wp_json_encode( $pb_obj ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: '" . esc_attr( $error_message ) . "'" . esc_html( $response->get_error_message() );
		} else {
			$pb_responsebody       = $response['body'];
			$pb_response_arr       = explode( '&', $pb_responsebody );
			$pb_account_id         = explode( '=', $pb_response_arr[0] ); // x_account_id.
			$pb_amount             = explode( '=', $pb_response_arr[1] ); // x_amount.
			$pb_currency           = explode( '=', $pb_response_arr[2] ); // x_currency.
			$pb_gateway_reference  = explode( '=', $pb_response_arr[3] ); // x_gateway_reference.
			$pb_message            = explode( '=', $pb_response_arr[4] ); // x_message.
			$pb_reference          = explode( '=', $pb_response_arr[5] ); // x_reference.
			$pb_result             = explode( '=', $pb_response_arr[6] ); // x_result.
			$pb_test               = explode( '=', $pb_response_arr[7] ); // x_test.
			$pb_timestamp          = explode( '=', $pb_response_arr[9] ); // x_timestamp.
			$pb_transaction_type   = explode( '=', $pb_response_arr[8] ); // x_transaction_type.
			$pb_response_signature = explode( '=', $pb_response_arr[10] ); // x_signature.
			$order_id              = $pb_reference[1];
			$order                 = new WC_Order( $order_id );
			$refund_signature_url  = 'x_account_id' . $pb_account_id[1] . 'x_amount' . $pb_amount[1] . 'x_currency' . $pb_currency[1] . 'x_gateway_reference' . $pb_gateway_reference[1] . 'x_message' . $pb_message[1] . 'x_reference' . $pb_reference[1] . 'x_result' . $pb_result[1] . 'x_test' . $pb_test[1] . 'x_timestamp' . $pb_timestamp[1] . 'x_transaction_type' . $pb_transaction_type[1];
			$refund_signature_url1 = hash_hmac( 'sha256', $refund_signature_url, $this->get_pb_token() );
			if ( $refund_signature_url1 === $pb_response_signature[1] ) {
				$refund_status = $pb_result[1];
				if ( 'completed' === $refund_status ) {
					$order->add_order_note(
						sprintf(
						/* translators: 1: amount */
							__(
								'This order was refunded for $%1$s via PayBright',
								'woocommerce'
							),
							$pb_amount[1]
						)
					);
					$pb_order_total  = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->total : $order->get_total();
					$pb_order_refund = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->refund_amount : $order->get_total_refunded();
					return true;
				} elseif ( 'failed' === $refund_status ) {
					$order->add_order_note(
						sprintf(
						/* translators: 1: amount */
							__(
								'Refund order request for $%1$s failed',
								'woocommerce'
							),
							$pb_amount[1]
						)
					);
				}
			} else {
				$order->add_order_note( __( 'Invalid PayBright Request', 'woocommerce' ) );
			}
		}
		return false;
	}

	/**
	 * Get Paybright Token.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_pb_token() {
		$api_token = $this->test_api_token;
		return $api_token;
	}

		/**
		 * Process Payment.
		 *
		 * @param  string $order_id order id.
		 */
	public function process_payment( $order_id ) {
		try {
			global $woocommerce;
			$order = wc_get_order( $order_id );
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( $order ),
			);
		} catch ( Exception $e ) {
			echo 'Caught exception: ', esc_attr( $e->getMessage() ), "\n";
			echo '<script>console.log($e->getMessage());</script>';
		}
	}

	/**
	 * Generate the request for the payment.
	 *
	 * @param  string $order_id order id.
	 */
	protected function generate_pb_payment_request( $order_id ) {
		$order = wc_get_order( $order_id );
		try {
			$vc_version                = version_compare( WC_VERSION, '3.0.0', '<' );
			$post_data                 = array();
			$post_data['x_account_id'] = $this->test_api_key;
			$post_data['x_amount']     = $order->get_total();
			$post_data['x_currency']   = $vc_version ? $order->get_order_currency() : $order->get_currency();

			$billing_address_1 = $vc_version ? $order->billing_address_1 : $order->get_billing_address_1();
			if ( ! empty( $billing_address ) ) {
				$post_data['x_customer_billing_address1'] = $billing_address_1;
			}

			$billing_address_2 = $vc_version ? $order->billing_address_2 : $order->get_billing_address_2();
			if ( ! empty( $billing_address_2 ) ) {
				$post_data['x_customer_billing_address2'] = $billing_address_2;
			}

			$billing_city = $vc_version ? $order->billing_city : $order->get_billing_city();
			if ( ! empty( $billing_city ) ) {
				$post_data['x_customer_billing_city'] = $billing_city;
			}

			$billing_company = $vc_version ? $order->billing_company : $order->get_billing_company();
			if ( ! empty( $billing_company ) ) {
				$post_data['x_customer_billing_company'] = $billing_company;
			}

			$billing_country = $vc_version ? $order->billing_country : $order->get_billing_country();
			if ( ! empty( $billing_country ) ) {
				$post_data['x_customer_billing_country'] = $billing_country;
			}

			$billing_phone = $vc_version ? $order->billing_phone : $order->get_billing_phone();
			if ( ! empty( $billing_phone ) ) {
				$post_data['x_customer_billing_phone'] = $billing_phone;
			}

			$billing_state = $vc_version ? $order->billing_state : $order->get_billing_state();
			if ( ! empty( $billing_state ) ) {
				$post_data['x_customer_billing_state'] = $billing_state;
			}

			$billing_postcode = $vc_version ? $order->billing_postcode : $order->get_billing_postcode();
			if ( ! empty( $billing_postcode ) ) {
				$post_data['x_customer_billing_zip'] = $billing_postcode;
			}

			$billing_email = $vc_version ? $order->billing_email : $order->get_billing_email();
			if ( ! empty( $billing_email ) ) {
				$post_data['x_customer_email'] = $billing_email;
			}

			$billing_first_name = $vc_version ? $order->billing_first_name : $order->get_billing_first_name();
			if ( ! empty( $billing_first_name ) ) {
				$post_data['x_customer_first_name'] = $billing_first_name;
			}

			$billing_last_name = $vc_version ? $order->billing_last_name : $order->get_billing_last_name();
			if ( ! empty( $billing_last_name ) ) {
				$post_data['x_customer_last_name'] = $billing_last_name;
			}

			$billing_phone = $vc_version ? $order->billing_phone : $order->get_billing_phone();
			if ( ! empty( $billing_phone ) ) {
				$post_data['x_customer_phone'] = $billing_phone;
			}

			$shipping_address_1 = $vc_version ? $order->shipping_address_1 : $order->get_shipping_address_1();
			if ( ! empty( $shipping_address_1 ) ) {
				$post_data['x_customer_shipping_address1'] = $shipping_address_1;
			}

			$shipping_address_2 = $vc_version ? $order->shipping_address_2 : $order->get_shipping_address_2();
			if ( ! empty( $shipping_address_2 ) ) {
				$post_data['x_customer_shipping_address2'] = $shipping_address_2;
			}

			$shipping_city = $vc_version ? $order->shipping_city : $order->get_shipping_city();
			if ( ! empty( $shipping_city ) ) {
				$post_data['x_customer_shipping_city'] = $shipping_city;
			}

			$shipping_company = $vc_version ? $order->shipping_company : $order->get_shipping_company();
			if ( ! empty( $shipping_company ) ) {
				$post_data['x_customer_shipping_company'] = $shipping_company;
			}

			$shipping_country = $vc_version ? $order->shipping_country : $order->get_shipping_country();
			if ( ! empty( $shipping_country ) ) {
				$post_data['x_customer_shipping_country'] = $shipping_country;
			}

			$shipping_first_name = $vc_version ? $order->shipping_first_name : $order->get_shipping_first_name();
			if ( ! empty( $shipping_first_name ) ) {
				$post_data['x_customer_shipping_first_name'] = $shipping_first_name;
			}

			$shipping_last_name = $vc_version ? $order->shipping_last_name : $order->get_shipping_last_name();
			if ( ! empty( $shipping_last_name ) ) {
				$post_data['x_customer_shipping_last_name'] = $shipping_last_name;
			}

			$shipping_state = $vc_version ? $order->shipping_state : $order->get_shipping_state();
			if ( ! empty( $shipping_state ) ) {
				$post_data['x_customer_shipping_state'] = $shipping_state;
			}

			$shipping_postcode = $vc_version ? $order->shipping_postcode : $order->get_shipping_postcode();
			if ( ! empty( $shipping_postcode ) ) {
				$post_data['x_customer_shipping_zip'] = $shipping_postcode;
			}

			$order_key = version_compare(
				WC_VERSION,
				'3.0',
				'<'
			) ? $order->order_key : $order->get_order_key();

			$confirmation_url = add_query_arg(
				array(
					'action'    => 'complete_checkout',
					'order_id'  => $order_id,
					'order_key' => $order_key,
				),
				WC()->api_request_url( get_class( $this ) )
			);

			$post_data['x_platform']     = 'woocommerce';
			$post_data['x_reference']    = trim( str_replace( '#', '', $order->get_order_number() ) );
			$post_data['x_shop_country'] = 'CA';
			$post_data['x_shop_name']    = get_home_url();
			$post_data['x_test']         = $this->testmode ? 'true' : 'false';
			$post_data['x_url_callback'] = html_entity_decode( $this->notify_url );
			$post_data['x_url_cancel']   = html_entity_decode( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_cancel_order_url() : $order->get_cancel_order_url() );
			$post_data['x_url_complete'] = $confirmation_url;
			$query1                      = implode(
				'',
				array_map(
					function( $v, $k ) {
						return $k . $v;
					},
					$post_data,
					array_keys( $post_data )
				)
			);

			$pb_sig = hash_hmac( 'sha256', $query1, $this->test_api_token );
			echo "<script>console.log('" . esc_attr( $query1 ) . "')</script>";

			$post_data['x_signature'] = $pb_sig;
			$pb_url                   = $this->paybright_pay_url;

			/**
			 * Redirect with POST.
			 *
			 * @param string $dest dest.
			 * @param string $post_data post_data.
			 * @param string $token token.
			 * @param string $strong strong.
			 */
			function RedirectWithMethodPost( $dest, $post_data, $token, $strong ) {
				$params = '';
				$url    = $params;
				if ( strpos( $dest, '?' ) ) {
					list($url, $params) = explode( '?', $dest, 2 );
				} else {
					$url = $dest;
				}
				echo "<form id='pb_form' method='post' action='" . esc_attr( $url ) . "'>\n";
				foreach ( $post_data as $key => $value ) {
					$sanitized_value = htmlspecialchars( $value, ENT_QUOTES );
					echo "<input type='hidden' name='" . esc_attr( $key ) . "' value='" . esc_attr( $sanitized_value ) . "'><br>";
				}
				echo '</form><script type="text/javascript">document.getElementById("pb_form").submit();</script>';
			}

			RedirectWithMethodPost( $pb_url, $post_data, $this->test_api_token, $query1 );
		} catch ( Exception $e ) {
			echo 'Caught exception: ', esc_attr( $e->getMessage() ), "\n";
			echo '<script>console.log($e->getMessage());</script>';
		}
	}

	/**
	 * Return paybright icon.
	 *
	 * @return string
	 */
	public function get_icon() {

		$icon = '<img style="max-width:100%;" src="' . ( plugin_dir_url( __FILE__ ) . 'res/images/pb.png' ) . '" alt="PayBright" />';
		return $icon;
	}
}
