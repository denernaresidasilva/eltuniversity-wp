<?php
/**
 * QR Code Functionality for Magic Login
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use MagicLogin\Dependencies\Endroid\QrCode\QrCode as EndroidQrCode;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\PngWriter;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\SvgWriter;
use MagicLogin\Dependencies\Endroid\QrCode\Encoding\Encoding;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;

/**
 * Class QR
 */
class QR {

	/**
	 * Setup the QR code functionality.
	 *
	 * @return void
	 */
	public static function setup() {
		add_action( 'init', [ __CLASS__, 'add_rewrite_rule' ] );
		add_filter( 'query_vars', [ __CLASS__, 'add_query_var' ] );
		add_action( 'template_redirect', [ __CLASS__, 'handle_qr_endpoint' ] );
	}

	/**
	 * Add a rewrite rule for the QR code endpoint.
	 *
	 * @return void
	 */
	public static function add_rewrite_rule() {
		add_rewrite_rule( '^magic-login-qr/?$', 'index.php?magic_login_qr=1', 'top' );
	}

	/**
	 * Add the query variable for the QR code endpoint.
	 *
	 * @param array $vars The existing query variables.
	 *
	 * @return array The modified query variables.
	 */
	public static function add_query_var( $vars ) {
		$vars[] = 'magic_login_qr';

		return $vars;
	}

	/**
	 * Handle the QR code endpoint.
	 *
	 * @return void
	 */
	public static function handle_qr_endpoint() {
		if ( get_query_var( 'magic_login_qr' ) ) {

			$encoded_url = isset( $_GET['url'] ) ? sanitize_text_field( $_GET['url'] ) : ''; // phpcs:ignore
			$decoded_url = base64_decode( rawurldecode( $encoded_url ) ); // phpcs:ignore

			if ( empty( $decoded_url ) || ! filter_var( $decoded_url, FILTER_VALIDATE_URL ) ) {
				wp_die( 'Invalid or missing login URL', 400 );
			}

			// png is safer for emails
			$type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'png'; // phpcs:ignore
			nocache_headers();
			header( 'Content-Type: ' . self::get_mime_type( $type ) );
			echo self::generate_image( $decoded_url, 3, $type, false ); // phpcs:ignore
			exit;
		}
	}

	/**
	 * Generate a QR code image.
	 *
	 * @param string $url    The URL to encode in the QR code.
	 * @param int    $scale  The scale of the QR code (default is 3).
	 * @param string $type   The type of image to generate ('png' or 'svg').
	 * @param bool   $base64 Whether to return the image as a base64 string (default is true).
	 *
	 * @return string
	 */
	public static function generate_image( $url, $scale = 3, $type = 'svg', $base64 = true ) {

		/**
		 * Filter the URL before encoding it into a QR code.
		 *
		 * @hook magic_login_qr_url
		 *
		 * @param string $url The URL to encode in the QR code.
		 *
		 * @return string The modified URL.
		 * @since 2.5
		 */
		$url = apply_filters( 'magic_login_qr_url', $url );

		/**
		 * Filter the type of output (e.g., png or svg).
		 *
		 * @hook magic_login_qr_type
		 *
		 * @param string $type The type of image to generate ('png' or 'svg').
		 *
		 * @return string The modified type.
		 */
		$type = apply_filters( 'magic_login_qr_type', $type );

		/**
		 * Filter the options used for QR generation.
		 *
		 * @hook magic_login_qr_options
		 *
		 * @param array $options QR code generation options.
		 *
		 * @return array The modified options.
		 * @since 2.5
		 */
		$options = apply_filters(
			'magic_login_qr_options',
			[
				'encoding' => new Encoding( 'UTF-8' ),
				'eccLevel' => new ErrorCorrectionLevelLow(),
				'size'     => $scale * 50, // scale = 5 → 250px
				'margin'   => 10,
			]
		);

		$qr_code = EndroidQrCode::create( $url )
								->setEncoding( $options['encoding'] )
								->setErrorCorrectionLevel( $options['eccLevel'] )
								->setSize( $options['size'] )
								->setMargin( $options['margin'] );

		// Fallback to SVG if GD is not available
		if ( strtolower( $type ) === 'png' && ! function_exists( 'imagecreatetruecolor' ) ) {
			$type = 'svg';
		}

		$writer = strtolower( $type ) === 'svg' ? new SvgWriter() : new PngWriter();
		$result = $writer->write( $qr_code );

		return $base64 ? $result->getDataUri() : $result->getString();
	}

	/**
	 * Get the URL for the QR code image.
	 *
	 * @param string $url The URL to encode in the QR code.
	 *
	 * @return string The URL for the QR code image.
	 */
	public static function get_image_src( string $url ): string {
		$encoded = rawurlencode( base64_encode( $url ) ); // phpcs:ignore

		/**
		 * Filter the final QR image source URL (after encoding).
		 *
		 * @hook  magic_login_qr_image_src
		 *
		 * @param string $src          The final QR image source URL.
		 * @param string $original_url The original URL being encoded.
		 *
		 * @return string  The modified QR image source URL.
		 * @since 2.5
		 */
		return apply_filters( 'magic_login_qr_image_src', home_url( '/magic-login-qr?url=' . $encoded ), $url );
	}

	/**
	 * Generate an HTML <img> tag for the QR code.
	 *
	 * @param string $url   The URL to encode in the QR code.
	 * @param int    $width The width of the image (default is 150).
	 * @param array  $args  Optional customization for class, alt, width, etc.
	 *
	 * @return string
	 */
	public static function get_img_tag( string $url, int $width = 150, array $args = [] ): string {
		$src  = self::get_image_src( $url );
		$args = wp_parse_args(
			$args,
			[
				'class' => 'magic-login-qr',
				'alt'   => esc_attr__( 'Scan to login', 'magic-login' ),
				'width' => $width,
			]
		);

		/**
		 * Filter the QR code <img> tag attributes before rendering.
		 *
		 * @hook magic_login_qr_img_args
		 *
		 * @param array  $args {
		 *     Arguments for customizing the QR image tag.
		 *
		 *     @type string $class CSS class.
		 *     @type string $alt   Alternative text.
		 *     @type int    $width Image width.
		 * }
		 * @param string $url The login URL being encoded.
		 *
		 * @since 2.5
		 */
		$args = apply_filters( 'magic_login_qr_img_args', $args, $url );

		return sprintf(
			'<img src="%s" width="%d" alt="%s"%s />',
			esc_url( $src ),
			(int) $args['width'],
			esc_attr( $args['alt'] ),
			$args['class'] ? ' class="' . esc_attr( $args['class'] ) . '"' : ''
		);
	}

	/**
	 * Get the MIME type for the QR code image.
	 *
	 * @param string $type The type of image (e.g., 'png', 'svg').
	 *
	 * @return string
	 */
	public static function get_mime_type( string $type ): string {
		switch ( strtolower( $type ) ) {
			case 'svg':
				$mime = 'image/svg+xml';
				break;
			case 'png':
			default:
				$mime = 'image/png';
				break;
		}

		/**
		 * Allow filtering the final MIME type.
		 *
		 * @hook  magic_login_qr_mime_type
		 *
		 * @param string $mime The MIME type of the image.
		 * @param string $type The type of image (e.g., 'png', 'svg').
		 *
		 * @return string The modified MIME type.
		 * @since 2.5
		 */
		return apply_filters( 'magic_login_qr_mime_type', $mime, $type );
	}

}
