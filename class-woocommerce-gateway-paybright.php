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
class WooCommerce_Gateway_Paybright {
	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var WooCommerce_Gateway_Paybright
	 */
	private static $self_instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$self_instance ) {
			self::$self_instance = new self();
		}

		return self::$self_instance;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		add_action(
			'plugins_loaded',
			array( $this, 'init_paybright_payment_gateway' ),
			0
		);
	}

	/**
	 * Initialize the gateway.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function init_paybright_payment_gateway() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		include_once plugin_basename( 'includes/class-wc-gateway-paybright.php' );
		load_plugin_textdomain(
			'woocommerce-gateway-paybright',
			false,
			trailingslashit(
				dirname(
					plugin_basename( __FILE__ )
				)
			)
		);
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param array $methods methods.
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Paybright';
		return $methods;
	}


}
