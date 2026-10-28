/* NOVA CORE — Plausible + Zaraz custom events
 *
 * Spec: Rapid Site/Plausible/event-survey-2026-10/nova-core-tracking-prompt.md
 *
 * Fixed event names, identical on every site:
 *   Contact Form Submitted  { form, section }
 *   Download Submitted      { asset, form, section }
 *   Newsletter Signup       { form, section }
 *   Directory Click         { business, type }   (inside [data-track-zone="directory"])
 *   Phone Click             { section, label }
 *   Email Click             { section, label }
 *   Nav Click               { label, menu }      (menu: header / footer / mobile)
 *   CTA Click               { label, section }
 *   Purchase Click          { product }          (links carrying [data-product])
 *
 * No other custom names, no composite "Label - Section - Page" names, and no
 * `page` prop: Plausible records the URL with every event. Section views and
 * Scroll Depth are gone — Plausible's own engagement event covers scrolling.
 *
 * Every click fires exactly ONE event. Precedence, first match wins:
 *   directory zone > [data-product] > tel: > mailto: > nav menus > CTA.
 * Form events fire on the form plugin's SUCCESS callback (Fluent Forms,
 * Bricks, WPForms, Gravity), never on the submit click, with a duplicate
 * guard. Unclassified forms send nothing, so WooCommerce add-to-cart, search,
 * login and comment forms are silent by construction.
 *
 * Every event goes to both plausible() and zaraz.track() with identical names
 * and props. The reduced volume is deliberate: section views were overflowing
 * Zaraz's free-tier event cap and cutting GA4 off mid-month.
 *
 * `section` comes from the nearest ancestor's data-track-section or data-name
 * (the existing fleet convention), else "unlabelled". Never a brxe-* class or
 * element id, never position-numbered.
 */
(function () {
  'use strict';

  // Honest automation (headless browsers, crawlers) identifies itself here; send nothing.
  if (window.navigator.webdriver) return;

  var config = window.novaCoreConfig || {};
  var isDevMode = config.environment !== 'production';
  var formMap = config.formMap || {}; // { identifier: { type, asset? } } from plugin settings

  function track(eventName, props) {
    if (isDevMode) {
      console.log('📊 Track Event:', eventName, props);
    }
    if (typeof window.plausible === 'function') {
      window.plausible(eventName, { props: props });
    }
    if (typeof window.zaraz !== 'undefined' && typeof window.zaraz.track === 'function') {
      window.zaraz.track(eventName, props);
    }
  }

  // Collapse whitespace; textContent/getAttribute already decode HTML entities.
  function clean(text) {
    return (text || '').replace(/\s+/g, ' ').trim();
  }

  function sectionFor(el) {
    var labelled = el.closest('[data-track-section]');
    if (labelled) return clean(labelled.getAttribute('data-track-section')) || 'unlabelled';
    labelled = el.closest('[data-name]');
    if (labelled) return clean(labelled.getAttribute('data-name')) || 'unlabelled';
    return 'unlabelled';
  }

  function labelFor(el) {
    var override = el.getAttribute('data-click');
    if (override) return clean(override);
    var text = clean((el.textContent || '').replace(/[↗→↘↙↖↪︎›»]/g, ''));
    if (text) return text;
    return clean(el.getAttribute('aria-label') || el.getAttribute('href')) || 'unlabelled';
  }

  function menuFor(el) {
    if (el.closest('[data-track-menu="mobile"], .bricks-mobile-menu-wrapper, .brx-mobile-menu, .mobile-menu')) return 'mobile';
    if (el.closest('header, [data-track-menu="header"]')) return 'header';
    if (el.closest('footer, [data-track-menu="footer"]')) return 'footer';
    return null;
  }

  var BUTTONISH = /btn|button/i;

  function isCta(el) {
    if (el.hasAttribute('data-click')) return true;
    var tag = el.tagName;
    if (tag === 'BUTTON') return true;
    if (tag === 'INPUT' && (el.type === 'submit' || el.type === 'button')) return true;
    if (tag === 'A' && BUTTONISH.test(el.className)) return true;
    return false;
  }

  /* ---------- Clicks: one delegated listener, one event per click ---------- */
  function onClick(ev) {
    if (!ev.target || !ev.target.closest) return;
    var el = ev.target.closest('a[href], button, input[type="submit"], input[type="button"], [data-click]');
    if (!el) return;

    var href = (el.tagName === 'A' && el.getAttribute('href')) || '';

    // 1. Directory zone claims all its links (so its tel:/mailto: links do
    //    NOT also fire Phone/Email Click).
    if (el.closest('[data-track-zone="directory"]')) {
      var type = href.indexOf('tel:') === 0 ? 'phone'
        : href.indexOf('mailto:') === 0 ? 'email'
        : 'website';
      var bizEl = el.closest('[data-business]');
      track('Directory Click', {
        business: clean(bizEl && bizEl.getAttribute('data-business')) || 'unlabelled',
        type: type
      });
      return;
    }

    // 2. Click-out to an external retailer.
    var productEl = el.closest('[data-product]');
    if (productEl) {
      track('Purchase Click', { product: clean(productEl.getAttribute('data-product')) || 'unlabelled' });
      return;
    }

    // 3. / 4. Phone and email.
    if (href.indexOf('tel:') === 0) {
      track('Phone Click', { section: sectionFor(el), label: labelFor(el) });
      return;
    }
    if (href.indexOf('mailto:') === 0) {
      track('Email Click', { section: sectionFor(el), label: labelFor(el) });
      return;
    }

    // 5. Navigation links.
    if (el.tagName === 'A') {
      var menu = menuFor(el);
      if (menu) {
        track('Nav Click', { label: labelFor(el), menu: menu });
        return;
      }
    }

    // A form's submit control belongs to the form's success event; the click
    // itself sends nothing (and nothing at all for unclassified forms).
    if (el.closest('form')) return;

    // 6. Everything else that looks like a CTA.
    if (isCta(el)) {
      track('CTA Click', { label: labelFor(el), section: sectionFor(el) });
    }
  }

  /* ---------- Forms: success callbacks only, one event per submission ---------- */
  var EVENT_FOR_TYPE = {
    contact: 'Contact Form Submitted',
    download: 'Download Submitted',
    newsletter: 'Newsletter Signup'
  };

  var recentFormFires = {};

  function classify(identifier, formEl) {
    var type = null;
    var asset = null;
    if (formEl && formEl.closest) {
      var typeEl = formEl.hasAttribute('data-form-type') ? formEl : formEl.closest('[data-form-type]');
      if (typeEl) type = clean(typeEl.getAttribute('data-form-type')).toLowerCase();
      var assetEl = formEl.hasAttribute('data-asset') ? formEl : formEl.closest('[data-asset]');
      if (assetEl) asset = assetEl.getAttribute('data-asset');
    }
    var mapped = formMap[identifier];
    if (!type && mapped) type = mapped.type;
    if (!asset && mapped && mapped.asset) asset = mapped.asset;
    return { type: type, asset: asset };
  }

  function formName(identifier, formEl) {
    if (formEl && formEl.getAttribute) {
      var named = clean(formEl.getAttribute('data-form-name') || formEl.getAttribute('aria-label'));
      if (named) return named;
      if (formEl.id) return formEl.id;
    }
    return identifier;
  }

  function formSuccess(identifier, formEl) {
    // Duplicate guard: the same submission must never fire twice, whatever
    // combination of plugin events reports it.
    var now = Date.now();
    if (recentFormFires[identifier] && now - recentFormFires[identifier] < 2000) return;

    var classification = classify(identifier, formEl);
    var eventName = EVENT_FOR_TYPE[classification.type];
    if (!eventName) {
      if (isDevMode) console.log('📊 Unclassified form, nothing sent:', identifier);
      return;
    }
    recentFormFires[identifier] = now;

    var props = {
      form: formName(identifier, formEl),
      section: formEl ? sectionFor(formEl) : 'unlabelled'
    };
    if (classification.type === 'download') {
      props.asset = clean(classification.asset) || 'unlabelled';
    }
    track(eventName, props);
  }

  function wireForms() {
    // Bricks forms: CustomEvent on document with { elementId, formData, res }.
    document.addEventListener('bricks/form/success', function (ev) {
      var elementId = ev.detail && ev.detail.elementId;
      var formEl = elementId ? document.querySelector('[data-element-id="' + elementId + '"]') : null;
      formSuccess('bricks-' + (elementId || 'unknown'), formEl);
    });

    // The jQuery-based plugins all require jQuery themselves, so if it's
    // absent none of these forms exist on the page.
    if (!window.jQuery) return;

    // Fluent Forms (the fleet's primary form plugin).
    window.jQuery(document).on('fluentform_submission_success', function (e, details) {
      var formEl = details && details.form
        ? (details.form.get ? details.form.get(0) : details.form)
        : null;
      var id = formEl && formEl.getAttribute && formEl.getAttribute('data-form_id');
      formSuccess('ff-' + (id || 'unknown'), formEl);
    });

    // WPForms (AJAX submissions).
    window.jQuery(document).on('wpformsAjaxSubmitSuccess', function (e) {
      var formEl = e.target && e.target.tagName === 'FORM'
        ? e.target
        : (e.target && e.target.closest ? e.target.closest('form') : null);
      var id = formEl && formEl.getAttribute && formEl.getAttribute('data-formid');
      formSuccess('wpforms-' + (id || 'unknown'), formEl);
    });

    // Gravity Forms (AJAX confirmations).
    window.jQuery(document).on('gform_confirmation_loaded', function (e, formId) {
      formSuccess('gform-' + formId, document.getElementById('gform_' + formId));
    });
  }

  function init() {
    if (isDevMode) {
      console.log('=== NOVA CORE TRACKING v0.2.0 (DEV MODE) ===');
    }
    document.addEventListener('click', onClick);
    wireForms();
  }

  function onReady() {
    // A page that is never visible must send zero custom events, matching how
    // Plausible's own script.js defers the pageview until the document is visible.
    if (document.visibilityState === 'visible' || document.visibilityState === undefined) {
      init();
    } else {
      document.addEventListener('visibilitychange', function onVis() {
        if (document.visibilityState !== 'visible') return;
        document.removeEventListener('visibilitychange', onVis);
        init();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
