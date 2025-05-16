document.addEventListener('DOMContentLoaded', function () {
    const config = window.trackingConfig || {};
    const isProduction = config.environment === 'production';
    const trackingEnabled = config.trackingEnabled !== false;
  
    // Initialize tracking config if it doesn't exist
    window.trackingConfig = window.trackingConfig || {};
  
    function detectZaraz() {
        // Method 1: Check for zaraz object (when enabled)
        if (typeof window.zaraz !== 'undefined' && typeof window.zaraz.track === 'function') {
            return true;
        }

        // Method 2: Check for zaraz script tag
        const zarazScript = document.querySelector('script[src*="zaraz"]');
        if (zarazScript) {
            return true;
        }

        // Method 3: Check for zaraz cookie
        if (document.cookie.includes('_zaraz')) {
            return true;
        }

        // Method 4: Check for zaraz meta tag
        const zarazMeta = document.querySelector('meta[name="zaraz"]');
        if (zarazMeta) {
            return true;
        }

        return false;
    }
  
    function getTrackingMode() {
      if (!trackingEnabled) return 'none';
      if (typeof config.forceMode === 'string' && config.forceMode.length > 0) return config.forceMode;
      if (config.autodetect === false) return 'none';
      
      // Check for Zaraz using multiple detection methods
      if (detectZaraz()) {
        // Notify PHP about Zaraz detection
        window.trackingConfig.detectedZaraz = true;
        // Update the config in the DOM for PHP to read
        const script = document.querySelector('script[data-tracking-config]');
        if (script) {
          script.textContent = 'window.trackingConfig = ' + JSON.stringify(window.trackingConfig) + ';';
        }
        return 'zaraz';
      }
      
      // Check for Google Analytics
      if (typeof gtag === 'function') return 'gtag';
      return 'none';
    }
  
    function getWPPageName() {
      const config = window.trackingConfig || {};
      if (config.pageTitle) return config.pageTitle;
      
      const body = document.body;
      const match = [...body.classList].find(cls => cls.startsWith('page-name-'));
      if (match) return match.replace('page-name-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
      if (body.classList.contains('home')) return 'Home';
      if (body.classList.contains('blog')) return 'Blog';
      if (body.classList.contains('archive')) return 'Archive';
      return 'Unknown Page';
    }
  
    function trackEvent(eventName, props) {
      const mode = getTrackingMode();
      if (mode === 'zaraz') {
        zaraz.track(eventName, props);
      } else if (mode === 'gtag') {
        gtag('event', eventName, {
          event_category: 'Custom Tracking',
          event_label: props.label || props.section || 'Unknown',
          page_title: props.page || document.title,
          section: props.section || 'Unknown'
        });
      } else {
        console.warn('No tracking backend available → event not sent:', eventName, props);
      }
    }
  
    function getSectionName(el) {
      const section = el.closest('section');
      return section
        ? section.getAttribute('data-name') ||
          section.getAttribute('id') ||
          Array.from(section.classList).join(' ') ||
          'Unnamed Section'
        : 'Global (no section)';
    }
  
    // CLICK TRACKING
    document.querySelectorAll('[data-click], [data-plausible]').forEach(el => {
      el.addEventListener('click', function () {
        const eventName = el.getAttribute('data-click') || 
                         el.getAttribute('data-plausible') || 
                         'Unknown Button';
        const section = getSectionName(el);
        const page = getWPPageName();
        const props = { section, page };
  
        if (isProduction) {
          if (typeof plausible === 'function') plausible(eventName, { props });
          trackEvent(eventName, props);
        } else {
          console.info('Staging mode → click event suppressed:', Object.assign({ event: eventName }, props));
        }
      });
    });
  
    // FLUENT FORMS TRACKING
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function () {
        const eventNameInput = form.querySelector('[name="ff_event_name"]');
        const eventName = eventNameInput ? eventNameInput.value : 'Form Submitted';
        const section = getSectionName(form);
        const page = getWPPageName();
        const props = { section, page };
  
        if (isProduction) {
          if (typeof plausible === 'function') plausible(eventName, { props });
          trackEvent(eventName, props);
        } else {
          console.info('Staging mode → form event suppressed:', Object.assign({ event: eventName }, props));
        }
      });
    });
  
    // SCROLL TRACKING
    const trackedSections = [];
    const observedSections = document.querySelectorAll(
      'main > section:not(.no-scroll-track), main > .brxe-template > section:not(.no-scroll-track), footer'
    );
  
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const sec = entry.target;
          const section = sec.getAttribute('data-name') ||
                          sec.getAttribute('id') ||
                          Array.from(sec.classList).join(' ') ||
                          'Unnamed Section';
  
          if (!trackedSections.includes(section)) {
            trackedSections.push(section);
            const props = { section, page: getWPPageName() };
            const eventName = 'Viewed Section';
  
            if (isProduction) {
              trackEvent(eventName, props);
            } else {
              console.info('Staging mode → scroll event suppressed:', Object.assign({ event: eventName }, props));
            }
          }
        }
      });
    }, { threshold: 0.25 });
  
    observedSections.forEach(section => observer.observe(section));
  
    // MENU ITEM CLICK TRACKING
    document.querySelectorAll('nav a, .menu a, .main-menu a').forEach(link => {
      link.addEventListener('click', function () {
        const eventName = 'Menu Click';
        const section = getSectionName(link);
        const menuContainer = link.closest('nav, ul');
        const menu = menuContainer
          ? menuContainer.getAttribute('id') ||
            Array.from(menuContainer.classList).join(' ') ||
            'Unnamed Menu'
          : 'Unknown Menu';
  
        const label = link.textContent.trim() || link.getAttribute('href') || 'Unnamed Link';
        const page = getWPPageName();
        const props = { section, menu, label, page };
  
        if (isProduction) {
          if (typeof plausible === 'function') plausible(eventName, { props });
          trackEvent(eventName, props);
        } else {
          console.info('Staging mode → menu click suppressed:', Object.assign({ event: eventName }, props));
        }
      });
    });
  });
  