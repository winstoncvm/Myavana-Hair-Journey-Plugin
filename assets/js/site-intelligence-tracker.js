(function() {
    'use strict';

    var config = window.myavanaSiteIntelligence || {};
    if (!config.ajax_url || !config.nonce) {
        return;
    }

    var path = config.current_path || window.location.pathname || '/';
    if (path.indexOf('/wp-admin') === 0 || path.indexOf('/wp-json') === 0) {
        return;
    }

    var startedAt = Date.now();
    var sentOnExit = false;
    var flushTimer = null;
    var sessionKey = 'myavana_site_session_id';
    var queue = [];
    var queueLimit = 30;
    var seenSections = {};
    var scrollMarks = { 25: false, 50: false, 75: false, 100: false };
    var activeSections = new Map();
    var pageContext = normalizePageContext(config.current_page || path);

    function createSessionId() {
        return 'myv_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
    }

    function getSessionId() {
        try {
            var existing = window.localStorage.getItem(sessionKey);
            if (existing) {
                return existing;
            }

            var created = createSessionId();
            window.localStorage.setItem(sessionKey, created);
            return created;
        } catch (error) {
            return createSessionId();
        }
    }

    function normalizePageContext(value) {
        value = String(value || '').toLowerCase();
        if (value.indexOf('goals') !== -1) return 'goals';
        if (value.indexOf('routines') !== -1) return 'routines';
        if (value.indexOf('community') !== -1) return 'community';
        if (value.indexOf('profile') !== -1) return 'profile';
        if (value.indexOf('hair-journey') !== -1 || value === 'journey') return 'journey';
        if (value === '' || value === '/' || value === 'home') return 'home';
        return value.replace(/[^a-z0-9_-]/g, '') || 'home';
    }

    function getDeviceType() {
        var width = window.innerWidth || 0;
        if (width <= 768) return 'mobile';
        if (width <= 1024) return 'tablet';
        return 'desktop';
    }

    function getElapsedSeconds() {
        return Math.max(1, Math.round((Date.now() - startedAt) / 1000));
    }

    function getScrollDepth() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
        var docHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.offsetHeight
        );
        var winHeight = window.innerHeight || document.documentElement.clientHeight || 0;
        if (docHeight <= winHeight) {
            return 100;
        }
        return Math.min(100, Math.round(((scrollTop + winHeight) / docHeight) * 100));
    }

    function deriveActionLabel(element) {
        if (!element) return '';

        var explicit = element.getAttribute('data-track-action') ||
            element.getAttribute('aria-label') ||
            element.getAttribute('title');
        if (explicit) {
            return explicit.trim().slice(0, 80);
        }

        var text = (element.textContent || '').replace(/\s+/g, ' ').trim();
        if (text) {
            return text.slice(0, 80);
        }

        return (element.tagName || 'unknown').toLowerCase();
    }

    function deriveSectionKey(element) {
        if (!element) return '';

        var explicit = element.getAttribute('data-myavana-track-section');
        if (explicit) {
            return explicit.replace(/\s+/g, '-').toLowerCase().slice(0, 120);
        }

        if (element.id) {
            return element.id.replace(/\s+/g, '-').toLowerCase().slice(0, 120);
        }

        var heading = element.querySelector('h1, h2, h3, h4, [data-section-title]');
        if (heading && heading.textContent) {
            return heading.textContent.replace(/\s+/g, '-').replace(/[^a-zA-Z0-9_-]/g, '').toLowerCase().slice(0, 120);
        }

        var className = typeof element.className === 'string' ? element.className : '';
        if (className) {
            return className.split(/\s+/).filter(Boolean).slice(0, 2).join('-').toLowerCase().slice(0, 120);
        }

        return element.tagName ? element.tagName.toLowerCase() : 'section';
    }

    function enqueueEvent(eventType, eventName, payload) {
        payload = payload || {};
        queue.push({
            session_id: getSessionId(),
            user_id: config.user_id || 0,
            page_context: normalizePageContext(payload.page_context || pageContext),
            path: payload.path || path,
            section_key: payload.section_key || '',
            event_type: eventType,
            event_name: eventName,
            event_value: payload.event_value || '',
            dwell_seconds: payload.dwell_seconds || 0,
            event_count: payload.event_count || 1,
            meta: payload.meta || {},
            created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
        });

        if (queue.length >= queueLimit) {
            flush(false);
        }
    }

    function flush(isExitEvent) {
        if (!queue.length) {
            return;
        }

        var items = queue.splice(0, queue.length);
        var body = new URLSearchParams();
        body.append('action', 'myavana_track_site_event_batch');
        body.append('nonce', String(config.nonce));
        body.append('events', JSON.stringify(items));

        if (isExitEvent && navigator.sendBeacon) {
            var blob = new Blob([body.toString()], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
            navigator.sendBeacon(config.ajax_url, blob);
            return;
        }

        fetch(config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin',
            keepalive: !!isExitEvent
        }).catch(function() {
            // Best-effort analytics only.
        });
    }

    function buildPagePayload() {
        var payload = new URLSearchParams();
        payload.append('action', 'myavana_track_page_activity');
        payload.append('nonce', String(config.nonce));
        payload.append('session_id', getSessionId());
        payload.append('path', path);
        payload.append('page_title', document.title || '');
        payload.append('referrer', document.referrer || '');
        payload.append('device_type', getDeviceType());
        payload.append('viewport', (window.innerWidth || 0) + 'x' + (window.innerHeight || 0));
        payload.append('time_spent_seconds', String(getElapsedSeconds()));
        return payload;
    }

    function sendPageActivity(isExitEvent) {
        if (isExitEvent && sentOnExit) {
            return;
        }

        if (isExitEvent) {
            sentOnExit = true;
        }

        var payload = buildPagePayload();

        if (isExitEvent && navigator.sendBeacon) {
            var blob = new Blob([payload.toString()], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
            navigator.sendBeacon(config.ajax_url, blob);
            return;
        }

        fetch(config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: payload.toString(),
            credentials: 'same-origin',
            keepalive: !!isExitEvent
        }).catch(function() {
            // Best-effort analytics only.
        });
    }

    function flushActiveSections() {
        activeSections.forEach(function(started, key) {
            var dwellSeconds = Math.max(1, Math.round((Date.now() - started) / 1000));
            enqueueEvent('section_dwell', key, {
                section_key: key,
                dwell_seconds: dwellSeconds,
                meta: {
                    page_title: document.title || ''
                }
            });
        });
        activeSections.clear();
    }

    function trackInitialPageView() {
        enqueueEvent('page', 'page_view', {
            meta: {
                page_title: document.title || '',
                referrer: document.referrer || '',
                viewport: (window.innerWidth || 0) + 'x' + (window.innerHeight || 0),
                device_type: getDeviceType()
            }
        });
    }

    function handleInteractions() {
        document.addEventListener('click', function(event) {
            var target = event.target.closest('a, button, [role="button"], [data-track-action]');
            if (!target) {
                return;
            }

            enqueueEvent('click', deriveActionLabel(target).toLowerCase().replace(/[^a-z0-9_-]+/g, '_').slice(0, 120) || 'click', {
                section_key: deriveSectionKey(target.closest('[data-myavana-track-section], section, article, .quick-action-card, .myavana-ai-card')),
                event_value: target.getAttribute('href') || '',
                meta: {
                    action_label: deriveActionLabel(target),
                    tag_name: (target.tagName || '').toLowerCase(),
                    href: target.getAttribute('href') || ''
                }
            });
        }, true);

        document.addEventListener('submit', function(event) {
            var form = event.target;
            if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') {
                return;
            }

            enqueueEvent('form_submit', (form.getAttribute('id') || form.getAttribute('name') || 'form_submit').toLowerCase().replace(/[^a-z0-9_-]+/g, '_').slice(0, 120), {
                section_key: deriveSectionKey(form.closest('[data-myavana-track-section], section, article')),
                meta: {
                    form_id: form.getAttribute('id') || '',
                    form_name: form.getAttribute('name') || ''
                }
            });
        }, true);
    }

    function handleScrollDepth() {
        window.addEventListener('scroll', function() {
            var depth = getScrollDepth();
            [25, 50, 75, 100].forEach(function(mark) {
                if (!scrollMarks[mark] && depth >= mark) {
                    scrollMarks[mark] = true;
                    enqueueEvent('scroll', 'scroll_' + mark, {
                        event_value: String(mark),
                        meta: { depth: depth }
                    });
                }
            });
        }, { passive: true });
    }

    function observeSections() {
        if (typeof window.IntersectionObserver === 'undefined') {
            return;
        }

        var selectors = [
            'section',
            '[data-myavana-track-section]',
            '.myavana-luxury-member-highlight',
            '.myavana-luxury-member-card',
            '.quick-action-card',
            '.myavana-ai-feed',
            '.myavana-ai-card'
        ];

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                var key = deriveSectionKey(entry.target);
                if (!key) {
                    return;
                }

                if (entry.isIntersecting) {
                    if (!seenSections[key]) {
                        seenSections[key] = true;
                        enqueueEvent('section_view', key, {
                            section_key: key,
                            meta: {
                                page_title: document.title || ''
                            }
                        });
                    }

                    if (!activeSections.has(key)) {
                        activeSections.set(key, Date.now());
                    }
                } else if (activeSections.has(key)) {
                    var dwellSeconds = Math.max(1, Math.round((Date.now() - activeSections.get(key)) / 1000));
                    activeSections.delete(key);
                    enqueueEvent('section_dwell', key, {
                        section_key: key,
                        dwell_seconds: dwellSeconds
                    });
                }
            });
        }, {
            threshold: 0.45,
            rootMargin: '0px 0px -10% 0px'
        });

        selectors.forEach(function(selector) {
            Array.prototype.slice.call(document.querySelectorAll(selector)).slice(0, 40).forEach(function(node) {
                observer.observe(node);
            });
        });
    }

    function bindVisibility() {
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                enqueueEvent('page', 'page_exit', {
                    dwell_seconds: getElapsedSeconds(),
                    meta: {
                        page_title: document.title || ''
                    }
                });
                flushActiveSections();
                flush(true);
                sendPageActivity(true);
            }
        });

        window.addEventListener('pagehide', function() {
            enqueueEvent('page', 'page_exit', {
                dwell_seconds: getElapsedSeconds(),
                meta: {
                    page_title: document.title || ''
                }
            });
            flushActiveSections();
            flush(true);
            sendPageActivity(true);
        });
    }

    function startTimers() {
        flushTimer = window.setInterval(function() {
            if (document.visibilityState === 'visible') {
                flush(false);
            }
        }, 15000);

        window.setInterval(function() {
            if (document.visibilityState === 'visible') {
                sendPageActivity(false);
            }
        }, 60000);
    }

    window.MyavanaSiteIntelligence = {
        track: function(eventType, eventName, payload) {
            enqueueEvent(eventType, eventName, payload || {});
        },
        flush: flush,
        getPageContext: function() {
            return pageContext;
        },
        getSessionId: getSessionId
    };

    trackInitialPageView();
    handleInteractions();
    handleScrollDepth();
    observeSections();
    bindVisibility();
    startTimers();
})();
