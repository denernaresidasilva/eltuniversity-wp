/* global SmartWebinar */
/* Smart Webinar – Countdown Module */
(function () {
    'use strict';

    window.SWCountdown = {
        timer: null,

        /**
         * @param {number} delaySeconds  Seconds until webinar starts
         * @param {Function} onComplete  Called when countdown reaches zero
         */
        start: function (delaySeconds, onComplete) {
            var self       = this;
            var remaining  = Math.max(0, delaySeconds);
            var hEl        = document.getElementById('sw-cd-h');
            var mEl        = document.getElementById('sw-cd-m');
            var sEl        = document.getElementById('sw-cd-s');

            if (!hEl || !mEl || !sEl) return;

            function pad(n) { return String(n).padStart(2, '0'); }

            function tick() {
                if (remaining <= 0) {
                    clearInterval(self.timer);
                    if (typeof onComplete === 'function') onComplete();
                    return;
                }
                var h = Math.floor(remaining / 3600);
                var m = Math.floor((remaining % 3600) / 60);
                var s = remaining % 60;
                hEl.textContent = pad(h);
                mEl.textContent = pad(m);
                sEl.textContent = pad(s);
                remaining--;
            }

            tick();
            self.timer = setInterval(tick, 1000);
        },

        stop: function () {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
    };
}());
