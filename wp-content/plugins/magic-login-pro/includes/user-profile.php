<?php
/**
 * User Profile settings
 *
 * @package MagicLogin
 */

namespace MagicLogin\UserProfile;

use MagicLogin\SmsService;
use function MagicLogin\Utils\sanitize_phone_number;
use const MagicLogin\Constants\DISABLE_USER_META;
use function MagicLogin\Utils\create_login_link;
use function MagicLogin\Utils\current_user_can_control_user;
use \WP_Error as WP_Error;
use \WP_User as WP_User;
use const MagicLogin\Constants\PHONE_NUMBER_META;
use const MagicLogin\Constants\TOKEN_USER_META;
use const MagicLogin\Constants\USER_TTL_META;
use const MagicLogin\Constants\USER_TOKEN_VALIDITY_META;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	add_action( 'show_user_profile', __NAMESPACE__ . '\\register_user_fields', 8 );
	add_action( 'edit_user_profile', __NAMESPACE__ . '\\register_user_fields', 8 );
	add_action( 'personal_options_update', __NAMESPACE__ . '\\save_profile_info' );
	add_action( 'edit_user_profile_update', __NAMESPACE__ . '\\save_profile_info' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\register_scripts' );
	add_action( 'wp_ajax_magic_login_create_login_link_for_user', __NAMESPACE__ . '\\create_login_link_for_user' ); // ajax for logged in users
	add_action( 'wp_ajax_reset_user_magic_login_tokens', __NAMESPACE__ . '\\reset_user_magic_login_tokens' );
	add_action( 'wp_ajax_magic_login_send_link_to_user', __NAMESPACE__ . '\\send_login_link_to_user' );
}

/**
 * Ajax callback for the creating login link on user profile
 *
 * @since 1.4
 */
function create_login_link_for_user() {
	if ( ! check_ajax_referer( 'magic-login-user-profile-nonce', 'nonce', false ) ) {
		wp_send_json_error( esc_html__( 'Login link could not be generated because: Invalid ajax nonce!', 'magic-login' ) );
	}

	if ( ! current_user_can_control_user() ) {
		wp_send_json_error( esc_html__( 'Login link could not be generated because: You cannot perform this action!', 'magic-login' ) );
	}

	$user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
	if ( ! $user_id ) {
		wp_send_json_error( esc_html__( 'Login link could not be generated because: An invalid user id has been sent!', 'magic-login' ) );
	}

	$user       = get_user_by( 'ID', $user_id );
	$login_link = create_login_link( $user );
	$qr_src     = \MagicLogin\QR::get_image_src( $login_link );

	wp_send_json_success(
		[
			'link'   => $login_link,
			'qr_url' => $qr_src,
		]
	);
}

/**
 * Register assets
 *
 * @param string $hook Hook name
 *
 * @since 1.4
 */
function register_scripts( $hook ) {
	if ( in_array( $hook, [ 'user-edit.php', 'profile.php' ], true ) && current_user_can_control_user() ) {
		wp_enqueue_script( 'magic-login-user-profile', MAGIC_LOGIN_PRO_URL . 'dist/js/user-profile.js', [ 'jquery' ], MAGIC_LOGIN_PRO_VERSION, true );

		wp_localize_script(
			'magic-login-user-profile',
			'magic_login_user_profile_object',
			[
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'magic-login-user-profile-nonce' ),
				'i18n_sending' => esc_html__( 'Sending login link to user...', 'magic-login' ),
				'i18n_no_link' => esc_html__( 'Please generate a login link first.', 'magic-login' ),
			]
		);

	}
}

/**
 * Register user fields on profile page
 *
 * @param \WP_User $user User Object
 *
 * @since 1.4
 */
function register_user_fields( $user ) {
	$user_id = get_current_user_id();
	if ( isset( $_GET['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = absint( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$disabled = (bool) get_user_meta( $user->ID, DISABLE_USER_META, true );
	$settings = \MagicLogin\Utils\get_settings();

	?>
	<?php if ( current_user_can_control_user() ) : ?>
		<h2><?php esc_html_e( 'Magic Login', 'magic-login' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr class="disable-magic-login">
				<th scope="row"><?php esc_html_e( 'Magic Login Disabled', 'magic-login' ); ?></th>
				<td>
					<label for="disable_magic_login">
						<input name="disable_magic_login" <?php checked( $disabled, 1 ); ?> type="checkbox" id="disable_magic_login" value="1">
						<?php esc_html_e( 'Disable magic login for this user', 'magic-login' ); ?>
					</label>
				</td>
			</tr>
			<tr class="generate-magic-login" style="<?php echo $disabled ? 'display:none;' : ''; ?>">
				<th scope="row">
					<button type="button" name="create_new_magic_login" id="create_new_magic_login" class="button button-secondary" value="<?php echo absint( $user_id ); ?>">
						<?php esc_html_e( 'Create Magic Login Link', 'magic-login' ); ?>
					</button>
				</th>
				<td>
					<div id="magic_login_user_link_wrapper" style="display: none;">
						<label><input type="text" id="magic_login_user_link" style="width: 80%;" name="magic_login_user_link" value="" readonly></label>
						<button type="button" id="send_magic_login_link_to_user" class="button button-secondary" style="margin-left: 5px;">
							<?php esc_html_e( 'Send this link to user', 'magic-login' ); ?>
						</button>
						<p class="description">

							<i>
								<?php
								echo wp_kses_post(
									sprintf(
									/* translators: %1$s:  redirect query arg. */
										__( 'You can append <code>%1$s</code> to the login URL for automatic redirection in case you have not specified the redirection URL from the plugin settings.', 'magic-login' ),
										'&redirect_to=$URL'
									)
								);
								?>
							</i>
						</p>
						<div id="magic_login_send_msg" style="display: none; margin-top: 5px;"></div>
					</div>
					<div id="magic_login_user_qr_wrapper" style="display: none; margin-top: 10px;">
						<img id="magic_login_user_qr_img" src="" alt="<?php esc_attr_e( 'Scan to login', 'magic-login' ); ?>" style="max-width: 200px;" />
					</div>

				</td>
			</tr>
			<tr class="reset-magic-login-user-tokens">
				<th scope="row" >
					<button type="button" name="reset_magic_login_user_token" id="reset_magic_login_user_token" class="button button-secondary" value="<?php echo absint( $user_id ); ?>">
						<?php esc_html_e( 'Reset Magic Login Links', 'magic-login' ); ?>
					</button>
				</th>
				<td>
					<div id="magic_login_reset_msg" style="display: none;"></div>
				</td>
			</tr>
			<tr class="user-ttl-settings" style="<?php echo $disabled ? 'display:none;' : ''; ?>">
				<th scope="row"><?php esc_html_e( 'Token TTL', 'magic-login' ); ?></th>
				<td>
					<input type="number" name="<?php echo esc_attr( USER_TTL_META ); ?>" id="<?php echo esc_attr( USER_TTL_META ); ?>"
						value="<?php echo esc_attr( get_user_meta( $user->ID, USER_TTL_META, true ) ); ?>" class="small-text" min="1" />
					<?php esc_html_e( 'minutes', 'magic-login' ); ?>
					<p class="description">
						<?php esc_html_e( 'Override the default token TTL (Time To Live) for this user. Leave empty to use the global setting.', 'magic-login' ); ?>
					</p>
				</td>
			</tr>
			<tr class="user-token-validity-settings" style="<?php echo $disabled ? 'display:none;' : ''; ?>">
				<th scope="row"><?php esc_html_e( 'Token Validity', 'magic-login' ); ?></th>
				<td>
					<input type="number" name="<?php echo esc_attr( USER_TOKEN_VALIDITY_META ); ?>" id="<?php echo esc_attr( USER_TOKEN_VALIDITY_META ); ?>"
						value="<?php echo esc_attr( get_user_meta( $user->ID, USER_TOKEN_VALIDITY_META, true ) ); ?>" class="small-text" min="1" />
					<p class="description">
						<?php esc_html_e( 'Override the default token validity period for this user. Leave empty to use the global setting.', 'magic-login' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>
		<div id="magic-login-user-profile-ajax-msg" style="display:none;">

		</div>
	<?php endif; ?>
	<?php if ( $settings['sms']['enable'] ) : ?>
		<table class="form-table">
			<tr>
				<th>
					<label for="<?php echo esc_attr( PHONE_NUMBER_META ); ?>?>">
						<?php esc_html_e( 'Phone Number', 'magic-login' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
						   name="<?php echo esc_attr( PHONE_NUMBER_META ); ?>"
						   id="<?php echo esc_attr( PHONE_NUMBER_META ); ?>?>"
						   value="<?php echo esc_attr( SmsService::get_user_phone_number( $user->ID ) ); ?>"
						   class="regular-text"
					/>
					<p class="description">
						<?php esc_html_e( 'Enter the phone number to receive Magic Login links via SMS.', 'magic-login' ); ?>
					</p>
				</td>
			</tr>
		</table>
	<?php endif; ?>
	<?php
}

/**
 * Save user preferences
 *
 * @param int $user_id User ID.
 *
 * @return void|false
 * @since 1.4
 */
function save_profile_info( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( current_user_can_control_user() ) {
		if ( isset( $_POST['disable_magic_login'] ) && true === (bool) $_POST['disable_magic_login'] ) {
			update_user_meta( $user_id, DISABLE_USER_META, 1 );
		} else {
			delete_user_meta( $user_id, DISABLE_USER_META );
		}
	}

	if ( isset( $_POST[ PHONE_NUMBER_META ] ) ) {
		$current_phone_number = SmsService::get_user_phone_number( $user_id );
		$phone_number         = sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) );
		if ( $phone_number !== $current_phone_number && SmsService::is_phone_number_taken( $phone_number ) ) { // the phone number is already used by another user
			wp_die( esc_html__( 'Unable to process your request. Please try a different phone number.', 'magic-login' ) );
		} else {
			update_user_meta( $user_id, PHONE_NUMBER_META, $phone_number );
		}
	}

	// Save user-specific TTL and token validity settings.
	if ( current_user_can_control_user() ) {
		$ttl            = isset( $_POST[ USER_TTL_META ] ) ? absint( $_POST[ USER_TTL_META ] ) : '';
		$token_validity = isset( $_POST[ USER_TOKEN_VALIDITY_META ] ) ? absint( $_POST[ USER_TOKEN_VALIDITY_META ] ) : '';

		if ( ! empty( $ttl ) ) {
			update_user_meta( $user_id, USER_TTL_META, $ttl );
		} else {
			delete_user_meta( $user_id, USER_TTL_META );
		}

		if ( ! empty( $token_validity ) ) {
			update_user_meta( $user_id, USER_TOKEN_VALIDITY_META, $token_validity );
		} else {
			delete_user_meta( $user_id, USER_TOKEN_VALIDITY_META );
		}
	}
}

/**
 * Reset user magic login tokens
 *
 * @return void
 */
function reset_user_magic_login_tokens() {
	if ( ! check_ajax_referer( 'magic-login-user-profile-nonce', 'nonce', false ) ) {
		wp_send_json_error( esc_html__( 'Reset failed: Invalid ajax nonce!', 'magic-login' ) );
	}

	if ( ! current_user_can_control_user() ) {
		wp_send_json_error( esc_html__( 'Reset failed: You cannot perform this action!', 'magic-login' ) );
	}

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	if ( ! $user_id ) {
		wp_send_json_error( esc_html__( 'Reset failed: Invalid user ID!', 'magic-login' ) );
	}

	delete_user_meta( $user_id, TOKEN_USER_META );

	wp_send_json_success( esc_html__( 'Magic login links have been reset successfully.', 'magic-login' ) );
}

/**
 * Send login link to user
 *
 * @return void
 * @since 2.6
 */
function send_login_link_to_user() {
	if ( ! check_ajax_referer( 'magic-login-user-profile-nonce', 'nonce', false ) ) {
		wp_send_json_error( esc_html__( 'Sending failed: Invalid ajax nonce!', 'magic-login' ) );
	}

	if ( ! current_user_can_control_user() ) {
		wp_send_json_error( esc_html__( 'Sending failed: You cannot perform this action!', 'magic-login' ) );
	}

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	if ( ! $user_id ) {
		wp_send_json_error( esc_html__( 'Sending failed: Invalid user ID!', 'magic-login' ) );
	}

	$login_link = isset( $_POST['login_link'] ) ? esc_url_raw( wp_unslash( $_POST['login_link'] ) ) : '';
	if ( empty( $login_link ) ) {
		wp_send_json_error( esc_html__( 'Sending failed: No login link provided!', 'magic-login' ) );
	}

	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		wp_send_json_error( esc_html__( 'Sending failed: User not found!', 'magic-login' ) );
	}

	// Use the standard LoginManager to send the login link
	$result = \MagicLogin\LoginManager::send_login_link( $user, $login_link );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			sprintf(
			/* translators: %s: error message */
				esc_html__( 'Sending failed: %s', 'magic-login' ),
				$result->get_error_message()
			)
		);
	}

	wp_send_json_success( esc_html__( 'Login link has been sent to the user successfully.', 'magic-login' ) );
}
