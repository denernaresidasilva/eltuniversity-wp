/* global jQuery, MagicLogin  */
/* eslint-disable */

(function ($) {
    // Use strict mode
    $(document).ready(function () {
        $('#disable_magic_login').on('change',function(){
           if( $('#disable_magic_login').is(':checked')){
                $('.generate-magic-login').hide();
           }else{
               $('.generate-magic-login').show();
           }
        });

        $('#create_new_magic_login').on('click', function () {
            $.post(magic_login_user_profile_object.ajax_url, {
                action: 'magic_login_create_login_link_for_user',
                nonce: magic_login_user_profile_object.nonce,
                user_id: $(this).val(),
            }, function (response) {

                if (undefined === response.success) {
                    return;
                }

                if (response.success) {
					const loginLink = response.data.link;
					const qrUrl = response.data.qr_url;

					$('#magic_login_user_link').val(loginLink);
					$('#magic_login_user_link_wrapper').show();

					if (qrUrl) {
						$('#magic_login_user_qr_img').attr('src', qrUrl);
						$('#magic_login_user_qr_wrapper').show();
					}
					$('#magic-login-user-profile-ajax-msg').hide();

				} else {
                    let $message = $('</p>');
                    $message.append(response.data);
                    let $notice_wrapper = $('<div class="notice inline notice-error is-dismissible"></div>');

                    $('#magic-login-user-profile-ajax-msg').html($notice_wrapper.append($message));
                    $('#magic_login_user_link_wrapper').hide();
                    $('#magic-login-user-profile-ajax-msg').show();
                }
            });
        });

        const autoCopyLoginLink = document.querySelector('#magic_login_user_link');

        autoCopyLoginLink.addEventListener('click', function (event) {
            const copyInput = document.querySelector('#magic_login_user_link');
            copyInput.focus();
            copyInput.select();
            document.execCommand('copy');
        });

    });


	// Use strict mode
	$(document).ready(function () {
		$('#reset_magic_login_user_token').on('click', function () {
			const userId = $(this).val();

			$.post(magic_login_user_profile_object.ajax_url, {
				action: 'reset_user_magic_login_tokens',
				nonce: magic_login_user_profile_object.nonce,
				user_id: userId,
			}, function (response) {
				if (response.success) {
					$('#magic_login_reset_msg')
						.text(response.data)
						.css('color', 'green')
						.show();
				} else {
					$('#magic_login_reset_msg')
						.text(response.data)
						.css('color', 'red')
						.show();
				}

				setTimeout(function () {
					$('#magic_login_reset_msg').fadeOut();
				}, 5000);
			});
		});

		// Handle sending login link to user
		$('#send_magic_login_link_to_user').on('click', function () {
			const loginLink = $('#magic_login_user_link').val();
			const userId = $('#create_new_magic_login').val();

			if (!loginLink) {
				$('#magic_login_send_msg')
					.text(magic_login_user_profile_object.i18n_no_link)
					.css('color', 'red')
					.show();
				return;
			}

			// Display sending message
			$('#magic_login_send_msg')
				.text(magic_login_user_profile_object.i18n_sending)
				.css('color', 'black')
				.show();

			$.post(magic_login_user_profile_object.ajax_url, {
				action: 'magic_login_send_link_to_user',
				nonce: magic_login_user_profile_object.nonce,
				user_id: userId,
				login_link: loginLink
			}, function (response) {
				if (response.success) {
					$('#magic_login_send_msg')
						.text(response.data)
						.css('color', 'green')
						.show();
				} else {
					$('#magic_login_send_msg')
						.text(response.data)
						.css('color', 'red')
						.show();
				}

				setTimeout(function () {
					$('#magic_login_send_msg').fadeOut();
				}, 5000);
			});
		});
	});

})(jQuery);

/* eslint-enable */
