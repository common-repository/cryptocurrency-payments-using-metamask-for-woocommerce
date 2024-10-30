<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CPMW_TX_INFO' ) ) {
	exit;
}

use CPMW\CONVERTER\ConverterUtils;

if ( ! class_exists( 'CPMW_TX_INFO' ) ) {
	/**
	 * Class CPMW_TX_VERIFY
	 *
	 * This class handles the verify transaction.
	 */
	class CPMW_TX_INFO {
		private static $instance     = null;
		private static $ether_amount = false;

		/**
		 * Constructor for initializing the class with Infura ID and chain ID.
		 *
		 * @param string $infura_id The Infura project ID.
		 * @param int    $chain_id The chain ID.
		 */
		public function __construct() {
			$this->cpmw_autoload_files();
		}

		/**
		 * Get an instance of the class with Infura ID and chain ID.
		 *
		 * @param string $infura_id The Infura project ID.
		 * @param int    $chain_id The chain ID.
		 * @return CPMW_TX_INFO
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Autoload necessary files for Web3.
		 *
		 * @return mixed
		 */
		private function cpmw_autoload_files() {

			require_once CPMW_PATH . 'includes/crypto_converter/Crypt/Random.php';
			require_once CPMW_PATH . 'includes/crypto_converter/Math/BigInteger.php';
			require_once CPMW_PATH . 'includes/crypto_converter/converter-utils.php';
		}

		/**
		 * Get transaction receipt based on transaction hash.
		 *
		 * @param string $txHash The transaction hash.
		 * @return false
		 */
		public function get_transaction_type( $receipt, $receiver_id ) {
			if ( $receipt ) {
				if ( isset( $receipt['input'] ) && '0x' !== $receipt['input'] ) {
					$senderId    = substr( trim( $receipt['input'] ), 0, 74 );
					$receiver_id = ltrim( $receiver_id, '0x' );

					if ( strpos( $senderId, $receiver_id ) !== false ) {
						self::$ether_amount = $this->convert_token_amount( $receipt['input'] );
					} else {
						return 'receiver are not same';
					}
				} else {
					$send_to = trim( $receipt['to'] );

					if ( $send_to === $receiver_id ) {
						$etherObj           = ConverterUtils::convert_to_ether( $receipt['value'], 'ether' );
						self::$ether_amount = $etherObj[0]->toString();
					} else {
						return 'receiver are not same';
					}
				}
			}
			return 'success';
		}

		/**
		 * Verify transaction based on the provided amount.
		 *
		 * @param string $amount The amount to verify.
		 * @return bool
		 */
		public function cpmw_tx_verification( $receipt, $amount, $receiver_id ) {

			$result = self::get_transaction_type( $receipt, $receiver_id );

			if ( $result === 'receiver are not same' ) {
				return $result;
			}

			if ( self::$ether_amount !== false ) {

				$actualTokenAmount = ConverterUtils::convert_to_wei( $amount, 'ether' );
				$actualTokenAmount = $actualTokenAmount->toString();

				if ( self::$ether_amount === $actualTokenAmount ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Convert token amount to Ether value.
		 *
		 * @param string $tk_amount The token amount.
		 * @return mixed
		 */
		private function convert_token_amount( $tk_amount ) {
			$amount = substr( $tk_amount, 74 ); // Extract the amount from the transaction data (skip first 74 characters: 0x + function signature)

			$etherObj = ConverterUtils::convert_to_ether( $amount, 'ether' ); // Convert amountWei to 0.0101
			$value    = $etherObj[0]->toString();

			return $value;
		}
	}
}



