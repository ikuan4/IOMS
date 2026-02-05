// SPA Navigation System
// Handles client-side routing to prevent header/sidebar reloads

class SPANavigator {
    constructor() {
        this.mainContent = null;
        this.isNavigating = false;
        this.currentUrl = window.location.href;
        this.cache = new Map();
        this.maxCacheSize = 10;
    }

    init() {
        this.mainContent = document.querySelector('#pjax-container') || document.querySelector('main.main');
        if (!this.mainContent) {
            console.warn('SPA Navigation: main content container not found');
            return;
        }

        // Intercept all internal navigation clicks
        this.interceptLinks();

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.url) {
                this.loadPage(e.state.url, false);
            }
        });

        // Save initial state
        history.replaceState({ url: this.currentUrl }, '', this.currentUrl);
    }

    interceptLinks() {
        document.addEventListener('click', (e) => {
            // Find the closest anchor tag
            const link = e.target.closest('a');

            if (!link) return;

            // Skip if it's an external link, has target="_blank", or is a special link
            const href = link.getAttribute('href');
            if (!href ||
                href.startsWith('#') ||
                href.startsWith('javascript:') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:') ||
                link.target === '_blank' ||
                link.hasAttribute('download') ||
                link.classList.contains('no-spa')) {
                return;
            }

            // Determine if link is external. In production behind proxies, generated URLs may differ by scheme
            // (http vs https), so we compare hostname instead of full origin.
            let isExternal = false;
            try {
                const parsed = new URL(link.href, window.location.href);
                isExternal = parsed.hostname && parsed.hostname !== window.location.hostname;
            } catch {
                isExternal = false;
            }
            if (isExternal) {
                return;
            }

            // Skip if link is in sidebar-footer or for logout
            if (link.closest('.sidebar-footer') || href.includes('logout')) {
                return;
            }

            e.preventDefault();

            const url = link.href;
            if (url !== this.currentUrl) {
                this.navigate(url);
            }
        });
    }

    navigate(url) {
        if (this.isNavigating) return;

        this.loadPage(url, true);
    }

    async loadPage(url, addToHistory = true) {
        if (this.isNavigating) return;

        this.isNavigating = true;
        this.showLoadingIndicator();

        try {
            // Add timestamp to URL to bypass cache
            const fetchUrl = new URL(url);
            fetchUrl.searchParams.set('_t', Date.now());

            // Always fetch fresh content (disable cache for JavaScript updates)
            const response = await fetch(fetchUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SPA-Navigation': 'true',
                    'Accept': 'text/html',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const html = await response.text();

            // Extract main content from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMainContent = doc.querySelector('#pjax-container') || doc.querySelector('main.main');

            if (newMainContent) {
                // Update the main content
                this.mainContent.innerHTML = newMainContent.innerHTML;

                // Update page title
                const newTitle = doc.querySelector('title');
                if (newTitle) {
                    document.title = newTitle.textContent;
                }

                // Update active states in sidebar
                this.updateActiveStates(url);

                // Re-initialize feather icons
                if (window.feather) {
                    window.feather.replace();
                }

                // Execute any scripts in the new content
                this.executeScripts(newMainContent);

                // Trigger custom event for other modules
                window.dispatchEvent(new CustomEvent('spa:navigated', {
                    detail: { url }
                }));

                // Update URL and history
                if (addToHistory) {
                    history.pushState({ url }, '', url);
                }

                this.currentUrl = url;

                // Scroll to top
                window.scrollTo(0, 0);
            } else {
                throw new Error('Could not find main content in response');
            }

        } catch (error) {
            console.error('SPA Navigation Error:', error);
            // Fallback to full page load
            window.location.href = url;
            return;
        } finally {
            this.isNavigating = false;
            this.hideLoadingIndicator();
        }
    }

    showLoadingIndicator() {
        // Add a loading class to main content
        this.mainContent?.classList.add('spa-loading');

        // Optional: show a loading overlay
        let overlay = document.getElementById('spa-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'spa-loading-overlay';
            overlay.className = 'spa-loading-overlay';
            overlay.innerHTML = '<div class="spa-spinner"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    hideLoadingIndicator() {
        this.mainContent?.classList.remove('spa-loading');

        const overlay = document.getElementById('spa-loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    updateActiveStates(url) {
        // Remove all active classes from sidebar links
        const sidebarLinks = document.querySelectorAll('.sidebar .nav a, .sidebar .nav-submenu a');
        sidebarLinks.forEach(link => {
            link.classList.remove('active');
        });

        const normalizePath = (rawUrl) => {
            if (!rawUrl) return null;
            try {
                const parsed = new URL(rawUrl, window.location.origin);
                let path = parsed.pathname || '/';
                // Normalize trailing slash (except root)
                if (path.length > 1 && path.endsWith('/')) {
                    path = path.slice(0, -1);
                }
                return path;
            } catch {
                return null;
            }
        };

        const currentPath = normalizePath(url);

        // Pick the most specific matching link (longest matching path)
        let matchingLink = null;
        let bestMatchLength = -1;

        Array.from(sidebarLinks).forEach(link => {
            const linkHref = link.getAttribute('href');
            const linkPath = normalizePath(linkHref);
            if (!currentPath || !linkPath) return;

            const isExact = currentPath === linkPath;
            const isPrefix = currentPath.startsWith(linkPath + '/');
            if (!isExact && !isPrefix) return;

            const score = linkPath.length + (isExact ? 10000 : 0);
            if (score > bestMatchLength) {
                bestMatchLength = score;
                matchingLink = link;
            }
        });

        if (matchingLink) {
            matchingLink.classList.add('active');

            // Also ensure parent nav-group is open if link is in submenu
            const navGroup = matchingLink.closest('.nav-group');
            if (navGroup) {
                navGroup.classList.add('open');
                const toggle = navGroup.querySelector('.nav-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    }

    executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });

            // Don't wrap inline scripts - execute them directly to preserve global scope
            // This allows functions to be called from HTML attributes like oninput
            newScript.textContent = oldScript.textContent;

            document.body.appendChild(newScript);
            document.body.removeChild(newScript);
        });
    }

    addToCache(url, html) {
        // Limit cache size
        if (this.cache.size >= this.maxCacheSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        this.cache.set(url, html);
    }

    clearCache() {
        this.cache.clear();
    }
}

// Initialize SPA navigation when DOM is ready
let spaNavigator = null;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        spaNavigator = new SPANavigator();
        spaNavigator.init();
    });
} else {
    spaNavigator = new SPANavigator();
    spaNavigator.init();
}

// Export for potential external use
window.spaNavigator = spaNavigator;
