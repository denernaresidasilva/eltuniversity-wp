<?php
/**
 * Recaptcha Service
 *
 * @package MagicLogin
 */

namespace MagicLogin;

/**
 * Class ReCaptchaService
 */
class ReCaptchaService {
	/**
	 * Site key
	 *
	 * @var string
	 */
	private $site_key;

	/**
	 * Site secret
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Captcha type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Constructor
	 *
	 * @param string $site_key   Site key
	 * @param string $secret_key Site secret
	 * @param string $type       Captcha type
	 */
	public function __construct( $site_key, $secret_key, $type ) {
		$this->site_key   = $site_key;
		$this->secret_key = $secret_key;
		$this->type       = $type;
	}

	/**
	 * Render the ReCaptcha
	 *
	 * @return string
	 */
	public function render() {
		$html               = '';
		$default_attributes = [
			'data-sitekey' => esc_attr( $this->site_key ),
		];

		if ( 'v2_checkbox' === $this->type ) {
			/**
			 * Filter the ReCaptcha attributes before rendering.
			 *
			 * @hook magic_login_recaptcha_attributes
			 *
			 * @param array  $default_attributes Default attributes for the ReCaptcha.
			 * @param string $type               Type of the ReCaptcha.
			 *
			 * @return array Modified attributes for the ReCaptcha.
			 */
			$attributes  = apply_filters( 'magic_login_recaptcha_attributes', $default_attributes, $this->type );
			$attr_string = '';
			foreach ( $attributes as $key => $value ) {
				$attr_string .= $key . '="' . esc_attr( $value ) . '" ';
			}
			$html .= '<div id="magic-login-grecaptcha" class="g-recaptcha" ' . $attr_string . '></div>';
		} elseif ( 'v2_invisible' === $this->type ) {
			$default_attributes['data-callback'] = 'magicLoginInvisibleRecaptchaSubmit';
			$default_attributes['data-size']     = 'invisible';
			$default_attributes['data-badge']    = 'inline';
			$attributes                          = apply_filters( 'magic_login_recaptcha_attributes', $default_attributes, $this->type );
			$attr_string                         = '';
			foreach ( $attributes as $key => $value ) {
				$attr_string .= $key . '="' . esc_attr( $value ) . '" ';
			}
			$html .= '<div id="magic-login-grecaptcha" class="g-recaptcha" ' . $attr_string . '></div>';
		} elseif ( 'v3' === $this->type ) {
			$html .= '<input type="hidden" name="g-recaptcha-response" class="g-recaptcha-v3" id="recaptchaResponse" data-sitekey="' . esc_attr( $this->site_key ) . '">';
		}

		return $html;
	}


	/**
	 * Verify the token
	 *
	 * @param string $token ReCaptcha token
	 *
	 * @return bool
	 */
	public function verify( $token ) {
		$url      = 'https://www.google.com/recaptcha/api/siteverify';
		$response = wp_remote_post(
			$url,
			array(
				'body' => array(
					'secret'   => $this->secret_key,
					'response' => $token,
				),
			)
		);

		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body, true );

		return isset( $result['success'] ) && $result['success'];
	}

	/**
	 * Get script URL
	 *
	 * @return string
	 */
	public function get_script_url() {
		if ( 'v2_checkbox' === $this->type ) {
			return 'https://www.google.com/recaptcha/api.js';
		} elseif ( 'v2_invisible' === $this->type ) {
			return 'https://www.google.com/recaptcha/api.js?render=explicit';
		} elseif ( 'v3' === $this->type ) {
			return 'https://www.google.com/recaptcha/api.js?render=' . $this->site_key;
		}
	}

	/**
	 * Get inline script
	 *
	 * @return string
	 */
	public function get_inline_script() {
		if ( 'v3' === $this->type ) {
			return <<<EOT
							document.addEventListener('DOMContentLoaded', function() {
							    document.querySelectorAll('form .magic-login-captcha-wrapper').forEach(function(wrapper) {
							        var form = wrapper.closest('form');
							        form.addEventListener('submit', function(event) {
							            event.preventDefault();
							            grecaptcha.ready(function() {
							                var recaptchaResponse = form.querySelector('#recaptchaResponse');
							                var siteKey = recaptchaResponse.getAttribute('data-sitekey');
							                grecaptcha.execute(siteKey, {action: 'submit'}).then(function(token) {
							                    recaptchaResponse.value = token;
							                    form.submit();
							                });
							            });
							        });
							    });
							});
					EOT;
		} elseif ( 'v2_invisible' === $this->type ) {
			return <<<EOT
					document.addEventListener('DOMContentLoaded', function() {
					    // Event delegation for the form submit event
					    document.addEventListener('submit', function(event) {
					        // Check if the form contains the magic-login-captcha-wrapper
					        var wrapper = event.target.querySelector('.magic-login-captcha-wrapper');
					        if (wrapper) {
					            // Find the closest form element
					            var form = wrapper.closest('form');
					            if (form) {
					                // Prevent the default form submission
					                event.preventDefault();
					                grecaptcha.execute();
					            }
					        }
					    });

					    magicLoginInvisibleRecaptchaOnloadCallback();
					});

					 var magicLoginInvisibleRecaptchaOnloadCallback = function() {
					      var recaptchaElement = document.getElementById('magic-login-grecaptcha');
					      var siteKey = recaptchaElement.getAttribute('data-sitekey');
					      if(siteKey){
							  grecaptcha.ready(function() {
									grecaptcha.render('magic-login-grecaptcha', {
						              'sitekey' : siteKey
						            });
					          });
				          }
			        };

					function magicLoginInvisibleRecaptchaSubmit(token) {
						var wrapper = document.querySelector('.magic-login-captcha-wrapper');
						if (wrapper) {
							var form = wrapper.closest('form');
						    var responseInput = document.createElement('input');
						    responseInput.setAttribute('type', 'hidden');
						    responseInput.setAttribute('name', 'g-recaptcha-response');
						    responseInput.setAttribute('value', token);
						    form.appendChild(responseInput);
						    form.submit();
					    }
					}

EOT;
		} elseif ( 'v2_checkbox' === $this->type ) {
			return <<<EOT
				document.addEventListener('DOMContentLoaded', function() {
					document.querySelectorAll('form .magic-login-captcha-wrapper').forEach(function(wrapper) {
						var form = wrapper.closest('form');
						var siteKey = wrapper.getAttribute('data-sitekey');
						form.addEventListener('submit', function(event) {
							var response = grecaptcha.getResponse();
							if (response.length === 0) {
								event.preventDefault();
								var errorElement = document.createElement('div');
								errorElement.className = 'magic-login-captcha-error';
								var spamProtectionMsg = form.getAttribute('data-spam-protection-msg');
								errorElement.innerText = spamProtectionMsg;
								wrapper.appendChild(errorElement);
								setTimeout(function() {
									if (errorElement.parentNode) {
										errorElement.parentNode.removeChild(errorElement);
									}
								}, 4000);
							}
						});
					});
				});
				EOT;
		}

		return '';
	}

}
