/* Smart Webinar – Admin JS */
/* global jQuery, swAdmin, wp */
(function ($) {
    'use strict';

    // ── Wizard navigation ────────────────────────────────────────────────────
    var totalSteps   = 6;
    var currentStep  = 1;

    function showStep(step) {
        currentStep = step;
        $('.sw-step').hide();
        $('.sw-step[data-step="' + step + '"]').show();
        $('.sw-step-btn').removeClass('active');
        $('.sw-step-btn[data-step="' + step + '"]').addClass('active');
        $('#sw-prev-step').toggle(step > 1);
        $('#sw-next-step').toggle(step < totalSteps);
        $('#sw-save-webinar').toggle(step === totalSteps);
    }

    $(document).on('click', '.sw-step-btn', function () {
        showStep(parseInt($(this).data('step')));
    });
    $('#sw-next-step').on('click', function () { if (currentStep < totalSteps) showStep(currentStep + 1); });
    $('#sw-prev-step').on('click', function () { if (currentStep > 1)         showStep(currentStep - 1); });

    // ── Color pickers ────────────────────────────────────────────────────────
    $('.sw-color-picker').wpColorPicker();

    // ── Chat message rows ────────────────────────────────────────────────────
    var tmpl = $('#sw-chat-row-template').html();
    $('#sw-add-chat-msg').on('click', function () {
        $('#sw-chat-messages').append(tmpl);
    });
    $(document).on('click', '.sw-remove-chat-row', function () {
        $(this).closest('.sw-chat-row').remove();
    });

    // ── Save Webinar ─────────────────────────────────────────────────────────
    $('#sw-save-webinar').on('click', function () {
        var $btn  = $(this);
        var $form = $('#sw-editor-form');

        $btn.prop('disabled', true).text(swAdmin.i18n.saving);

        var formData = $form.serializeArray();
        // Append checkboxes that may be unchecked
        ['offer_active', 'offer_new_tab'].forEach(function (name) {
            if (!$form.find('[name="' + name + '"]:checked').length) {
                formData.push({ name: name, value: '0' });
            }
        });
        formData.push({ name: 'action',     value: 'sw_save_webinar' });
        formData.push({ name: 'nonce',      value: swAdmin.nonce });
        formData.push({ name: 'webinar_id', value: $form.data('webinar-id') });

        // Collect chat rows
        $form.find('.sw-chat-row').each(function () {
            var $row = $(this);
            formData.push({ name: 'chat_type[]',    value: $row.find('[name="chat_type[]"]').val() });
            formData.push({ name: 'chat_author[]',  value: $row.find('[name="chat_author[]"]').val() });
            formData.push({ name: 'chat_show_at[]', value: $row.find('[name="chat_show_at[]"]').val() });
            formData.push({ name: 'chat_message[]', value: $row.find('[name="chat_message[]"]').val() });
        });

        $.post(swAdmin.ajaxUrl, $.param(formData))
            .done(function (res) {
                if (res.success) {
                    $btn.text(swAdmin.i18n.saved);
                    if (!$form.data('webinar-id') && res.data.webinar_id) {
                        $form.data('webinar-id', res.data.webinar_id);
                        // Update URL without reload
                        var url = new URL(window.location.href);
                        url.searchParams.set('webinar_id', res.data.webinar_id);
                        window.history.replaceState({}, '', url.toString());
                    }
                } else {
                    alert(swAdmin.i18n.error_save + ': ' + (res.data || ''));
                }
            })
            .fail(function () { alert(swAdmin.i18n.error_connection); })
            .always(function () { $btn.prop('disabled', false); });
    });

}(jQuery));
