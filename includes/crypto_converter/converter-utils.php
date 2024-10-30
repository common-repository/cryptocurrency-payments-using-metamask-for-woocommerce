<?php

namespace CPMW\CONVERTER;

use InvalidArgumentException;
use CPMW\CONVERTER\BigInteger as BigNumber;
/**
 * Class ConverterUtils
 *
 * This class provides utility functions for converting cryptocurrency values
 * between different units. It includes a constant array that defines various
 * units of cryptocurrency, representing their values in terms of wei.
 * The units are derived from the ethjs-unit library and include both
 * standard and alternative names for each unit.
 */

if ( ! class_exists( 'ConverterUtils' ) ) {
	class ConverterUtils {

		/**
		 * UNITS
		 *
		 * This constant array defines various units of cryptocurrency,
		 * representing their values in terms of wei. The units are
		 * derived from the ethjs-unit library and include both
		 * standard and alternative names for each unit.
		 *
		 * @const array
		 * @var array<string, string> $UNITS An associative array where
		 * each key is the name of the unit and the value is its
		 * corresponding value in wei.
		 */
		const UNITS = array(
			'noether'    => '0',
			'wei'        => '1',
			'kwei'       => '1000',
			'Kwei'       => '1000',
			'babbage'    => '1000',
			'femtoether' => '1000',
			'mwei'       => '1000000',
			'Mwei'       => '1000000',
			'lovelace'   => '1000000',
			'picoether'  => '1000000',
			'gwei'       => '1000000000',
			'Gwei'       => '1000000000',
			'shannon'    => '1000000000',
			'nanoether'  => '1000000000',
			'nano'       => '1000000000',
			'szabo'      => '1000000000000',
			'microether' => '1000000000000',
			'micro'      => '1000000000000',
			'finney'     => '1000000000000000',
			'milliether' => '1000000000000000',
			'milli'      => '1000000000000000',
			'ether'      => '1000000000000000000',
			'kether'     => '1000000000000000000000',
			'grand'      => '1000000000000000000000',
			'mether'     => '1000000000000000000000000',
			'gether'     => '1000000000000000000000000000',
			'tether'     => '1000000000000000000000000000000',
		);

		/**
		 * Checks if a given string is prefixed with '0x'.
		 *
		 * @param string $value The value to check
		 * @return bool True if the value is zero-prefixed, false otherwise
		 */
		public static function has_zero_hex_prefix( $value ) {
			if ( ! is_string( $value ) ) {
				throw new InvalidArgumentException( 'The value passed to has_zero_hex_prefix function must be a string.' );
			}
			return ( strpos( $value, '0x' ) === 0 );
		}

		/**
		 * Strips the zero prefix from a hexadecimal string value if present.
		 *
		 * @param string $hexValue The hexadecimal value to strip the zero prefix from
		 * @return string The hexadecimal value without the zero prefix
		 */
		public static function strip_zero_prefix( $hexValue ) {
			if ( self::has_zero_hex_prefix( $hexValue ) ) {
				$replacementCount = 1;
				return str_replace( '0x', '', $hexValue, $replacementCount );
			}
			return $hexValue;
		}

		/**
		 * Checks if a given numeric string represents a negative value.
		 *
		 * @param string $numericString The numeric string to check
		 * @return bool Whether the numeric string represents a negative value
		 */
		public static function is_negative( $numericString ) {   
			if ( ! is_string( $numericString ) ) {
				throw new InvalidArgumentException( 'The input to is_negative function must be a string.' );
			}
			return ( strpos( $numericString, '-' ) === 0 );
		}

		/**
		 * convert_to_wei
		 * Converts a number from a specific unit to wei.
		 *
		 * @param BigNumber|string $valueToConvert The number to convert
		 * @param string           $fromUnit The unit to convert from
		 * @return \phpseclib\Math\BigInteger The converted value in wei
		 */
		public static function convert_to_wei( $valueToConvert, $fromUnit ) {
			// Validate that the input number is either a string or an instance of BigNumber
			if ( ! is_string( $valueToConvert ) && ! ( $valueToConvert instanceof BigNumber ) ) {
				throw new InvalidArgumentException( 'convert_to_wei value must be string or BigNumber.' );
			}
			// Convert the input number to a BigNumber instance
			$bigNumberValue = self::convert_to_bignumber( $valueToConvert );

			// Ensure the unit is a string
			if ( ! is_string( $fromUnit ) ) {
				throw new InvalidArgumentException( 'convert_to_wei unit must be string.' );
			}
			// Check if the provided unit is supported
			if ( ! isset( self::UNITS[ $fromUnit ] ) ) {
				throw new InvalidArgumentException( 'convert_to_wei doesn\'t support ' . $fromUnit . ' unit.' );
			}
			// Create a BigNumber instance for the conversion factor based on the unit
			$conversionFactor = new BigNumber( self::UNITS[ $fromUnit ] );

			// Check if the BigNumber representation is an array (indicating a fractional number)
			if ( is_array( $bigNumberValue ) ) {
				// Extract whole and fractional parts, along with their length and sign
				list($wholePart, $fractionalPart, $fractionLength, $negativeSign) = $bigNumberValue;

				// Validate that the fraction length does not exceed the unit's length
				if ( $fractionLength > strlen( self::UNITS[ $fromUnit ] ) ) {
					throw new InvalidArgumentException( 'convert_to_wei fraction part is out of limit.' );
				}
				// Multiply the whole part by the conversion factor
				$wholePart = $wholePart->multiply( $conversionFactor );

				// Determine the base for the fraction based on the BigInteger mode
				switch ( MATH_BIGINTEGER_MODE ) {
					case $wholePart::MODE_GMP:
						static $two; // Static variable for GMP mode
						$powerBase = gmp_pow( gmp_init( 10 ), (int) $fractionLength );
						break;
					case $wholePart::MODE_BCMATH:
						$powerBase = bcpow( '10', (string) $fractionLength, 0 );
						break;
					default:
						$powerBase = pow( 10, (int) $fractionLength );
						break;
				}
				// Create a BigNumber instance for the base
				$baseValue = new BigNumber( $powerBase );
				// Calculate the fractional part and divide by the base
				$fractionalPart = $fractionalPart->multiply( $conversionFactor )->divide( $baseValue )[0];

				// Return the final result, considering the sign of the number
				if ( $negativeSign !== false ) {
					return $wholePart->add( $fractionalPart )->multiply( $negativeSign );
				}
				return $wholePart->add( $fractionalPart );
			}

			// For non-fractional numbers, simply multiply by the conversion factor
			return $bigNumberValue->multiply( $conversionFactor );
		}

		/**
		 * Converts a number from a specified unit to ether.
		 *
		 * @param BigNumber|string|int $amount The number to convert.
		 * @param string               $from_unit The unit of the number (e.g., 'kether').
		 * @return BigNumber The equivalent value in ether.
		 */
		public static function convert_to_ether( $amount, $from_unit ) {
			// Convert the amount from the specified unit to wei
			$weiValue = self::convert_to_wei( $amount, $from_unit );
			// Create a BigNumber instance for the ether unit
			$etherUnitValue = new BigNumber( self::UNITS['ether'] );

			// Divide the wei value by the ether unit to get the equivalent ether value
			return $weiValue->divide( $etherUnitValue );
		}

		/**
		 * Converts a number or number string to a BigNumber instance.
		 *
		 * @param BigNumber|string|int $inputNumber The number to convert.
		 * @return BigNumber The converted BigNumber instance.
		 * @throws InvalidArgumentException If the input is not valid.
		 */
		public static function convert_to_bignumber( $inputNumber ) {
			// Check if the input is an instance of BigNumber
			if ( $inputNumber instanceof BigNumber ) {
				$bigNumberInstance = $inputNumber; // Assign the BigNumber instance to $bigNumberInstance
			} elseif ( is_int( $inputNumber ) ) {
				$bigNumberInstance = new BigNumber( $inputNumber ); // Convert integer to BigNumber
			} elseif ( is_numeric( $inputNumber ) ) {
				$inputNumber = (string) $inputNumber; // Convert number to string

				// Check if the number is negative
				if ( self::is_negative( $inputNumber ) ) {
					$negativeCount = 1;
					$inputNumber = str_replace( '-', '', $inputNumber, $negativeCount ); // Remove negative sign
					$negativeMultiplier = new BigNumber( -1 ); // Store negative multiplier
				}
				// Check if the number has a decimal point
				if ( strpos( $inputNumber, '.' ) > 0 ) {
					$numberComponents = explode( '.', $inputNumber ); // Split into whole and fractional parts

					// Validate the number of components
					if ( count( $numberComponents ) > 2 ) {
						throw new InvalidArgumentException( 'convert_to_bignumber number must be a valid number.' ); // Throw error for invalid number
					}
					$wholePart = $numberComponents[0]; // Whole part
					$fractionalPart = $numberComponents[1]; // Fractional part

					// Return an array with whole, fraction, length, and negative sign
					return array(
						new BigNumber( $wholePart ),
						new BigNumber( $fractionalPart ),
						strlen( $numberComponents[1] ), // Length of the fractional part
						isset( $negativeMultiplier ) ? $negativeMultiplier : false, // Negative sign if applicable
					);
				} else {
					$bigNumberInstance = new BigNumber( $inputNumber ); // Convert to BigNumber if no decimal
				}
				// Apply negative multiplier if applicable
				if ( isset( $negativeMultiplier ) ) {
					$bigNumberInstance = $bigNumberInstance->multiply( $negativeMultiplier ); // Multiply by -1 if negative
				}
			} elseif ( is_string( $inputNumber ) ) {
				$inputNumber = mb_strtolower( $inputNumber ); // Convert string to lowercase

				// Check if the string is negative
				if ( self::is_negative( $inputNumber ) ) {
					$negativeCount = 1;
					$inputNumber = str_replace( '-', '', $inputNumber, $negativeCount ); // Remove negative sign
					$negativeMultiplier = new BigNumber( -1 ); // Store negative multiplier
				}
				// Check if the string is zero-prefixed or a valid hex string
				if ( self::has_zero_hex_prefix( $inputNumber ) || preg_match( '/^[0-9a-f]+$/i', $inputNumber ) === 1 ) {
					$inputNumber = self::strip_zero_prefix( $inputNumber ); // Strip zero prefix
					$bigNumberInstance = new BigNumber( $inputNumber, 16 ); // Convert hex string to BigNumber
				} elseif ( empty( $inputNumber ) ) {
					$bigNumberInstance = new BigNumber( 0 ); // Assign zero if empty
				} else {
					throw new InvalidArgumentException( 'convert_to_bignumber number must be valid hex string.' ); // Throw error for invalid hex string
				}
				// Apply negative multiplier if applicable
				if ( isset( $negativeMultiplier ) ) {
					$bigNumberInstance = $bigNumberInstance->multiply( $negativeMultiplier ); // Multiply by -1 if negative
				}
			} else {
				throw new InvalidArgumentException( 'convert_to_bignumber number must be BigNumber, string or int.' ); // Throw error for invalid type
			}
			return $bigNumberInstance; // Return the BigNumber instance
		}
	}
}
