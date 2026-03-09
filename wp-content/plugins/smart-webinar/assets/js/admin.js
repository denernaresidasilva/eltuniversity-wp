/* Smart Webinar – Admin JS */
/* global jQuery, swAdmin, wp */
(function ($) {
    'use strict';

    // ── Constants ────────────────────────────────────────────────────────────
    var TOTAL_STEPS = 7;
    var currentStep = 1;
    var currentMode = $('input[name="mode"]:checked').val() || 'evergreen';

    // ── Wizard navigation ────────────────────────────────────────────────────
    function showStep(step) {
        currentStep = step;
        $('.sw-step').hide();
        $('.sw-step[data-step="' + step + '"]').show();
        $('.sw-step-btn').removeClass('active');
        $('.sw-step-btn[data-step="' + step + '"]').addClass('active');
        $('#sw-prev-step').toggle(step > 1);
        $('#sw-next-step').toggle(step < TOTAL_STEPS);
        $('#sw-save-webinar').toggle(step === TOTAL_STEPS);
        // Update progress bar
        var pct = (step / TOTAL_STEPS) * 100;
        $('.sw-progress-bar').css('width', pct + '%');
        // Update countdown fields visibility when entering step 3
        if (step === 3) {
            updateCountdownFields(currentMode);
        }
    }

    $(document).on('click', '.sw-step-btn', function () {
        showStep(parseInt($(this).data('step'), 10));
    });
    $(document).ready(function () {
        $('#sw-next-step').on('click', function () {
            if (currentStep < TOTAL_STEPS) showStep(currentStep + 1);
        });
        $('#sw-prev-step').on('click', function () {
            if (currentStep > 1) showStep(currentStep - 1);
        });
    });

    // ── Mode selection ───────────────────────────────────────────────────────
    function updateModeFields(mode) {
        currentMode = mode;
        // Toggle mode card selected state
        $('.sw-mode-card').removeClass('selected');
        $('.sw-mode-card input[value="' + mode + '"]').closest('.sw-mode-card').addClass('selected');
        // Show/hide mode-specific fields
        $('.sw-mode-fields').hide();
        $('.sw-mode-fields[data-mode="' + mode + '"]').show();
        // Sync video URL value across sections when switching
        var currentUrl = $('.sw-video-url-input:first').val();
        $('.sw-video-url-input').val(currentUrl);
    }

    function updateCountdownFields(mode) {
        $('.sw-countdown-fields').hide();
        if (mode === 'live' || mode === 'ondemand') {
            $('.sw-countdown-live, .sw-countdown-ondemand').show();
        } else if (mode === 'evergreen') {
            $('.sw-countdown-evergreen').show();
        }
    }

    $(document).on('change', 'input[name="mode"]', function () {
        updateModeFields($(this).val());
    });

    // Sync video URL between mode sections when user types
    $(document).on('input', '.sw-video-url-input', function () {
        var val = $(this).val();
        $('.sw-video-url-input').not(this).val(val);
    });

    // Initialize mode fields on load
    $(document).ready(function () {
        var initialMode = $('input[name="mode"]:checked').val() || 'evergreen';
        updateModeFields(initialMode);
        updateCountdownFields(initialMode);
    });

    // ── Color pickers ────────────────────────────────────────────────────────
    $(document).ready(function () {
        $('.sw-color-picker').wpColorPicker({
            change: function (event, ui) {
                // Update room preview background when room color picker changes
                if ($(this).attr('name') === 'room_bg_color') {
                    $('#sw-room-preview').css('background-color', ui.color.toString());
                }
            }
        });
    });

    // ── Chat timer (MM:SS) ───────────────────────────────────────────────────
    function parseTimeInput(val) {
        var parts = val.split(':');
        var mins = 0, secs = 0;
        if (parts.length === 2) {
            mins = parseInt(parts[0], 10) || 0;
            secs = parseInt(parts[1], 10) || 0;
        } else {
            secs = parseInt(val, 10) || 0;
        }
        return (mins * 60) + secs;
    }

    function formatSeconds(totalSec) {
        var m = Math.floor(totalSec / 60);
        var s = totalSec % 60;
        return ('0' + m).slice(-2) + ':' + ('0' + s).slice(-2);
    }

    $(document).on('input change blur', '.sw-timer-display', function () {
        var $row = $(this).closest('.sw-chat-row');
        var val  = $(this).val().trim();
        var secs = parseTimeInput(val);
        $row.find('.sw-timer-seconds').val(secs);
        $row.find('.sw-timer-readable').text(formatSeconds(secs));
        // Format as MM:SS on blur
        if ($(this).is(':not(:focus)')) {
            $(this).val(formatSeconds(secs));
        }
    });

    // ── Chat message rows ────────────────────────────────────────────────────
    var tmpl = '';
    $(document).ready(function () {
        tmpl = $('#sw-chat-row-template').html();
    });

    $(document).on('click', '#sw-add-chat-msg', function () {
        $('#sw-chat-messages').append(tmpl);
    });
    $(document).on('click', '.sw-remove-chat-row', function () {
        $(this).closest('.sw-chat-row').remove();
    });

    // ── Delete Webinar (list page) ───────────────────────────────────────────
    $(document).on('click', '.sw-delete-webinar', function () {
        var $btn = $(this);
        var id   = $btn.data('id');
        if (!window.confirm(swAdmin.i18n.confirm_delete)) return;

        $btn.prop('disabled', true).text('…');

        $.post(swAdmin.ajaxUrl, {
            action:     'sw_delete_webinar',
            webinar_id: id,
            nonce:      swAdmin.nonce
        }).done(function (res) {
            if (res.success) {
                $btn.closest('tr').fadeOut(400, function () { $(this).remove(); });
            } else {
                alert(res.data || 'Erro ao excluir.');
                $btn.prop('disabled', false).text(swAdmin.i18n.confirm_delete ? 'Excluir' : 'Excluir');
            }
        }).fail(function () {
            alert(swAdmin.i18n.error_connection);
            $btn.prop('disabled', false).text('Excluir');
        });
    });

    // ── Media uploader (room appearance) ─────────────────────────────────────
    $(document).on('click', '.sw-media-upload', function () {
        var $btn     = $(this);
        var targetId = $btn.data('target');
        var previewId= $btn.data('preview');
        var fileType = $btn.data('type'); // 'image' or 'video'

        var frame = wp.media({
            title:    fileType === 'image' ? swAdmin.i18n.select_image : swAdmin.i18n.select_video,
            button:   { text: swAdmin.i18n.use_file },
            multiple: false,
            library:  { type: fileType === 'image' ? 'image' : 'video' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.url;
            $('#' + targetId).val(url);

            var $preview = $('#' + previewId);
            if (fileType === 'image') {
                var $img = $('<img>').attr('src', url).attr('alt', '');
                $preview.empty().append($img);
                updateRoomPreview();
            } else {
                var $vid = $('<video>').attr('src', url).attr('muted', '').attr('loop', '').attr('autoplay', '').attr('playsinline', '');
                $preview.empty().append($vid);
                updateRoomPreview();
            }
            $btn.next('.sw-media-remove').show();
        });

        frame.open();
    });

    $(document).on('click', '.sw-media-remove', function () {
        var $btn     = $(this);
        var targetId = $btn.data('target');
        var previewId= $btn.data('preview');
        var fileType = targetId.indexOf('video') !== -1 ? 'video' : 'image';

        $('#' + targetId).val('');
        var placeholder = fileType === 'image'
            ? '🖼 Nenhuma imagem selecionada'
            : '🎬 Nenhum vídeo selecionado';
        $('#' + previewId).html('<span class="sw-media-placeholder">' + placeholder + '</span>');
        $btn.hide();
        updateRoomPreview();
    });

    // ── Room preview ──────────────────────────────────────────────────────────
    function updateRoomPreview() {
        var bgColor = $('input[name="room_bg_color"]').val() || '#1a1a2e';
        var bgImage = $('#sw-bg-image-url').val();
        var bgVideo = $('#sw-bg-video-url').val();
        var $preview = $('#sw-room-preview');

        $preview.css('background-color', bgColor);

        if (bgVideo) {
            $preview.find('video.sw-room-bg-video').remove();
            var $bgVid = $('<video>').addClass('sw-room-bg-video').attr({
                src: bgVideo, muted: '', loop: '', autoplay: '', playsinline: ''
            });
            $preview.prepend($bgVid);
            $preview.css('background-image', 'none');
        } else if (bgImage) {
            $preview.find('video.sw-room-bg-video').remove();
            $preview.css('background-image', 'url(' + bgImage + ')');
        } else {
            $preview.find('video.sw-room-bg-video').remove();
            $preview.css('background-image', 'none');
        }
    }

    // Sync countdown text to preview
    $(document).on('input', 'input[name="countdown_text"]', function () {
        $('.sw-room-countdown-text').text($(this).val());
    });

    // Countdown timer in preview
    var previewTimer = null;
    function startPreviewCountdown() {
        var total = 3600; // default 1hr for preview
        clearInterval(previewTimer);
        previewTimer = setInterval(function () {
            if ($('.sw-step[data-step="7"]').is(':visible')) {
                total--;
                var h = Math.floor(total / 3600);
                var m = Math.floor((total % 3600) / 60);
                var s = total % 60;
                var timeStr = ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2) + ':' + ('0' + s).slice(-2);
                $('.sw-room-countdown-timer').text(timeStr);
                if (total <= 0) total = 3600;
            }
        }, 1000);
    }

    $(document).on('click', '.sw-step-btn[data-step="7"]', function () {
        updateRoomPreview();
        startPreviewCountdown();
    });

    // ── Save Webinar ─────────────────────────────────────────────────────────
    $(document).ready(function () {
        $('#sw-save-webinar').on('click', function () {
            var $btn  = $(this);
            var $form = $('#sw-editor-form');

            $btn.prop('disabled', true).html('⏳ ' + swAdmin.i18n.saving);

            // Disable video_url inputs in non-active mode sections to avoid duplicates
            $form.find('.sw-video-url-wrap').each(function () {
                var $section = $(this);
                var sectionMode = $section.data('mode');
                var isActive = sectionMode === currentMode;
                $section.find('.sw-video-url-input').prop('disabled', !isActive);
            });

            var formData = $form.serializeArray();

            // Re-enable after serialization
            $form.find('.sw-video-url-input').prop('disabled', false);

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
                formData.push({ name: 'chat_show_at[]', value: $row.find('.sw-timer-seconds').val() });
                formData.push({ name: 'chat_message[]', value: $row.find('[name="chat_message[]"]').val() });
            });

            $.post(swAdmin.ajaxUrl, $.param(formData))
                .done(function (res) {
                    if (res.success) {
                        $btn.html('✅ ' + swAdmin.i18n.saved);
                        if (!$form.data('webinar-id') && res.data.webinar_id) {
                            $form.data('webinar-id', res.data.webinar_id);
                            var url = new URL(window.location.href);
                            url.searchParams.set('webinar_id', res.data.webinar_id);
                            window.history.replaceState({}, '', url.toString());
                        }
                        setTimeout(function () {
                            $btn.html('💾 Salvar Webinar').prop('disabled', false);
                        }, 2000);
                    } else {
                        alert(swAdmin.i18n.error_save + ': ' + (res.data || ''));
                        $btn.html('💾 Salvar Webinar').prop('disabled', false);
                    }
                })
                .fail(function () {
                    alert(swAdmin.i18n.error_connection);
                    $btn.html('💾 Salvar Webinar').prop('disabled', false);
                });
        });
    });

}(jQuery));
