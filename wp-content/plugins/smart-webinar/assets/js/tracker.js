/* Smart Webinar – Video Tracker Module */
(function () {
    'use strict';

    window.SWTracker = {
        config: null,
        firedMilestones: {},
        heartbeatTimer: null,
        currentTime: 0,

        init: function (config) {
            this.config = config;
            this.firedMilestones = {};
        },

        /**
         * Called continuously during video playback.
         * @param {number} currentTime  Current playback position in seconds
         * @param {number} duration     Total video duration in seconds
         */
        onProgress: function (currentTime, duration) {
            if (!this.config || !duration) return;
            this.currentTime = currentTime;

            var pct = Math.floor((currentTime / duration) * 100);
            var milestones = [25, 50, 75, 90, 100];

            milestones.forEach(function (m) {
                if (pct >= m && !this.firedMilestones[m]) {
                    this.firedMilestones[m] = true;
                    this.send('video_' + m, currentTime, m);
                }
            }, this);
        },

        onStart: function () {
            this.send('video_start', 0, 0);
        },

        onPause: function (currentTime) {
            this.send('video_pause', currentTime, 0);
        },

        onResume: function (currentTime) {
            this.send('video_resume', currentTime, 0);
        },

        onEnd: function (duration) {
            this.send('video_100', duration, 100);
        },

        onOfferShown: function () {
            this.send('offer_show', this.currentTime, 0);
        },

        onOfferClicked: function () {
            this.send('offer_click', this.currentTime, 0);
        },

        send: function (eventType, watchTime, percentage) {
            if (!this.config) return;
            var body = new FormData();
            body.append('webinar_id', this.config.webinarId);
            body.append('session_id', this.config.sessionId);
            body.append('event_type', eventType);
            body.append('watch_time', Math.round(watchTime));
            body.append('percentage', percentage);
            body.append('device',     this.config.device || 'desktop');
            body.append('nonce',      this.config.nonce);

            fetch(this.config.restUrl + 'track', {
                method:  'POST',
                body:    body,
                headers: { 'X-WP-Nonce': this.config.nonce },
            }).catch(function () { /* silent fail */ });
        },

        startHeartbeat: function () {
            var self = this;
            this.heartbeatTimer = setInterval(function () {
                // Heartbeat to keep session alive (every 30s)
                if (self.currentTime > 0) {
                    self.send('heartbeat', self.currentTime, 0);
                }
            }, 30000);
        },

        stopHeartbeat: function () {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
        },
    };
}());
