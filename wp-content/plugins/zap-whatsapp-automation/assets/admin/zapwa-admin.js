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
       2. Variable chips — clicar insere no editor ativo
    --------------------------------------------------------------- */
    var ZapWAVars = {

        init: function () {
            $(document).on('click', '.zapwa-var', function () {
                var varText = $(this).data('var');
                var $chip   = $(this);

                ZapWAVars._insertVar(varText);

                $chip.addClass('copied');
                var orig = $chip.text();
                $chip.text('✔ Inserido');
                setTimeout(function () {
                    $chip.removeClass('copied').text(orig);
                }, 1200);

                // Atualiza o preview imediatamente
                setTimeout(function () {
                    ZapWAPreview._update(ZapWAPreview._getContent());
                }, 50);
            });
        },

        _insertVar: function (text) {
            // Tenta Gutenberg
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                try {
                    wp.data.dispatch('core/editor').insertBlocks(
                        wp.blocks.createBlock('core/paragraph', { content: text })
                    );
                    return;
                } catch (e) {}
            }
            // Tenta TinyMCE
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                tinyMCE.activeEditor.insertContent(text);
                return;
            }
            // Textarea fallback
            var $ta = $('#content');
            if ($ta.length) {
                var el    = $ta[0];
                var start = el.selectionStart;
                var end   = el.selectionEnd;
                var val   = el.value;
                el.value  = val.substring(0, start) + text + val.substring(end);
                el.selectionStart = el.selectionEnd = start + text.length;
                $ta.trigger('input');
            } else {
                // Copia para área de transferência como fallback final
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
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
       Init on DOM ready
    --------------------------------------------------------------- */
    $(function () {
        ZapWAPreview.init();
        ZapWAVars.init();
        ZapWAMobileTables.init();
    });

}(jQuery));
