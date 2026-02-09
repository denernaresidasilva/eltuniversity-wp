<?php
/**
 * Helper
 *
 * @package TutorPro\Tools
 * @author  Themeum<support@themeum.com>
 * @link    https://themeum.com
 * @since   3.7.1
 */

namespace TutorPro\Tools;

/**
 * Class Helper
 *
 * @since 3.7.1
 */
class Helper {
	/**
	 * Recursively unserialize all serialized values in an array
	 *
	 * @since 3.6.0
	 *
	 * @param mixed $data The input data (array or string).
	 *
	 * @return mixed The processed data with unserialized values
	 */
	public static function deep_maybe_unserialize( $data ) {
		// Handle arrays recursively.
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::deep_maybe_unserialize( $value );
			}
			return $data;
		}

		// Handle objects recursively (convert to array first).
		if ( is_object( $data ) ) {
			$data = (array) $data;
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::deep_maybe_unserialize( $value );
			}
			return (object) $data;
		}

		// Handle strings (check if serialized).
		if ( is_string( $data ) ) {
			// Skip if empty or doesn't look serialized.
			if ( empty( $data ) || ! preg_match( '/^[aOs]:[\d]+:/', $data ) ) {
				return $data;
			}

			$unserialized = maybe_unserialize( $data );
			if ( $data !== $unserialized ) {
				return self::deep_maybe_unserialize( $unserialized );
			}
		}

		return $data;
	}
}
