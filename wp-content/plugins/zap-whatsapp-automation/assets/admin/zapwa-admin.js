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
       5. Floating navigation (scroll-to-top visibility)
    --------------------------------------------------------------- */
    var ZapWANav = {

        init: function () {
            var $fabTop = $('#zapwa-fab-top');
            if (!$fabTop.length) return;

            $(window).on('scroll.zapwaNav', function () {
                if ($(window).scrollTop() > 200) {
                    $fabTop.addClass('visible');
                } else {
                    $fabTop.removeClass('visible');
                }
            });
        }
    };

    /* ---------------------------------------------------------------
       6. Email preview modal
    --------------------------------------------------------------- */
    var ZapWAEmailPreview = {

        $modal: null,
        $frame: null,
        $loading: null,

        init: function () {
            this.$modal   = $('#zapwa-email-preview-modal');
            this.$frame   = $('#zapwa-email-preview-frame');
            this.$loading = $('#zapwa-preview-loading');
            this.$trigger = null; // element that opened the modal

            if (!this.$modal.length) return;

            var self = this;

            // Open on preview button click
            $(document).on('click', '#zapwa-email-preview-btn', function () {
                self.$trigger = $(this);
                self.open();
            });

            // Close on × button
            $(document).on('click', '#zapwa-modal-close', function () {
                self.close();
            });

            // Close on overlay click (outside modal box)
            this.$modal.on('click', function (e) {
                if ($(e.target).is(self.$modal)) {
                    self.close();
                }
            });

            // Close on Escape key
            $(document).on('keydown.zapwaModal', function (e) {
                if (e.key === 'Escape') {
                    self.close();
                }
            });
        },

        open: function () {
            var self    = this;
            var subject = $('#zapwa_email_subject').val() || '';
            var body    = $('#zapwa_email_body').val()    || '';
            var isHtml  = $('#zapwa_email_is_html').is(':checked') ? '1' : '';

            // Show modal with loading state
            this.$frame.hide();
            this.$loading.show().text(
                (zapwaAdmin.i18n && zapwaAdmin.i18n.emailPreviewLoading) || 'Carregando preview...'
            );
            this.$modal.fadeIn(200);
            $('body').addClass('zapwa-modal-open');

            $.post(zapwaAdmin.ajaxUrl, {
                action:   'zapwa_email_preview',
                nonce:    zapwaAdmin.emailPreviewNonce,
                subject:  subject,
                body:     body,
                is_html:  isHtml
            })
            .done(function (html) {
                var doc = self.$frame[0].contentDocument || self.$frame[0].contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();
                self.$loading.hide();
                self.$frame.show();
            })
            .fail(function () {
                self.$loading.text(
                    (zapwaAdmin.i18n && zapwaAdmin.i18n.emailPreviewError) || 'Erro ao carregar o preview.'
                );
            });
        },

        close: function () {
            this.$modal.fadeOut(150);
            $('body').removeClass('zapwa-modal-open');
            // Restore focus to the element that triggered the modal
            if (this.$trigger && this.$trigger.length) {
                this.$trigger.trigger('focus');
            }
            // Reset iframe
            this.$frame.hide();
            this.$loading.show();
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
        ZapWANav.init();
        ZapWAEmailPreview.init();
    });

}(jQuery));

    /* ---------------------------------------------------------------
       7. WhatsApp Editor Toolbar — emoji picker, media, variables
    --------------------------------------------------------------- */
    var ZapWAEditorToolbar = {

        init: function () {

            if (!$('#zapwa-editor-wrap').length) return;

            var self = this;

            // ── Emoji picker ──────────────────────────────────────
            $(document).on('click', '#zapwa-emoji-btn', function (e) {
                e.stopPropagation();
                var $panel = $('#zapwa-emoji-panel');
                var $btn   = $(this);
                var offset = $btn.offset();
                $panel.css({
                    top:  (offset.top + $btn.outerHeight() + 4) + 'px',
                    left: offset.left + 'px'
                });
                $panel.toggle();
                $btn.attr('aria-expanded', $panel.is(':visible').toString());
            });

            $(document).on('click', '.zapwa-emoji-item', function () {
                var emoji = $(this).data('emoji');
                self._insertIntoEditor(emoji);
                $('#zapwa-emoji-panel').hide();
                $('#zapwa-emoji-btn').attr('aria-expanded', 'false');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#zapwa-emoji-btn, #zapwa-emoji-panel').length) {
                    $('#zapwa-emoji-panel').hide();
                    $('#zapwa-emoji-btn').attr('aria-expanded', 'false');
                }
            });

            // ── Media / Image URL ─────────────────────────────────
            $(document).on('click', '#zapwa-media-btn', function () {
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({ title: 'Selecionar Mídia', button: { text: 'Inserir URL' }, multiple: false });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        self._insertIntoEditor(att.url);
                    });
                    frame.open();
                } else {
                    var url = prompt('Cole a URL da mídia (imagem, vídeo, etc.):');
                    if (url) self._insertIntoEditor(url);
                }
            });

            // ── File upload ───────────────────────────────────────
            $(document).on('click', '#zapwa-file-btn', function () {
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({ title: 'Carregar Arquivo', button: { text: 'Usar Arquivo' }, multiple: false, library: { type: '' } });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        self._insertIntoEditor(att.url);
                    });
                    frame.open();
                } else {
                    var url = prompt('Cole a URL do arquivo:');
                    if (url) self._insertIntoEditor(url);
                }
            });

            // ── WhatsApp Preview (toolbar button) — toggle inline sidebar card ──
            $(document).on('click', '#zapwa-wa-preview-btn', function () {
                var $wrap = $('#zapwa-bubble-wrap');
                if ($wrap.is(':visible')) {
                    $wrap.slideUp(200);
                    return;
                }
                // Update preview content before showing
                var content = ZapWAPreview._getContent();
                var $text   = $('#zapwa-bubble-text');
                if (!$.trim(content)) {
                    $text.html('<span class="zapwa-bubble-empty">Escreva a mensagem para ver o preview...</span>');
                } else {
                    var escaped = $('<div>').text($.trim(content)).html()
                        .replace(/\n/g, '<br>');
                    escaped = escaped.replace(/\{([^}]+)\}/g, '<span style="color:#075e54;font-weight:700;">{$1}</span>');
                    var now = ZapWAPreview._now();
                    $text.html(escaped + '<div class="zapwa-bubble-time">' + now + ' ✓✓</div>');
                }
                $wrap.slideDown(200);
                // Scroll the preview card into view (guard against missing offset)
                var offset = $wrap.offset();
                if (offset) {
                    $('html, body').animate({ scrollTop: offset.top - 80 }, 300);
                }
            });

            // Close inline preview via ✕ button
            $(document).on('click', '#zapwa-preview-close-btn', function () {
                $('#zapwa-bubble-wrap').slideUp(200);
            });
        },

        _insertIntoEditor: function (text) {
            // Try Gutenberg — use select() to check store existence without side effects
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/block-editor')) {
                try {
                    var blocks = wp.data.select('core/block-editor').getSelectedBlock();
                    if (blocks && blocks.name === 'core/paragraph') {
                        var content = blocks.attributes.content || '';
                        wp.data.dispatch('core/block-editor').updateBlockAttributes(blocks.clientId, { content: content + text });
                        return;
                    }
                } catch (e) {}
            }
            // Try TinyMCE classic editor
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                tinyMCE.activeEditor.insertContent(text);
                return;
            }
            // Textarea fallback
            var field = document.getElementById('content');
            if (field) {
                var start = field.selectionStart;
                var end   = field.selectionEnd;
                field.value = field.value.slice(0, start) + text + field.value.slice(end);
                field.selectionStart = field.selectionEnd = start + text.length;
                field.focus();
                $(field).trigger('input');
            }
        }
    };

    // Register init
    $(function () {
        ZapWAEditorToolbar.init();
    });
