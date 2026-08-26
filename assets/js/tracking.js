document.addEventListener('DOMContentLoaded', function () {
    // Honest automation (headless browsers, crawlers) identifies itself here; send nothing.
    if (window.navigator.webdriver) return;

    // Use the working configuration source
    const config = window.novaCoreConfig || {};
    const isDevMode = config.environment !== 'production';

    // Log init message only in dev mode
    if (isDevMode) {
      console.log('=== NOVA CORE TRACKING v0.1.64 (DEV MODE) ===');
    }

    function getWPPageName() {
      const config = window.novaCoreConfig || {};
      if (config.pageTitle) return config.pageTitle;

      const body = document.body;

      // Check for page-name-* classes first
      const match = [...body.classList].find(cls => cls.startsWith('page-name-'));
      if (match) return match.replace('page-name-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

      // Check for WordPress standard classes
      if (body.classList.contains('home')) return 'Home';
      if (body.classList.contains('blog')) return 'Blog';
      if (body.classList.contains('archive')) return 'Archive';
      if (body.classList.contains('single')) return 'Single Post';
      if (body.classList.contains('page')) return 'Page';
      if (body.classList.contains('search')) return 'Search Results';
      if (body.classList.contains('404')) return 'Page Not Found';

      // Check for post type classes
      const postTypeMatch = [...body.classList].find(cls => cls.startsWith('post-type-'));
      if (postTypeMatch) {
        const postType = postTypeMatch.replace('post-type-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        return postType;
      }

      // Check for taxonomy classes
      const taxonomyMatch = [...body.classList].find(cls => cls.startsWith('tax-'));
      if (taxonomyMatch) {
        const taxonomy = taxonomyMatch.replace('tax-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        return taxonomy;
      }

      // Fallback to document title
      if (document.title) {
        return document.title.replace(/[-|–] .*$/, '').trim();
      }

      return 'Unknown Page';
    }

    function trackEvent(eventName, props) {
      // Log to console in dev mode
      if (isDevMode) {
        console.log('📊 Track Event:', eventName, props);
      }

      // Always send to Plausible if available (via official WP plugin)
      if (typeof plausible === 'function') {
        plausible(eventName, { props });
      }

      // Always send to Zaraz if available (handles GA and other tools)
      if (typeof window.zaraz !== 'undefined' && typeof window.zaraz.track === 'function') {
        window.zaraz.track(eventName, props);
      }
    }

    function getSectionName(el) {
      const section = el.closest('section');
      if (!section) return 'Global (no section)';

      // Check if section has data-track-inside attribute
      if (section.hasAttribute('data-track-inside')) {
        // Find the closest element with data-name inside the section
        const namedElement = el.closest('[data-name]');
        if (namedElement && section.contains(namedElement)) {
          return namedElement.getAttribute('data-name');
        }
      }

      // Fall back to original behavior
      return section.getAttribute('data-name') ||
             section.getAttribute('id') ||
             Array.from(section.classList).join(' ') ||
             'Unnamed Section';
    }

    function init() {
      // SCROLL TRACKING
      const trackedSections = [];
      const observedSections = document.querySelectorAll(
        'main > section:not(.no-scroll-track), main > .brxe-template > section:not(.no-scroll-track), footer'
      );

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const sec = entry.target;
            const page = getWPPageName();

            // Check if this is a data-track-inside section
            if (sec.hasAttribute('data-track-inside')) {
              // Find all elements with data-name inside this section
              const namedElements = sec.querySelectorAll('[data-name]');
              namedElements.forEach(namedEl => {
                const section = namedEl.getAttribute('data-name');
                if (!trackedSections.includes(section)) {
                  trackedSections.push(section);
                  const props = { section, page };
                  // Send descriptive event name to Plausible for better Goals visibility
                  const eventName = `Viewed: ${section}`;
                  if (typeof plausible === 'function') plausible(eventName, { props });
                  trackEvent('Viewed Section', props);
                }
              });
            } else {
              // Original behavior for regular sections
              const section = sec.getAttribute('data-name') ||
                            sec.getAttribute('id') ||
                            Array.from(sec.classList).join(' ') ||
                            'Unnamed Section';

              if (!trackedSections.includes(section)) {
                trackedSections.push(section);
                const props = { section, page };
                // Send descriptive event name to Plausible for better Goals visibility
                const eventName = `Viewed: ${section}`;
                if (typeof plausible === 'function') plausible(eventName, { props });
                trackEvent('Viewed Section', props);
              }
            }
          }
        });
      }, { threshold: 0.25 });

      observedSections.forEach(section => observer.observe(section));

      // CLICK TRACKING
      document.querySelectorAll('[data-click], [data-plausible]').forEach(el => {
        el.addEventListener('click', function () {
          const eventName = el.getAttribute('data-click') ||
                           el.getAttribute('data-plausible') ||
                           'Unknown Button';

          // Get the section name, handling data-track-inside
          let section;
          const parentSection = el.closest('section');
          if (parentSection && parentSection.hasAttribute('data-track-inside')) {
            const namedElement = el.closest('[data-name]');
            if (namedElement && parentSection.contains(namedElement)) {
              section = namedElement.getAttribute('data-name');
            }
          }
          if (!section) {
            section = getSectionName(el);
          }

          const page = getWPPageName();
          const props = { section, page };

          // Format event name for Plausible: "Event Name - Section - Page"
          const plausibleEventName = `${eventName} - ${section} - ${page}`;
          if (typeof plausible === 'function') plausible(plausibleEventName, { props });
          trackEvent(eventName, props);
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

          if (typeof plausible === 'function') plausible(eventName, { props });
          trackEvent(eventName, props);
        });
      });

      // MENU ITEM CLICK TRACKING
      document.querySelectorAll('nav a:not([data-click]):not([data-plausible]), .menu a:not([data-click]):not([data-plausible]), .main-menu a:not([data-click]):not([data-plausible]), .bricks-nav-menu-wrapper a:not([data-click]):not([data-plausible])').forEach(link => {
        link.addEventListener('click', function () {
          const eventName = 'Menu Click';
          const section = getSectionName(link);
          const menuContainer = link.closest('nav, ul, .bricks-nav-menu-wrapper');
          const menu = menuContainer
            ? menuContainer.getAttribute('id') ||
              Array.from(menuContainer.classList).join(' ') ||
              'Unnamed Menu'
            : 'Unknown Menu';

          const label = link.textContent.trim() || link.getAttribute('href') || 'Unnamed Link';
          const page = getWPPageName();

          // Check if menu is in header or footer
          const location = link.closest('header') ? 'header' :
                          link.closest('footer') ? 'footer' :
                          'main';

          const props = {
            section,
            menu,
            label,
            page,
            location
          };

          if (typeof plausible === 'function') plausible(eventName, { props });
          trackEvent(eventName, props);
        });
      });
    }

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
  });
