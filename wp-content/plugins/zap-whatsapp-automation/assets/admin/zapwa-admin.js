/* global zapwaAdmin, wp */
(function ($) {
    'use strict';

    var ZapWAEditorUtils = {
        getContent: function () {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                try {
                    return this.stripHtml(wp.data.select('core/editor').getEditedPostContent());
                } catch (e) {}
            }
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                return this.stripHtml(tinyMCE.activeEditor.getContent());
            }
            return $('#content').val() || '';
        },

        stripHtml: function (html) {
            return $('<div>').html(html).text();
        },

        now: function () {
            var d = new Date();
            return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
        },

        renderPreviewHtml: function (text, emptyPlaceholder) {
            var trimmed = $.trim(text);
            if (!trimmed) {
                return '<span class="zapwa-bubble-empty">' + emptyPlaceholder + '</span>';
            }

            var escaped = $('<div>').text(trimmed).html().replace(/\n/g, '<br>');
            escaped = escaped.replace(/\{([^}]+)\}/g, '<span style="color:#075e54;font-weight:700;">{$1}</span>');

            return escaped + '<div class="zapwa-bubble-time">' + this.now() + ' ✓✓</div>';
        },

        insertIntoEditor: function (text) {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/block-editor')) {
                try {
                    var block = wp.data.select('core/block-editor').getSelectedBlock();
                    if (block && block.name === 'core/paragraph') {
                        var content = block.attributes.content || '';
                        wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, { content: content + text });
                        return;
                    }
                } catch (e) {}
            }

            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                tinyMCE.activeEditor.insertContent(text);
                return;
            }

            var field = document.getElementById('content');
            if (field) {
                var start = field.selectionStart;
                var end = field.selectionEnd;
                field.value = field.value.slice(0, start) + text + field.value.slice(end);
                field.selectionStart = field.selectionEnd = start + text.length;
                field.focus();
                $(field).trigger('input');
            }
        }
    };

    var ZapWAPreview = {
        $bubble: null,
        $wrap: null,

        init: function () {
            this.$wrap = $('#zapwa-bubble-wrap');
            this.$bubble = $('#zapwa-bubble-text');

            if (!this.$wrap.length || !this.$bubble.length) {
                return;
            }

            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                this._listenGutenberg();
            } else {
                this._listenClassic();
            }

            this.update(ZapWAEditorUtils.getContent());
        },

        _listenGutenberg: function () {
            var self = this;
            try {
                wp.data.subscribe(function () {
                    self.update(ZapWAEditorUtils.getContent());
                });
            } catch (e) {
                self._listenClassic();
            }
        },

        _listenClassic: function () {
            var self = this;

            if (typeof tinyMCE !== 'undefined') {
                $(document).on('tinymce-editor-init', function (event, editor) {
                    editor.on('input keyup change', function () {
                        self.update(ZapWAEditorUtils.stripHtml(editor.getContent()));
                    });
                });
            }

            $(document).on('input keyup', '#content', function () {
                self.update($(this).val());
            });
        },

        update: function (text) {
            if (!this.$bubble || !this.$bubble.length) {
                return;
            }

            this.$bubble.html(
                ZapWAEditorUtils.renderPreviewHtml(
                    text,
                    (zapwaAdmin && zapwaAdmin.previewPlaceholder) || 'Escreva a mensagem acima para ver o preview...'
                )
            );
        }
    };

    var ZapWAVars = {
        init: function () {
            $(document).on('click', '.zapwa-var', function () {
                var varText = $(this).data('var');
                var $chip = $(this);
                ZapWAVars._copyVar(varText, $chip);
            });
        },

        _copyVar: function (text, $chip) {
            var orig = $chip.text();

            var done = function () {
                $chip.addClass('copied').text('✔ Copiado');
                setTimeout(function () {
                    $chip.removeClass('copied').text(orig);
                }, 1200);
            };

            var fallback = function () {
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

    var ZapWAMobileTables = {
        init: function () {
            if ($(window).width() > 782) {
                return;
            }

            $('.zapwa-page table, .post-type-zapwa_message .wp-list-table').each(function () {
                var headers = [];
                $(this).find('thead th').each(function () {
                    headers.push($(this).text().trim());
                });

                if (!headers.length) {
                    return;
                }

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

    var ZapWAMessageSettings = {
        init: function () {
            this._initTypeTabs();
            this._initEmailToggle();
            this._initChannelNav();
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
                $('#zapwa-email-fields').stop(true, true)[$(this).is(':checked') ? 'slideDown' : 'slideUp'](200);
            });
        },

        _initChannelNav: function () {
            $(document).on('click', '[data-zapwa-scroll-target]', function () {
                var target = $(this).data('zapwa-scroll-target');
                var $target = $(target);

                if (!$target.length) {
                    return;
                }

                if (target === '#zapwa-email-section') {
                    $('#zapwa_email_enabled').prop('checked', true).trigger('change');
                }

                $('html, body').animate({ scrollTop: $target.offset().top - 90 }, 280);
            });
        }
    };

    var ZapWANav = {
        init: function () {
            var $fabTop = $('#zapwa-fab-top');
            if (!$fabTop.length) {
                return;
            }

            $(window).on('scroll.zapwaNav', function () {
                $fabTop.toggleClass('visible', $(window).scrollTop() > 200);
            });
        }
    };

    var ZapWAEmailPreview = {
        $modal: null,
        $frame: null,
        $loading: null,
        $trigger: null,

        init: function () {
            this.$modal = $('#zapwa-email-preview-modal');
            this.$frame = $('#zapwa-email-preview-frame');
            this.$loading = $('#zapwa-preview-loading');

            if (!this.$modal.length) {
                return;
            }

            var self = this;

            $(document).on('click', '#zapwa-email-preview-btn', function () {
                self.$trigger = $(this);
                self.open();
            });

            $(document).on('click', '#zapwa-modal-close', function () {
                self.close();
            });

            this.$modal.on('click', function (e) {
                if ($(e.target).is(self.$modal)) {
                    self.close();
                }
            });

            $(document).on('keydown.zapwaEmailModal', function (e) {
                if (e.key === 'Escape') {
                    self.close();
                }
            });
        },

        open: function () {
            var self = this;

            this.$frame.hide();
            this.$loading.show().text((zapwaAdmin.i18n && zapwaAdmin.i18n.emailPreviewLoading) || 'Carregando preview...');
            this.$modal.fadeIn(200);
            $('body').addClass('zapwa-modal-open');

            $.post(zapwaAdmin.ajaxUrl, {
                action: 'zapwa_email_preview',
                nonce: zapwaAdmin.emailPreviewNonce,
                subject: $('#zapwa_email_subject').val() || '',
                body: $('#zapwa_email_body').val() || '',
                is_html: $('#zapwa_email_is_html').is(':checked') ? '1' : ''
            }).done(function (html) {
                var frame = self.$frame[0];
                var doc = frame.contentDocument || frame.contentWindow.document;
                doc.open();
                doc.write(html);
                doc.close();
                self.$loading.hide();
                self.$frame.show();
            }).fail(function () {
                self.$loading.text((zapwaAdmin.i18n && zapwaAdmin.i18n.emailPreviewError) || 'Erro ao carregar o preview.');
            });
        },

        close: function () {
            this.$modal.fadeOut(150);
            $('body').removeClass('zapwa-modal-open');
            this.$frame.hide();
            this.$loading.show();

            if (this.$trigger && this.$trigger.length) {
                this.$trigger.trigger('focus');
            }
        }
    };

    var ZapWAEditorToolbar = {
        $waModal: null,

        init: function () {
            // Always cache the modal reference (rendered in admin_footer, not inside form).
            this.$waModal = $('#zapwa-wa-preview-modal');

            if (!$('#zapwa-editor-wrap').length) {
                return;
            }

            this._bindEmoji();
            this._bindMediaButtons();
            this._bindPreview();
        },

        _bindEmoji: function () {
            $(document).on('click', '#zapwa-emoji-btn', function (e) {
                e.stopPropagation();
                var $panel = $('#zapwa-emoji-panel');
                var $btn = $(this);
                var offset = $btn.offset();

                $panel.css({
                    top: (offset.top + $btn.outerHeight() + 4) + 'px',
                    left: offset.left + 'px'
                }).toggle();

                $btn.attr('aria-expanded', $panel.is(':visible').toString());
            });

            $(document).on('click', '.zapwa-emoji-item', function () {
                ZapWAEditorUtils.insertIntoEditor($(this).data('emoji'));
                $('#zapwa-emoji-panel').hide();
                $('#zapwa-emoji-btn').attr('aria-expanded', 'false');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#zapwa-emoji-btn, #zapwa-emoji-panel').length) {
                    $('#zapwa-emoji-panel').hide();
                    $('#zapwa-emoji-btn').attr('aria-expanded', 'false');
                }
            });
        },

        _bindMediaButtons: function () {
            $(document).on('click', '#zapwa-media-btn', function () {
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({ title: 'Selecionar Mídia', button: { text: 'Inserir URL' }, multiple: false });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        ZapWAEditorUtils.insertIntoEditor(att.url);
                    });
                    frame.open();
                } else {
                    var url = prompt('Cole a URL da mídia (imagem, vídeo, etc.):');
                    if (url) ZapWAEditorUtils.insertIntoEditor(url);
                }
            });

            $(document).on('click', '#zapwa-file-btn', function () {
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({ title: 'Carregar Arquivo', button: { text: 'Usar Arquivo' }, multiple: false, library: { type: '' } });
                    frame.on('select', function () {
                        var att = frame.state().get('selection').first().toJSON();
                        ZapWAEditorUtils.insertIntoEditor(att.url);
                    });
                    frame.open();
                } else {
                    var url = prompt('Cole a URL do arquivo:');
                    if (url) ZapWAEditorUtils.insertIntoEditor(url);
                }
            });
        },

        _bindPreview: function () {
            var self = this;

            $(document).on('click', '#zapwa-wa-preview-btn', function () {
                var html = ZapWAEditorUtils.renderPreviewHtml(
                    ZapWAEditorUtils.getContent(),
                    'Escreva a mensagem para ver o preview...'
                );

                if (self.$waModal.length) {
                    $('#zapwa-wa-modal-text').html(html);
                    self.$waModal.fadeIn(180);
                    $('body').addClass('zapwa-modal-open');
                    return;
                }

                var $wrap = $('#zapwa-bubble-wrap');
                if (!$wrap.length) {
                    return;
                }

                $('#zapwa-bubble-text').html(html);
                $wrap.stop(true, true).slideDown(180);

                var offset = $wrap.offset();
                if (offset) {
                    $('html, body').animate({ scrollTop: offset.top - 80 }, 280);
                }
            });

            $(document).on('click', '#zapwa-wa-modal-close', function () {
                self._closeWaModal();
            });

            $(document).on('click', '#zapwa-wa-preview-modal', function (e) {
                if ($(e.target).is('#zapwa-wa-preview-modal')) {
                    self._closeWaModal();
                }
            });

            $(document).on('keydown.zapwaWaModal', function (e) {
                if (e.key === 'Escape') {
                    self._closeWaModal();
                }
            });

            $(document).on('click', '#zapwa-preview-close-btn', function () {
                $('#zapwa-bubble-wrap').slideUp(180);
            });
        },

        _closeWaModal: function () {
            if (this.$waModal && this.$waModal.length) {
                this.$waModal.fadeOut(150);
                $('body').removeClass('zapwa-modal-open');
            }
        }
    };

    $(function () {
        ZapWAPreview.init();
        ZapWAVars.init();
        ZapWAMobileTables.init();
        ZapWAMessageSettings.init();
        ZapWANav.init();
        ZapWAEmailPreview.init();
        ZapWAEditorToolbar.init();
    });

}(jQuery));
