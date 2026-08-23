(function() {
    'use strict';

    var config = window.myavanaAIIntelligence || {};
    if (!config.ajax_url || !config.nonce || !config.is_logged_in) return;

    var routes = config.routes || {};
    var pageContext = resolvePageContext(config.current_page || config.current_path || window.location.pathname || '/');
    var components = [];
    var isOpen = false;
    var toastStorageKey = 'myavana-ai-toast-shown:' + pageContext;

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

    function requestFeed(forceRefresh) {
        var body = new URLSearchParams();
        body.append('action', 'myavana_get_ai_intelligence_feed');
        body.append('nonce', String(config.nonce));
        body.append('page_context', pageContext);
        if (forceRefresh) body.append('force_refresh', '1');

        return fetch(config.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(payload) {
            return payload && payload.success && payload.data && Array.isArray(payload.data.components)
                ? payload.data.components : [];
        }).catch(function() { return []; });
    }

    function buildUI() {
        if (document.querySelector('.myavana-ai-notification-root')) return;

        var root = document.createElement('div');
        root.className = 'myavana-ai-notification-root';
        root.innerHTML = [
            '<button type="button" class="myavana-ai-guide-trigger" aria-expanded="false" aria-controls="myavanaAiGuidePanel" aria-label="Open AI guidance">',
            '<span class="myavana-ai-guide-trigger-icon" aria-hidden="true">✦</span>',
            '<span class="myavana-ai-guide-trigger-label">Guidance</span>',
            '<span class="myavana-ai-guide-count" hidden>0</span>',
            '</button>',
            '<div class="myavana-ai-guide-scrim" hidden></div>',
            '<aside class="myavana-ai-guide-panel" id="myavanaAiGuidePanel" aria-label="Your AI guidance" aria-hidden="true">',
            '<div class="myavana-ai-guide-panel-head">',
            '<div><span class="myavana-ai-guide-kicker">MYAVANA AI GUIDE</span><h2>Your next best moves</h2><p>Personalized from your profile and recent journey activity.</p></div>',
            '<button type="button" class="myavana-ai-guide-close" aria-label="Close guidance">×</button>',
            '</div>',
            '<div class="myavana-ai-guide-list" aria-live="polite"></div>',
            '<div class="myavana-ai-guide-panel-foot"><button type="button" class="myavana-ai-guide-refresh">Refresh guidance</button></div>',
            '</aside>',
            '<div class="myavana-ai-guide-toast" role="status" aria-live="polite" hidden></div>'
        ].join('');
        document.body.appendChild(root);

        var trigger = root.querySelector('.myavana-ai-guide-trigger');
        var navActions = document.querySelector('.myavana-global-nav-user-actions');
        // Keep the drawer root under <body>. The navbar uses backdrop-filter,
        // which creates a containing block and would otherwise shrink a fixed
        // drawer to the height of the header. Only the trigger lives in nav.
        if (navActions) navActions.insertBefore(trigger, navActions.firstChild);

        trigger.addEventListener('click', togglePanel);
        root.querySelector('.myavana-ai-guide-close').addEventListener('click', closePanel);
        root.querySelector('.myavana-ai-guide-scrim').addEventListener('click', closePanel);
        root.querySelector('.myavana-ai-guide-refresh').addEventListener('click', function() {
            refreshFeed();
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isOpen) closePanel();
        });
    }

    function togglePanel() { isOpen ? closePanel() : openPanel(); }

    function openPanel() {
        var root = document.querySelector('.myavana-ai-notification-root');
        if (!root) return;
        isOpen = true;
        root.classList.add('is-open');
        root.querySelector('.myavana-ai-guide-panel').setAttribute('aria-hidden', 'false');
        getTrigger().setAttribute('aria-expanded', 'true');
        root.querySelector('.myavana-ai-guide-scrim').hidden = false;
        document.body.classList.add('myavana-ai-guide-open');
    }

    function closePanel() {
        var root = document.querySelector('.myavana-ai-notification-root');
        if (!root) return;
        isOpen = false;
        root.classList.remove('is-open');
        root.querySelector('.myavana-ai-guide-panel').setAttribute('aria-hidden', 'true');
        getTrigger().setAttribute('aria-expanded', 'false');
        root.querySelector('.myavana-ai-guide-scrim').hidden = true;
        document.body.classList.remove('myavana-ai-guide-open');
    }

    function render(loadedComponents) {
        components = loadedComponents.slice(0, 3);
        buildUI();
        var root = document.querySelector('.myavana-ai-notification-root');
        if (!root) return;
        var list = root.querySelector('.myavana-ai-guide-list');
        var count = root.querySelector('.myavana-ai-guide-count');
        list.innerHTML = '';
        components.forEach(function(component) { list.appendChild(renderCard(component)); trackAIEvent('impression', component); });
        count.textContent = String(components.length);
        count.hidden = components.length === 0;
        getTrigger().classList.toggle('has-guidance', components.length > 0);
        if (components.length) showToastOnce(components[0]);
    }

    function renderCard(component) {
        var card = document.createElement('article');
        card.className = 'myavana-ai-guide-card type-' + (component.component_type || 'suggestion');
        card.setAttribute('data-component-id', String(component.id || ''));
        var cta = component.cta_label && component.cta_action
            ? '<button type="button" class="myavana-ai-guide-cta">' + escapeHtml(component.cta_label) + '<span aria-hidden="true">→</span></button>' : '';
        card.innerHTML = [
            '<div class="myavana-ai-guide-card-top"><span class="myavana-ai-guide-type">' + escapeHtml(prettyType(component.component_type)) + '</span>',
            '<button type="button" class="myavana-ai-guide-dismiss" aria-label="Dismiss this suggestion">×</button></div>',
            '<h3>' + escapeHtml(component.title || '') + '</h3>',
            '<p>' + escapeHtml(component.body || '') + '</p>',
            '<div class="myavana-ai-guide-card-foot">' + cta + '<button type="button" class="myavana-ai-guide-save">Save for later</button></div>'
        ].join('');
        card.querySelector('.myavana-ai-guide-dismiss').addEventListener('click', function() { setComponentState(component, 'dismissed', card); });
        card.querySelector('.myavana-ai-guide-save').addEventListener('click', function() { setComponentState(component, 'saved', card); });
        var ctaButton = card.querySelector('.myavana-ai-guide-cta');
        if (ctaButton) ctaButton.addEventListener('click', function() {
            updateState(component.id, 'applied');
            trackAIEvent('cta', component);
            runAction(component.cta_action);
        });
        return card;
    }

    function setComponentState(component, state, card) {
        updateState(component.id, state).then(function() {
            trackAIEvent(state === 'saved' ? 'save' : 'dismiss', component);
            card.remove();
            components = components.filter(function(item) { return item !== component; });
            refreshCount();
        });
    }

    function refreshCount() {
        var root = document.querySelector('.myavana-ai-notification-root');
        if (!root) return;
        var count = root.querySelector('.myavana-ai-guide-count');
        count.textContent = String(components.length);
        count.hidden = components.length === 0;
        getTrigger().classList.toggle('has-guidance', components.length > 0);
        if (!components.length) closePanel();
    }

    function showToastOnce(component) {
        try { if (sessionStorage.getItem(toastStorageKey) === '1') return; sessionStorage.setItem(toastStorageKey, '1'); } catch (error) {}
        var toast = document.querySelector('.myavana-ai-guide-toast');
        if (!toast) return;
        toast.innerHTML = '<span class="myavana-ai-toast-mark" aria-hidden="true">✦</span><div><span>AI guidance</span><strong>' + escapeHtml(component.title || 'A new suggestion is ready') + '</strong></div><button type="button" aria-label="Open guidance">View</button><button type="button" class="myavana-ai-toast-dismiss" aria-label="Dismiss notification">×</button>';
        toast.hidden = false;
        window.setTimeout(function() { toast.classList.add('is-visible'); }, 300);
        toast.querySelector('button:not(.myavana-ai-toast-dismiss)').addEventListener('click', function() { toast.hidden = true; openPanel(); });
        toast.querySelector('.myavana-ai-toast-dismiss').addEventListener('click', function() { toast.classList.remove('is-visible'); window.setTimeout(function() { toast.hidden = true; }, 200); });
    }

    function refreshFeed() {
        var button = document.querySelector('.myavana-ai-guide-refresh');
        if (button) { button.disabled = true; button.textContent = 'Refreshing…'; }
        requestFeed(true).then(function(items) { render(items); }).finally(function() {
            if (button) { button.disabled = false; button.textContent = 'Refresh guidance'; }
        });
    }

    function updateState(componentId, state) {
        var body = new URLSearchParams();
        body.append('action', 'myavana_update_ai_component_state');
        body.append('nonce', String(config.nonce));
        body.append('component_id', String(componentId || ''));
        body.append('state', state);
        return fetch(config.ajax_url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString(), credentials: 'same-origin' }).catch(function() { return null; });
    }

    function runAction(action) {
        action = String(action || '');
        if (!action) return;
        if (action.indexOf('navigate:') === 0) { window.location.href = action.slice(9); return; }
        if (action === 'action:add_entry' && window.MyavanaLuxuryHomepage && typeof window.MyavanaLuxuryHomepage.openEntryModal === 'function') { window.MyavanaLuxuryHomepage.openEntryModal(); return; }
        if (action === 'action:open_ai_analysis' && typeof window.openAIAnalysisModal === 'function') { window.openAIAnalysisModal(); return; }
        if (action === 'action:create_goal' && typeof window.createGoal === 'function') { window.createGoal(); return; }
        if (action === 'action:create_routine' && typeof window.createRoutine === 'function') { window.createRoutine(); return; }
        if (routes.timeline) window.location.href = routes.timeline;
    }

    function trackAIEvent(name, component) {
        if (!window.MyavanaSiteIntelligence || typeof window.MyavanaSiteIntelligence.track !== 'function') return;
        window.MyavanaSiteIntelligence.track('ai_component', name, { page_context: pageContext, section_key: 'ai_guide', event_value: component.component_type || '', meta: { component_id: component.id || 0, title: component.title || '', model_name: component.model_name || '' } });
    }

    function prettyType(type) {
        return { goal_nudge: 'Goal focus', routine_nudge: 'Routine care', analysis_nudge: 'Hair insight', gamification_nudge: 'Momentum', advice: 'Guidance', insight: 'Insight' }[type] || 'Suggestion';
    }

    function getTrigger() { return document.querySelector('.myavana-ai-guide-trigger'); }

    function escapeHtml(value) { return String(value || '').replace(/[&<>"']/g, function(char) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]; }); }

    requestFeed().then(render);
})();
