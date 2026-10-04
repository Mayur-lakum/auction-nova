// SSE client-side helper
(function(window) {
    'use strict';

    var STORAGE_KEY = 'auctionnova_sse_last_id';

    function getBaseUrl() {
        if (typeof SITE_URL !== 'undefined') return SITE_URL;
        if (window.SITE_URL) return window.SITE_URL;
        if (window.siteUrl) return window.siteUrl;
        return '/';
    }

    function readStoredLastId() {
        try {
            var v = localStorage.getItem(STORAGE_KEY);
            if (v && /^\d+$/.test(v)) return parseInt(v, 10);
        } catch (e) {}
        return 0;
    }

    function writeStoredLastId(id) {
        var n = parseInt(id, 10);
        if (!n || isNaN(n) || n <= 0) return;
        try { localStorage.setItem(STORAGE_KEY, String(n)); } catch (e) {}
    }

    function safeJsonParse(text) {
        if (!text || typeof text !== 'string') return null;
        try {
            return JSON.parse(text);
        } catch (e) {
            return null;
        }
    }

    window.AuctionSSE = {
        es: null,
        lastId: 0,
        listeners: {},
        bound: {},
        reconnectTimer: null,

        init: function() {
            var self = this;
            if (!window.EventSource) return;

            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }

            if (this.es) {
                try { this.es.close(); } catch (e) {}
                this.es = null;
            }
            this.bound = {};

            this.lastId = readStoredLastId() || this.lastId || 0;
            var sseUrl = getBaseUrl() + 'api/live-updates.php?last_id=' + encodeURIComponent(String(this.lastId || 0));
            this.es = new EventSource(sseUrl);

            this.es.onerror = function() {
                try { self.es && self.es.close(); } catch (e) {}
                self.es = null;

                if (self.reconnectTimer) return;
                self.reconnectTimer = setTimeout(function() {
                    self.reconnectTimer = null;
                    self.init();
                }, 2000);
            };

            Object.keys(this.listeners).forEach(function(evt) {
                self._ensureBound(evt);
            });
        },

        _ensureBound: function(eventName) {
            if (!eventName || !this.es || this.bound[eventName]) return;
            var self = this;

            this.es.addEventListener(eventName, function(e) {
                if (e && e.lastEventId) {
                    self.lastId = parseInt(e.lastEventId, 10) || self.lastId;
                    writeStoredLastId(self.lastId);
                }

                var payload = safeJsonParse(e && e.data ? e.data : null);
                var cbs = self.listeners[eventName];
                if (!cbs || !cbs.length) return;

                cbs.forEach(function(cb) {
                    try { cb(payload); } catch (err) { console.error(err); }
                });
            });

            this.bound[eventName] = true;
        },

        on: function(eventName, callback) {
            if (!eventName || typeof callback !== 'function') return;
            if (!this.listeners[eventName]) this.listeners[eventName] = [];
            this.listeners[eventName].push(callback);

            if (!this.es) {
                this.init();
            } else {
                this._ensureBound(eventName);
            }
        },

        close: function() {
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }
            if (this.es) {
                try { this.es.close(); } catch (e) {}
                this.es = null;
            }
            this.bound = {};
        }
    };
})(window);

document.addEventListener('DOMContentLoaded', function() {
    if (window.AuctionSSE && typeof window.AuctionSSE.init === 'function') {
        window.AuctionSSE.init();
    }
});
