/* global zapwaAdmin, wp */
(function ($) {
    'use strict';

    /* ---------------------------------------------------------------
       1. WhatsApp Bubble Preview
    --------------------------------------------------------------- */
    var ZapWAPreview = {

        $bubble: null,
        $wrap: null,

        init: function () {
            this.$wrap   = $('#zapwa-bubble-wrap');
            this.$bubble = $('#zapwa-bubble-text');

            if (!this.$wrap.length) return;

            // Detecta editor (Gutenberg ou classic/textarea)
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                this._listenGutenberg();
            } else {
                this._listenClassic();
            }

            // Leitura inicial
            this._update(this._getContent());
        },

        _listenGutenberg: function () {
            var self = this;
            try {
                wp.data.subscribe(function () {
                    var content = wp.data.select('core/editor').getEditedPostContent();
                    self._update(self._stripHtml(content));
                });
            } catch (e) {
                self._listenClassic();
            }
        },

        _listenClassic: function () {
            var self = this;
            // TinyMCE
            if (typeof tinyMCE !== 'undefined') {
                $(document).on('tinymce-editor-init', function (event, editor) {
                    editor.on('input keyup change', function () {
                        self._update(self._stripHtml(editor.getContent()));
                    });
                });
            }
            // Textarea fallback (#content)
            $(document).on('input keyup', '#content', function () {
                self._update($(this).val());
            });
        },

        _getContent: function () {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                try {
                    return this._stripHtml(
                        wp.data.select('core/editor').getEditedPostContent()
                    );
                } catch (e) {}
            }
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                return this._stripHtml(tinyMCE.activeEditor.getContent());
            }
            return $('#content').val() || '';
        },

        _stripHtml: function (html) {
            return $('<div>').html(html).text();
        },

        _update: function (text) {
            if (!this.$bubble) return;
            var trimmed = $.trim(text);
            if (trimmed === '') {
                this.$bubble.html(
                    '<span class="zapwa-bubble-empty">' +
                    zapwaAdmin.previewPlaceholder +
                    '</span>'
                );
            } else {
                var escaped = trimmed
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\n/g, '<br>');
                // Variáveis em destaque
                escaped = escaped.replace(
                    /\{([^}]+)\}/g,
                    '<span style="color:#075e54;font-weight:700;">{$1}</span>'
                );
                this.$bubble.html(escaped + '<div class="zapwa-bubble-time">' + this._now() + ' ✓✓</div>');
            }
        },

        _now: function () {
            var d = new Date();
            return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        }
    };

    /* ---------------------------------------------------------------
       2. Variable chips — clicar copia a variável para a área de transferência
    --------------------------------------------------------------- */
    var ZapWAVars = {

        init: function () {
            $(document).on('click', '.zapwa-var', function () {
                var varText = $(this).data('var');
                var $chip   = $(this);

                ZapWAVars._copyVar(varText, $chip);
            });
        },

        _copyVar: function (text, $chip) {
            var orig = $chip.text();

            var done = function () {
                $chip.addClass('copied');
                $chip.text('✔ Copiado');
                setTimeout(function () {
                    $chip.removeClass('copied').text(orig);
                }, 1200);
            };

            var fallback = function () {
                // Fallback para navegadores sem Clipboard API
                try {
                    var $tmp = $('<textarea>').val(text).appendTo('body').select();
                    document.execCommand('copy');
                    $tmp.remove();
                    done();
                } catch (e) {}
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, fallback);
            } else {
                fallback();
            }
        }
    };

    /* ---------------------------------------------------------------
       3. Mobile: adiciona data-colname nas células das tabelas do ZapWA
    --------------------------------------------------------------- */
    var ZapWAMobileTables = {

        init: function () {
            if ($(window).width() > 782) return;

            $('.zapwa-page table, .post-type-zapwa_message .wp-list-table').each(function () {
                var headers = [];
                $(this).find('thead th').each(function () {
                    headers.push($(this).text().trim());
                });
                if (!headers.length) return;
                $(this).find('tbody tr').each(function () {
                    $(this).find('td').each(function (i) {
                        if (headers[i]) {
                            $(this).attr('data-colname', headers[i]);
                        }
                    });
                });
            });
        }
    };

    /* ---------------------------------------------------------------
       4. Message Settings — type tabs & email toggle
    --------------------------------------------------------------- */
    var ZapWAMessageSettings = {

        init: function () {
            this._initTypeTabs();
            this._initEmailToggle();
        },

        _initTypeTabs: function () {
            $(document).on('click', '.zapwa-type-tab', function () {
                var type = $(this).data('type');
                $('.zapwa-type-tab').removeClass('active');
                $(this).addClass('active');
                $('#zapwa_type').val(type);
                $('#config-trigger, #config-broadcast').hide();
                $('#config-' + type).show();
                $('#desc-trigger, #desc-broadcast').hide();
                $('#desc-' + type).show();
            });
        },

        _initEmailToggle: function () {
            $(document).on('change', '#zapwa_email_enabled', function () {
                if ($(this).is(':checked')) {
                    $('#zapwa-email-fields').slideDown(200);
                } else {
                    $('#zapwa-email-fields').slideUp(200);
                }
            });
        }
    };

    /* ---------------------------------------------------------------
       Init on DOM ready
    --------------------------------------------------------------- */
    $(function () {
        ZapWAPreview.init();
        ZapWAVars.init();
        ZapWAMobileTables.init();
        ZapWAMessageSettings.init();
    });

}(jQuery));
