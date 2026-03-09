/* Smart Webinar – Chat Module */
(function () {
    'use strict';

    window.SWChat = {
        config:        null,
        pollTimer:     null,
        lastMessageId: 0,
        currentTime:   0,
        scheduledMsgs: [],

        init: function (config, scheduledMessages) {
            this.config        = config;
            this.scheduledMsgs = Array.isArray(scheduledMessages) ? scheduledMessages : [];
            this.attachEvents();
        },

        attachEvents: function () {
            var self = this;
            var webinarId = this.config.webinarId;

            // Send on button click
            var sendBtn = document.querySelector('.sw-chat-send[data-webinar-id="' + webinarId + '"]');
            if (sendBtn) {
                sendBtn.addEventListener('click', function () { self.sendMessage(); });
            }

            // Send on Enter key
            var input = document.getElementById('sw-chat-input-' + webinarId);
            if (input) {
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });
            }
        },

        /** Start polling for new messages */
        startPolling: function () {
            var self = this;
            self.poll();
            this.pollTimer = setInterval(function () { self.poll(); }, 5000);
        },

        stopPolling: function () {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        poll: function () {
            if (!this.config) return;
            var self = this;
            var url  = this.config.restUrl + 'chat/messages'
                     + '?webinar_id=' + this.config.webinarId
                     + '&session_id=' + encodeURIComponent(this.config.sessionId)
                     + '&after='      + this.lastMessageId
                     + '&nonce='      + encodeURIComponent(this.config.nonce);

            fetch(url, { headers: { 'X-WP-Nonce': this.config.nonce } })
                .then(function (r) { return r.json(); })
                .then(function (messages) {
                    if (!Array.isArray(messages)) return;
                    messages.forEach(function (msg) {
                        self.appendMessage(msg);
                        if (parseInt(msg.id) > self.lastMessageId) {
                            self.lastMessageId = parseInt(msg.id);
                        }
                    });
                })
                .catch(function () { /* silent */ });
        },

        /** Tick – called every second by the player with current video time */
        tick: function (currentTime) {
            this.currentTime = currentTime;
            var self = this;
            this.scheduledMsgs = this.scheduledMsgs.filter(function (msg) {
                if (parseInt(msg.show_at) <= currentTime) {
                    self.appendMessage(msg, true);
                    return false; // remove from queue
                }
                return true;
            });
        },

        appendMessage: function (msg, isScheduled) {
            var container = document.getElementById('sw-chat-messages-' + this.config.webinarId);
            if (!container) return;

            var el       = document.createElement('div');
            el.className = 'sw-chat-msg' + (msg.message_type === 'user' ? ' sw-chat-msg--user' : '');

            var avatarHtml;
            if (msg.author_avatar) {
                avatarHtml = '<div class="sw-chat-avatar"><img src="' + this._esc(msg.author_avatar) + '" alt="' + this._esc(msg.author_name) + '"></div>';
            } else {
                var initial = (msg.author_name || '?').charAt(0).toUpperCase();
                avatarHtml  = '<div class="sw-chat-avatar">' + this._esc(initial) + '</div>';
            }

            el.innerHTML = avatarHtml
                + '<div class="sw-chat-body">'
                + '<div class="sw-chat-author">' + this._esc(msg.author_name || 'User') + '</div>'
                + '<div class="sw-chat-text">'   + this._esc(msg.message)               + '</div>'
                + '</div>';

            container.appendChild(el);
            container.scrollTop = container.scrollHeight;
        },

        sendMessage: function () {
            var input = document.getElementById('sw-chat-input-' + this.config.webinarId);
            if (!input) return;
            var message = input.value.trim();
            if (!message) return;
            input.value = '';

            var self = this;
            var body = new FormData();
            body.append('webinar_id', this.config.webinarId);
            body.append('session_id', this.config.sessionId);
            body.append('message',    message);
            body.append('nonce',      this.config.nonce);

            fetch(this.config.restUrl + 'chat', {
                method:  'POST',
                body:    body,
                headers: { 'X-WP-Nonce': this.config.nonce },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    var currentUser = typeof wpApiSettings !== 'undefined'
                        ? (wpApiSettings.name || 'Você')
                        : 'Você';
                    self.appendMessage({
                        id:           data.id,
                        author_name:  currentUser,
                        author_avatar:'',
                        message:      message,
                        message_type: 'user',
                    });
                }
            })
            .catch(function () { /* silent */ });
        },

        _esc: function (str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str || ''));
            return d.innerHTML;
        },
    };
}());
