/* Smart Webinar – Player Orchestrator */
/* global SWCountdown, SWTracker, SWChat */
(function () {
    'use strict';

    window.SmartWebinar = {
        config:      null,
        ytPlayer:    null,
        progressTimer: null,

        init: function (config) {
            this.config = config;

            SWTracker.init(config);
            SWChat.init(config, window['sw_scheduled_msgs_' + config.webinarId] || []);

            if (config.mode === 'live') {
                this._startLive();
            } else {
                this._startCountdown();
            }

            this._bindBeforeUnload();
            this._bindChatToggle();
            this._bindAppNavigation();
        },

        _startCountdown: function () {
            var self   = this;
            var delay  = configDelay(this.config);

            // Fire countdown_started event
            this._dispatchEvent('countdown_started');

            SWCountdown.start(delay, function () {
                self._onCountdownComplete();
            });

            // Update count display
            var countEl = document.querySelector('[data-webinar-id="' + this.config.webinarId + '"] .sw-countdown-count');
            if (countEl) countEl.textContent = delay;
        },

        _onCountdownComplete: function () {
            var wrapper   = document.getElementById('sw-countdown-' + this.config.webinarId);
            var playerWrp = document.getElementById('sw-player-wrapper-' + this.config.webinarId);

            if (wrapper)   wrapper.style.display   = 'none';
            if (playerWrp) playerWrp.style.display = 'block';

            this._dispatchEvent('live_started');
            this._startVideo();
        },

        _startLive: function () {
            this._dispatchEvent('live_started');
            this._startVideo();
        },

        _startVideo: function () {
            var self = this;

            // YouTube IFrame API
            if (typeof YT !== 'undefined' && YT.Player) {
                self._initYTPlayer();
            } else {
                var tag  = document.createElement('script');
                tag.src  = 'https://www.youtube.com/iframe_api';
                document.head.appendChild(tag);
                window.onYouTubeIframeAPIReady = function () { self._initYTPlayer(); };
            }

            SWChat.startPolling();
        },

        _initYTPlayer: function () {
            var self = this;
            var iframeEl = document.getElementById('sw-video-' + this.config.webinarId);
            if (!iframeEl) return;

            this.ytPlayer = new YT.Player(iframeEl, {
                events: {
                    onReady: function (e) {
                        if (self.config.mode !== 'live') {
                            // Simulated: autoplay from beginning
                            e.target.playVideo();
                        }
                    },
                    onStateChange: function (e) { self._onPlayerStateChange(e); },
                },
            });
        },

        _onPlayerStateChange: function (e) {
            var self = this;
            // YT.PlayerState: UNSTARTED=-1, ENDED=0, PLAYING=1, PAUSED=2, BUFFERING=3, CUED=5
            switch (e.data) {
                case 1: // PLAYING
                    SWTracker.onStart();
                    SWTracker.startHeartbeat();
                    self._startProgressLoop();
                    break;
                case 2: // PAUSED
                    SWTracker.onPause(self.ytPlayer.getCurrentTime());
                    self._stopProgressLoop();
                    break;
                case 0: // ENDED
                    SWTracker.onEnd(self.config.duration);
                    SWChat.stopPolling();
                    self._stopProgressLoop();
                    self._dispatchEvent('replay_available');

                    if (self.config.mode !== 'live') {
                        self._restartCountdownCycle();
                    }
                    break;
            }
        },

        _startProgressLoop: function () {
            var self = this;
            self._stopProgressLoop();
            self.progressTimer = setInterval(function () {
                if (!self.ytPlayer || typeof self.ytPlayer.getCurrentTime !== 'function') return;
                var current  = self.ytPlayer.getCurrentTime();
                var duration = self.config.duration || self.ytPlayer.getDuration() || 0;
                SWTracker.onProgress(current, duration);
                SWChat.tick(Math.round(current));
                self._checkOffer(current);
            }, 1000);
        },

        _stopProgressLoop: function () {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
        },

        _checkOffer: function (currentTime) {
            if (!this.config.offer) return;
            var offer  = this.config.offer;
            var offerEl = document.getElementById('sw-offer-' + this.config.webinarId);
            if (!offerEl) return;

            var visible = offerEl.style.display !== 'none';

            if (!visible && currentTime >= offer.showAt) {
                offerEl.style.display = 'block';
                SWTracker.onOfferShown();
                this._dispatchEvent('offer_shown');

                // Attach offer click listener (once)
                var self   = this;
                var offerBtn = offerEl.querySelector('.sw-offer-btn');
                if (offerBtn && !offerBtn.dataset.tracked) {
                    offerBtn.dataset.tracked = '1';
                    offerBtn.addEventListener('click', function () {
                        SWTracker.onOfferClicked();
                        self._dispatchEvent('offer_clicked');
                    });
                }
            }

            if (visible && offer.hideAt > 0 && currentTime >= offer.hideAt) {
                offerEl.style.display = 'none';
            }
        },



        _bindChatToggle: function () {
            var button = document.querySelector('[data-target="sw-chat-box-' + this.config.webinarId + '"]');
            if (!button) return;

            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-target');
                var box = document.getElementById(targetId);
                if (!box) return;

                var isHidden = box.style.display === 'none';
                box.style.display = isHidden ? '' : 'none';
                button.textContent = isHidden ? 'Ocultar chat' : 'Exibir chat';
            });
        },


        _bindAppNavigation: function () {
            var root = document.querySelector('[data-webinar-id="' + this.config.webinarId + '"]');
            if (!root) return;

            var navButtons = root.querySelectorAll('[data-sw-tab]');
            var panels = root.querySelectorAll('[data-sw-panel]');
            if (!navButtons.length || !panels.length) return;

            navButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-sw-tab');

                    navButtons.forEach(function (item) {
                        item.classList.toggle('is-active', item === btn);
                    });

                    panels.forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.getAttribute('data-sw-panel') === target);
                    });
                });
            });
        },

        _restartCountdownCycle: function () {
            var wrapper = document.getElementById('sw-countdown-' + this.config.webinarId);
            var playerWrp = document.getElementById('sw-player-wrapper-' + this.config.webinarId);

            if (playerWrp) playerWrp.style.display = 'none';
            if (wrapper) wrapper.style.display = 'flex';

            this._startCountdown();
        },

        _dispatchEvent: function (event) {
            // Fired for logging; actual server-side dispatch happens in PHP.
            document.dispatchEvent(new CustomEvent('sw:event', {
                detail: { event: event, webinarId: this.config.webinarId, sessionId: this.config.sessionId },
            }));
        },

        _bindBeforeUnload: function () {
            var self = this;
            window.addEventListener('beforeunload', function () {
                SWTracker.stopHeartbeat();
                SWChat.stopPolling();
                // Synchronous beacon to end session
                if (navigator.sendBeacon) {
                    var data = new FormData();
                    data.append('action',     'sw_end_session');
                    data.append('session_id', self.config.sessionId);
                    data.append('nonce',      self.config.nonce);
                    navigator.sendBeacon(self.config.ajaxUrl, data);
                }
            });
        },
    };

    // Helper: derive countdown delay
    function configDelay(config) {
        return typeof config.countdownDelay === 'number' ? config.countdownDelay : 0;
    }
}());
