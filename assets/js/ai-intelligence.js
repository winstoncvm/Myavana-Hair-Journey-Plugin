(function() {
    'use strict';

    var config = window.myavanaAIIntelligence || {};
    if (!config.ajax_url || !config.nonce || !config.is_logged_in) {
        return;
    }

    var routes = config.routes || {};
    var pageContext = resolvePageContext(config.current_page || config.current_path || window.location.pathname || '/');
    var inserted = false;
    var dismissStorageKey = 'myavana-ai-feed-dismissed:' + pageContext;

    function resolvePageContext(value) {
        value = String(value || '').toLowerCase();
        if (value.indexOf('goals') !== -1) return 'goals';
        if (value.indexOf('routines') !== -1) return 'routines';
        if (value.indexOf('community') !== -1) return 'community';
        if (value.indexOf('profile') !== -1) return 'profile';
        if (value.indexOf('hair-journey') !== -1 || value === 'journey') return 'journey';
        if (value === '' || value === '/' || value === 'home') return 'home';
        return 'journey';
    }

    function requestFeed() {
        var body = new URLSearchParams();
        body.append('action', 'myavana_get_ai_intelligence_feed');
        body.append('nonce', String(config.nonce));
        body.append('page_context', pageContext);

        return fetch(config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(payload) {
            if (!payload || !payload.success || !payload.data) {
                return [];
            }
            return Array.isArray(payload.data.components) ? payload.data.components : [];
        }).catch(function() {
            return [];
        });
    }

    function findAnchor() {
        var selectors = {
            home: ['.myavana-luxury-member-hub-container', '.myavana-luxury-homepage'],
            goals: ['.myavana-goals-v2-page', '.hair-journey-container'],
            routines: ['.myavana-routines-v2-page', '.hair-journey-container'],
            community: ['.myavana-community-container', 'main'],
            profile: ['.myavana-up-shell', '.hair-journey-container', 'main'],
            journey: ['.hair-journey-container', '.timeline-view-hjn', 'main']
        };

        var candidates = selectors[pageContext] || selectors.journey;
        for (var i = 0; i < candidates.length; i++) {
            var node = document.querySelector(candidates[i]);
            if (node) {
                return node;
            }
        }

        return document.querySelector('main') || document.body;
    }

    function renderFeed(components) {
        if (!components.length || inserted || isFeedDismissed()) {
            return;
        }

        var anchor = findAnchor();
        if (!anchor) {
            return;
        }

        inserted = true;

        var wrapper = document.createElement('section');
        wrapper.className = 'myavana-ai-feed';
        wrapper.setAttribute('data-myavana-track-section', 'ai-feed-' + pageContext);
        wrapper.innerHTML = [
            '<div class="myavana-ai-feed-head">',
            '<div class="myavana-ai-feed-copy">',
            '<span class="myavana-ai-feed-kicker">AI Guide</span>',
            '<h3>Quiet guidance from your journey data.</h3>',
            '</div>',
            '<div class="myavana-ai-feed-tools">',
            '<button type="button" class="myavana-ai-feed-refresh" aria-label="Refresh AI suggestions">Refresh</button>',
            '<button type="button" class="myavana-ai-feed-dismiss" aria-label="Dismiss AI suggestions">Close</button>',
            '</div>',
            '</div>',
            '<div class="myavana-ai-feed-grid"></div>'
        ].join('');

        var grid = wrapper.querySelector('.myavana-ai-feed-grid');
        components.slice(0, 1).forEach(function(component) {
            grid.appendChild(renderCard(component));
            trackAIEvent('impression', component);
        });

        wrapper.querySelector('.myavana-ai-feed-refresh').addEventListener('click', function() {
            clearFeedDismissal();
            wrapper.remove();
            inserted = false;
            refreshFeed(true);
        });

        wrapper.querySelector('.myavana-ai-feed-dismiss').addEventListener('click', function() {
            dismissFeed(wrapper);
        });

        anchor.insertBefore(wrapper, anchor.firstChild);
    }

    function renderCard(component) {
        var card = document.createElement('article');
        card.className = 'myavana-ai-card type-' + (component.component_type || 'suggestion');
        card.setAttribute('data-component-id', String(component.id || ''));

        var badge = prettyType(component.component_type || 'suggestion');
        var cta = component.cta_label && component.cta_action
            ? '<button type="button" class="myavana-ai-cta">' + escapeHtml(component.cta_label) + '</button>'
            : '';

        card.innerHTML = [
            '<div class="myavana-ai-card-top">',
            '<span class="myavana-ai-badge">' + escapeHtml(badge) + '</span>',
            '<div class="myavana-ai-card-actions">',
            '<button type="button" class="myavana-ai-icon" data-state="saved" aria-label="Save suggestion">Save</button>',
            '<button type="button" class="myavana-ai-icon" data-state="dismissed" aria-label="Dismiss suggestion">Hide</button>',
            '</div>',
            '</div>',
            '<h4>' + escapeHtml(component.title || '') + '</h4>',
            '<p>' + escapeHtml(component.body || '') + '</p>',
            '<div class="myavana-ai-card-foot">' + cta + '</div>'
        ].join('');

        var saveBtn = card.querySelector('[data-state="saved"]');
        var dismissBtn = card.querySelector('[data-state="dismissed"]');
        var ctaBtn = card.querySelector('.myavana-ai-cta');

        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                updateState(component.id, 'saved').then(function() {
                    trackAIEvent('save', component);
                    card.remove();
                    cleanupFeed();
                });
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                updateState(component.id, 'dismissed').then(function() {
                    trackAIEvent('dismiss', component);
                    card.remove();
                    cleanupFeed();
                });
            });
        }

        if (ctaBtn) {
            ctaBtn.addEventListener('click', function() {
                updateState(component.id, 'applied');
                trackAIEvent('cta', component);
                runAction(component.cta_action);
            });
        }

        return card;
    }

    function updateState(componentId, state) {
        var body = new URLSearchParams();
        body.append('action', 'myavana_update_ai_component_state');
        body.append('nonce', String(config.nonce));
        body.append('component_id', String(componentId || ''));
        body.append('state', state);

        return fetch(config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        }).catch(function() {
            return null;
        });
    }

    function runAction(action) {
        action = String(action || '');
        if (!action) {
            return;
        }

        if (action.indexOf('navigate:') === 0) {
            window.location.href = action.slice(9);
            return;
        }

        if (action === 'action:add_entry' && window.MyavanaLuxuryHomepage && typeof window.MyavanaLuxuryHomepage.openEntryModal === 'function') {
            window.MyavanaLuxuryHomepage.openEntryModal();
            return;
        }

        if (action === 'action:open_ai_analysis' && typeof window.openAIAnalysisModal === 'function') {
            window.openAIAnalysisModal();
            return;
        }

        if (action === 'action:create_goal' && typeof window.createGoal === 'function') {
            window.createGoal();
            return;
        }

        if (action === 'action:create_routine' && typeof window.createRoutine === 'function') {
            window.createRoutine();
            return;
        }

        if (routes.timeline) {
            window.location.href = routes.timeline;
        }
    }

    function trackAIEvent(name, component) {
        if (!window.MyavanaSiteIntelligence || typeof window.MyavanaSiteIntelligence.track !== 'function') {
            return;
        }

        window.MyavanaSiteIntelligence.track('ai_component', name, {
            page_context: pageContext,
            section_key: 'ai_feed',
            event_value: component.component_type || '',
            meta: {
                component_id: component.id || 0,
                title: component.title || '',
                model_name: component.model_name || ''
            }
        });
    }

    function cleanupFeed() {
        var wrapper = document.querySelector('.myavana-ai-feed');
        if (!wrapper) {
            return;
        }

        if (!wrapper.querySelector('.myavana-ai-card')) {
            wrapper.remove();
            inserted = false;
        }
    }

    function dismissFeed(wrapper) {
        storeFeedDismissal();
        if (wrapper && wrapper.parentNode) {
            wrapper.parentNode.removeChild(wrapper);
        }
        inserted = false;
    }

    function isFeedDismissed() {
        try {
            return window.sessionStorage.getItem(dismissStorageKey) === '1';
        } catch (error) {
            return false;
        }
    }

    function storeFeedDismissal() {
        try {
            window.sessionStorage.setItem(dismissStorageKey, '1');
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function clearFeedDismissal() {
        try {
            window.sessionStorage.removeItem(dismissStorageKey);
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function prettyType(type) {
        switch (type) {
            case 'goal_nudge': return 'Goal Focus';
            case 'routine_nudge': return 'Routine Help';
            case 'analysis_nudge': return 'AI Insight';
            case 'gamification_nudge': return 'Momentum';
            case 'advice': return 'Advice';
            case 'insight': return 'Insight';
            default: return 'Suggestion';
        }
    }

    function refreshFeed(forceRefresh) {
        clearFeedDismissal();
        var existing = document.querySelector('.myavana-ai-feed');
        if (existing) {
            existing.remove();
        }

        var body = new URLSearchParams();
        body.append('action', 'myavana_get_ai_intelligence_feed');
        body.append('nonce', String(config.nonce));
        body.append('page_context', pageContext);
        if (forceRefresh) {
            body.append('force_refresh', '1');
        }

        fetch(config.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(payload) {
            if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.components)) {
                return;
            }
            renderFeed(payload.data.components);
        }).catch(function() {
            // Silent fail for non-intrusive UI.
        });
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    requestFeed().then(renderFeed);
})();
