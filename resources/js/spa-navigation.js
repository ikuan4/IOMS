// SPA Navigation System (no inline Blade scripts)
// Replaces ONLY #pjax-container to keep sidebar/header mounted.

class SPANavigator {
    constructor() {
        this.mainContent = null;
        this.isNavigating = false;
        this.currentUrl = null;
    }

    init() {
        this.mainContent = document.querySelector('#pjax-container') || document.querySelector('main.main');
        if (!this.mainContent) {
            console.warn('SPA Navigation: main content container not found');
            return;
        }

        // Save initial state (normalize to current origin to avoid http/https proxy mismatches)
        this.currentUrl = this.normalizeInternalUrl(window.location.href)?.href ?? window.location.href;
        history.replaceState({ url: this.currentUrl }, '', this.currentUrl);

        // Intercept all internal navigation clicks (delegated)
        this.interceptLinks();

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            const target = (e?.state && e.state.url) ? e.state.url : window.location.href;
            this.loadPage(target, { addToHistory: false, scrollTop: false });
        });
    }

    normalizeInternalUrl(rawUrl) {
        if (!rawUrl) return null;
        try {
            const parsed = new URL(rawUrl, window.location.href);

            // External -> ignore
            if (parsed.hostname && parsed.hostname !== window.location.hostname) {
                return null;
            }

            // Force same-origin (fixes production where APP_URL is http behind https proxy)
            const normalized = new URL(parsed.pathname + parsed.search + parsed.hash, window.location.origin);
            return normalized;
        } catch {
            return null;
        }
    }

    interceptLinks() {
        if (document.__spaLinksIntercepted) return;

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            const hrefAttr = link.getAttribute('href');
            if (!hrefAttr ||
                hrefAttr.startsWith('#') ||
                hrefAttr.startsWith('javascript:') ||
                hrefAttr.startsWith('mailto:') ||
                hrefAttr.startsWith('tel:') ||
                link.target === '_blank' ||
                link.hasAttribute('download') ||
                link.classList.contains('no-spa') ||
                link.closest('.sidebar-footer') ||
                hrefAttr.includes('logout')) {
                return;
            }

            const normalized = this.normalizeInternalUrl(link.href || hrefAttr);
            if (!normalized) return;

            // Same-page hash navigation should not be intercepted
            if (normalized.pathname === window.location.pathname && normalized.search === window.location.search && normalized.hash) {
                return;
            }

            e.preventDefault();

            const url = normalized.href;
            if (url !== this.currentUrl) {
                this.loadPage(url, { addToHistory: true, scrollTop: true });
            }
        });

        document.__spaLinksIntercepted = true;
    }

    async loadPage(url, { addToHistory = true, scrollTop = true } = {}) {
        if (this.isNavigating) return;

        const normalized = this.normalizeInternalUrl(url);
        if (!normalized) {
            window.location.href = url;
            return;
        }

        this.isNavigating = true;
        this.showLoadingIndicator();

        try {
            const fetchUrl = new URL(normalized.href);
            fetchUrl.searchParams.set('_t', Date.now());

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

            // If auth redirects happen, do a hard navigation to the final URL.
            if (response.redirected && response.url) {
                const finalUrl = this.normalizeInternalUrl(response.url)?.href;
                if (finalUrl && !finalUrl.includes('/login')) {
                    // continue normally
                } else {
                    window.location.href = response.url;
                    return;
                }
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newMainContent = doc.querySelector('#pjax-container') || doc.querySelector('main.main');
            if (!newMainContent) {
                throw new Error('Could not find #pjax-container in response');
            }

            // Swap ONLY the container
            this.mainContent.innerHTML = newMainContent.innerHTML;

            // Update title
            const newTitle = doc.querySelector('title');
            if (newTitle) {
                document.title = newTitle.textContent;
            }

            // Update active states in sidebar
            this.updateActiveStates(normalized.href);

            // Execute scripts contained in the swapped content
            this.executeScripts(newMainContent);

            // Update URL and history
            if (addToHistory) {
                history.pushState({ url: normalized.href }, '', normalized.href);
            }
            this.currentUrl = normalized.href;

            window.dispatchEvent(new CustomEvent('spa:navigated', {
                detail: { url: normalized.href }
            }));

            if (scrollTop) {
                window.scrollTo(0, 0);
            }
        } catch (error) {
            console.error('SPA Navigation Error:', error);
            window.location.href = url;
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

let spaNavigatorSingleton = null;

export function initSpaNavigation() {
    if (spaNavigatorSingleton) {
        // Ensure container reference stays valid
        spaNavigatorSingleton.mainContent = document.querySelector('#pjax-container') || document.querySelector('main.main');
        return spaNavigatorSingleton;
    }

    spaNavigatorSingleton = new SPANavigator();
    spaNavigatorSingleton.init();
    window.spaNavigator = spaNavigatorSingleton;
    return spaNavigatorSingleton;
}
