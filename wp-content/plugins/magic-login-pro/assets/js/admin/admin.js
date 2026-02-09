import './modules/toggle';
// add tabs.js from wpmudev node package
import '@wpmudev/shared-ui/dist/js/_src/tabs';
import '@wpmudev/shared-ui/dist/js/_src/modal-dialog';

jQuery('#open-magic-login-sms-test-modal').on('click', function (e) {
	e.preventDefault();
	const modalId = 'magic-login-sms-test',
		focusAfterClosed = this,
		focusWhenOpen = undefined,
		hasOverlayMask = false,
		isCloseOnEsc = true,
		isAnimated = true
	;

	SUI.openModal(
		modalId,
		focusAfterClosed,
		focusWhenOpen,
		hasOverlayMask,
		isCloseOnEsc,
		isAnimated
	);
});

jQuery(document).ready(function($) {
	$('#sms-test-form').on('submit', function(e) {
		e.preventDefault();

		var phone = $('#magic-login-sms-test-phone').val();
		var message = $('#magic-login-sms-test-message').val();
		var nonce = $('#magic_login_send_test_sms_nonce').val();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'send_test_sms',
				phone: phone,
				message: message,
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$('#sms-response-message').html(
						'<div class="sui-notice-content" style="margin: 10px 0;">' + response.data + '</div>'
					).removeClass('sui-notice-red')
						.addClass('sui-notice sui-notice-green sui-active');
				} else {
					$('#sms-response-message').html(
						'<div class="sui-notice-content" style="margin: 10px 0;">Error: ' + response.data + '</div>'
					).removeClass('sui-notice-green')
						.addClass('sui-notice sui-notice-red sui-active');
				}
			},
			error: function(response) {
				$('#sms-response-message').html(
					'<div class="sui-notice-content" style="margin: 10px 0;">Error: ' + response.responseJSON.data + '</div>'
				).removeClass('sui-notice-green')
					.addClass('sui-notice sui-notice-red sui-active');
			}
		});
	});
});

jQuery(document).ready(function($) {
	// Assuming registration_mode is a select element
	$('[name="registration_mode"]').on('change',function() {
		if ($(this).val() === 'auto') {
			$('#auto-registration-details').show();
		} else {
			$('#auto-registration-details').hide();
		}

		if ($(this).val() === 'standard') {
			$('#standard-registration-details').show();
		} else {
			$('#standard-registration-details').hide();
		}

		if ($(this).val() === 'shortcode') {
			$('#shortcode-registration-details').show();
		} else {
			$('#shortcode-registration-details').hide();
		}

	});


	$('input[name="spam_protection_service"]').on('change', function () {
		$('.spam-protection-service-settings').hide();
		$('#' + $(this).val() + '-details').show();
	});


	$('#magic-login-import-file-input').on('change', function () {
		const elm = $(this)[0];
		if (elm.files.length) {
			const file = elm.files[0];
			$('#magic-login-import-file-name').text(file.name);
			$('#magic-login-import-upload-wrap').addClass('sui-has_file');
			$('#magic-login-import-btn').removeAttr('disabled');
		} else {
			$('#magic-login-import-file-name').text('');
			$('#magic-login-import-upload-wrap').removeClass('sui-has_file');
			$('#magic-login-import-btn').attr('disabled', 'disabled');
		}
	});

	$('#magic-login-import-remove-file').on('click', function () {
		$('#magic-login-import-file-input').val('').trigger('change');
	});

});

document.addEventListener('DOMContentLoaded', function () {
	// Temporarily remove the URL fragment to prevent scrolling
	const initialFragment = window.location.hash;
	if (initialFragment) {
		history.replaceState(null, null, window.location.pathname + window.location.search);
	}

	// Function to activate the tab based on the fragment
	function activateTabFromFragment(container) {
		const fragment = initialFragment;
		if (fragment) {
			const activeTabButton = container.querySelector(`.magic-login-main-tab-item[aria-controls="${fragment.substring(1)}"]`);
			if (activeTabButton) {
				activateTab(activeTabButton, false, container);
			}
		}
	}

	// Function to activate the tab
	function activateTab(tabButton, updateHistory = true, container) {
		const tabButtons = container.querySelectorAll('.magic-login-main-tab-item');
		const tabContents = container.nextElementSibling.querySelectorAll('.magic-login-main-tab-content');

		tabButtons.forEach(button => {
			button.classList.remove('active');
			button.setAttribute('aria-selected', 'false');
			button.setAttribute('tabindex', '-1');
		});

		tabContents.forEach(content => {
			content.classList.remove('active');
			content.setAttribute('tabindex', '-1');
		});

		tabButton.classList.add('active');
		tabButton.setAttribute('aria-selected', 'true');
		tabButton.setAttribute('tabindex', '0');

		const tabContentId = tabButton.getAttribute('aria-controls');
		const activeTabContent = container.nextElementSibling.querySelector(`#${tabContentId}`);
		activeTabContent.classList.add('active');
		activeTabContent.setAttribute('tabindex', '0');

		// Update the URL fragment without scrolling
		if (updateHistory) {
			history.replaceState(null, null, `#${tabContentId}`);
		}
	}

	// Initialize tabs for elements with the 'magic-login-main-tab-nav' class
	const tabContainers = document.querySelectorAll('.magic-login-main-tab-nav');
	tabContainers.forEach(container => {
		const tabButtons = container.querySelectorAll('.magic-login-main-tab-item');
		tabButtons.forEach(button => {
			button.addEventListener('click', function () {
				activateTab(button, true, container);
			});
		});

		// Activate the tab from the fragment on page load
		activateTabFromFragment(container);

		// Handle back/forward navigation
		window.addEventListener('hashchange', function() {
			activateTabFromFragment(container);
		});
	});

	// Reapply the initial fragment without scrolling
	if (initialFragment) {
		setTimeout(() => {
			history.replaceState(null, null, initialFragment);
		}, 0);
	}
});

