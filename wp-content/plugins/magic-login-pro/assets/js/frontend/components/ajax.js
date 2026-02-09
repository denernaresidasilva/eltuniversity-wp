// Ajaxify the form submission for shortcode and block
window.magicLoginAjaxEnabled = true;
(function ($) {
	$(document).on('submit', '#magicloginform', function (e) {
		e.preventDefault();

		const $form = $(this);

		if (!checkCaptchaAndPreventSubmit($form)) {
			return false;
		}

		$form.trigger('magic-login:login:before-submit');

		$.post(
			$form.data('ajax-url'),
			{
				beforeSend() {
					// show spinner
					const spinnerImg = '<img src="' + $form.data('ajax-spinner') + '" alt="' + $form.data('ajax-sending-msg') + '" class="magic-login-spinner-image" />';
					const spinnerMessage = '<span class="magic-login-spinner-message">' + $form.data('ajax-sending-msg') + '</span>';
					const spinnerHtml = '<div class="magic-login-spinner-container">' + spinnerImg + spinnerMessage + '</div>';

					$('.magic-login-form-header').html(spinnerHtml);

					$form
						.find('.magic-login-submit ')
						.attr('disabled', 'disabled');

					$form.trigger('magic-login:login:before-send');
				},
				action: 'magic_login_ajax_request',
				data  : $('#magicloginform').serialize(),
			},
			function (response) {
				$('.magic-login-form-header').html(response.data.message);
				if (!response.data.show_form) {
					$form.hide();
				}

				if (response.data.code_form) {
					$form.parent().replaceWith(response.data.code_form);
					$('.magic-login-code-form-header').find('.info').html($(response.data.info).text()).show();
					$('#magiclogincodeform').find('.magic-login-code-cancel').hide();
					maybeInitializeSpamProtection();
				}

				if (response.data.registration_form) {
					$form.parent().replaceWith(response.data.registration_form);
					maybeInitializeSpamProtection();
				}

				if (!response.success) {
					maybeInitializeSpamProtection();
				}

				// Trigger a custom event after the successful AJAX request
				$form.trigger('magic-login:login:ajax-success', [response]);
			}
		).fail(function (response) {
			// Trigger a custom event after the failed AJAX request
			$form.trigger('magic-login:login:ajax-fail', [response]);
		}).always(function () {
			$form.find('.magic-login-submit ').attr('disabled', false);
			$form.trigger('magic-login:login:always');
		});
	});

	$(document).on('submit', '#magiclogincodeform', function (e) {
		e.preventDefault();

		const $form = $(this);

		if (!checkCaptchaAndPreventSubmit($form)) {
			return false;
		}

		$form.trigger('magic-login:code-login:before-submit');

		$.ajax({
			type      : 'POST',
			url       : $form.data('ajax-url'),
			data      : $form.serialize() + '&action=magic_login_code_login',
			beforeSend: function () {
				const spinnerImg = '<img src="' + $form.data('ajax-spinner') + '" alt="' + $form.data('ajax-sending-msg') + '" class="magic-login-spinner-image" />';
				const spinnerMessage = '<span class="magic-login-spinner-message">' + $form.data('ajax-sending-msg') + '</span>';
				const spinnerHtml = '<div class="magic-login-spinner-container">' + spinnerImg + spinnerMessage + '</div>';
				$('.magic-login-code-form-header').find('.spinner').html(spinnerHtml).show();
				$form.find('.magic-login-code-submit').attr('disabled', 'disabled');
				$form.trigger('magic-login:code-login:before-send');
				$('.magic-login-code-form-header').find('.ajax-result').html('').removeClass('success error').hide();
			},
			success   : function (response) {
				// Check success message
				if (response.success && response.data && response.data.message) {
					$('.magic-login-code-form-header').find('.ajax-result')
						.html(response.data.message)
						.addClass('success')
						.removeClass('error')
						.show();
				}

				// Check redirect
				if (response.data && response.data.redirect_to) {
					window.location.href = response.data.redirect_to;
					return;
				}

				// Handle error case
				if (!response.success && response.data && response.data.message) {
					$('.magic-login-code-form-header').find('.ajax-result')
						.html(response.data.message)
						.addClass('error')
						.removeClass('success')
						.show();
					maybeInitializeSpamProtection();
				}

				$form.trigger('magic-login:code-login:ajax-success', [response]);
			}
		})
			.fail(function (response) {
				$form.trigger('magic-login:code-login:ajax-fail', [response]);
			})
			.always(function () {
				$form.find('.magic-login-code-submit').attr('disabled', false);
				$form.trigger('magic-login:code-login:always');
				$form.find('.spinner').hide();
			});
	});


	$(document).on('submit', '#magic_login_registration_form', function (e) {
		e.preventDefault();

		const $form = $(this);
		const $formWrapper = $('#magic-login-register');
		var ajaxUrl = $form.data('ajax-url');
		var formData = $form.serialize();

		if (!checkCaptchaAndPreventSubmit($form)) {
			return false;
		}

		$form.trigger('magic-login:registration:before-submit');

		$.ajax({
			type      : 'POST',
			url       : ajaxUrl,
			data      : formData,
			beforeSend: function () {
				const spinnerImg = '<img src="' + $form.data('ajax-spinner') + '" alt="' + $form.data('ajax-sending-msg') + '" class="magic-login-spinner-image" />';
				const spinnerMessage = '<span class="magic-login-spinner-message">' + $form.data('ajax-sending-msg') + '</span>';
				const spinnerHtml = '<div class="magic-login-spinner-container">' + spinnerImg + spinnerMessage + '</div>';
				$formWrapper.find('.registration_result').html(spinnerHtml);
				$form.find('input[type="submit"]').prop('disabled', true);

				$form.trigger('magic-login:registration:before-send');
			},
			success   : function (response) {
				if (response.success) {
					if (response.data.redirect_url) {
						$formWrapper.find('.registration_result').html('<div class="success">' + response.data.success_message + '</div>');
						// Redirect to the specified URL
						window.location.href = response.data.redirect_url;
					} else {
						$formWrapper.find('.registration_result').html('<div class="success">' + response.data + '</div>');
						$form[0].reset();
						$form.hide();
					}

				} else {
					$formWrapper.find('.registration_result').html('<div class="error">' + response.data + '</div>');
					maybeInitializeSpamProtection();
				}

				$form.trigger('magic-login:registration:ajax-success', [response]);
			}
		}).fail(function (response) {
			$form.trigger('magic-login:registration:ajax-fail', [response]);
		}).always(function () {
			$form.find('input[type="submit"]').prop('disabled', false);
			$form.trigger('magic-login:registration:always');
			maybeInitializeSpamProtection();
		});
	});


	function checkCaptchaAndPreventSubmit($form) {
		if ($form.find('.magic-login-captcha-wrapper').length) {
			try {
				var recaptchaResponse = (typeof grecaptcha !== 'undefined' && typeof grecaptcha.getResponse === 'function') ? grecaptcha.getResponse() : null;
			} catch (e) {
				var recaptchaResponse = null;
			}

			var turnstileResponse = $form.find('[name="magic-login-cf-turnstile-response"]').val();
			var isRecaptchaV3 = $form.find('#recaptchaResponse').length;
			var isInvisibleRecaptcha = $form.find('.g-recaptcha').data('size') === 'invisible';

			// If invisible reCAPTCHA and no token is received, trigger it
			if (isInvisibleRecaptcha && !recaptchaResponse) {
				// Trigger reCAPTCHA challenge
				const widgetId = $form.find('.g-recaptcha').attr('data-recaptcha-widget-id');
				if (widgetId) {
					grecaptcha.execute(widgetId); // This will trigger the reCAPTCHA challenge
				} else {
					console.error('Invisible reCAPTCHA widget not found');
				}
				return false; // Prevent submission for now
			}

			if (!recaptchaResponse && !turnstileResponse && !isRecaptchaV3) {
				const spamProtectionMsg = $form.data('spam-protection-msg');
				if ($form.find('.magic-login-captcha-error').length === 0) {
					$form.find('.magic-login-captcha-wrapper').append('<div class="magic-login-captcha-error">' + spamProtectionMsg + '</div>');
					setTimeout(function () {
						$form.find('.magic-login-captcha-error').fadeOut().remove();
					}, 4000);
				}
				return false;
			}
		}
		return true;
	}


	// Init reCAPTCHA or Turnstile after loading the form via AJAX
	function maybeInitializeSpamProtection() {
		if (typeof grecaptcha !== 'undefined') {
			grecaptcha.ready(function () {

				// v3
				$('.g-recaptcha-v3').each(function () {
					var $recaptchaElement = $(this);
					var siteKey = $recaptchaElement.data('sitekey');


					grecaptcha.execute(siteKey, {action: 'submit'}).then(function (token) {
						$recaptchaElement.val(token);
					});

					return;
				});

				$('.g-recaptcha').each(function () {
					var $recaptchaElement = $(this);
					var widgetId = $recaptchaElement.attr('data-recaptcha-widget-id');

						try {
							// Attempt to render the reCAPTCHA widget
							if (!widgetId) {

								var recaptchaSize = $recaptchaElement.data('size');
								var recaptchaParams = {
									'sitekey': $recaptchaElement.data('sitekey'),
									'size'   : recaptchaSize,
									'badge'  : $recaptchaElement.data('badge'),
								}

								// if data callback is set, add it to the params
								if ($recaptchaElement.data('callback')) {
									recaptchaParams.callback = magicLoginInvisibleRecaptchaSubmit;
								}

								var newWidgetId = grecaptcha.render($recaptchaElement[0], recaptchaParams);
								// Store the widget ID in the element's data attributes
								$recaptchaElement.attr('data-recaptcha-widget-id', newWidgetId);

							} else {
								grecaptcha.reset(widgetId);
							}
						} catch (e) {
							if (e.message.includes('has already been rendered')) {
								// If the widget has already been rendered, reset it
								grecaptcha.reset(widgetId);
							} else {
								// Log other errors for debugging
								console.error('reCAPTCHA rendering failed:', e);
							}
						}

				});

			});

		}

		if (typeof turnstile !== 'undefined' && $('.magic-login-cf-turnstile').length) {
			var response = $('#magic-login-cf-turnstile-container').find('input[name="magic-login-cf-turnstile-response"]');

			if (response.length) {
				turnstile.reset('#magic-login-cf-turnstile-container');
			} else {
				$('.magic-login-cf-turnstile').each(function () {
					if (!$(this).attr('data-magic-login-turnstile-rendered')) {
						turnstile.render(this, {
							'sitekey': $(this).data('sitekey'),
							'response-field-name': 'magic-login-cf-turnstile-response'
						});
						$(this).attr('data-magic-login-turnstile-rendered', 'true');
					}
				});
			}
		}
	}

	maybeInitializeSpamProtection();

})(jQuery);


// write magicLoginInvisibleRecaptchaSubmit global function here
// This function is called by the reCAPTCHA callback function
// and submits the form if the reCAPTCHA is invisible
window.magicLoginInvisibleRecaptchaSubmit = function (token) {
	var wrapper = document.querySelector('.magic-login-captcha-wrapper');
	if (wrapper) {
		var form = wrapper.closest('form');
		var responseInput = document.createElement('input');
		responseInput.setAttribute('type', 'hidden');
		responseInput.setAttribute('name', 'g-recaptcha-response');
		responseInput.setAttribute('value', token);
		form.appendChild(responseInput);
		if(typeof jQuery !== 'undefined') {
			jQuery(form).trigger('submit');
		}
	}
};
