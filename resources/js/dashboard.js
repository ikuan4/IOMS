// resources/js/dashboard.js
// DASHBOARD PAGE JS (collapse/expand + theme toggle + JS-assisted hover push)
// Robust, idempotent and compatible with Vite HMR.

document.addEventListener("DOMContentLoaded", () => {

  // Contract Version actions (works with SPA navigation because it's delegated)
  if (!document.__contractVersionActionsBound) {
    document.addEventListener('click', (e) => {
      const deleteBtn = e.target.closest('button[data-contract-version-delete]');
      if (deleteBtn) {
        const versionId = deleteBtn.getAttribute('data-version-id');
        const versionNumber = deleteBtn.getAttribute('data-version-number') || '';
        const form = document.getElementById(`deleteVersionForm-${versionId}`);
        if (!form || typeof window.showConfirmModal !== 'function') return;

        window.showConfirmModal({
          type: 'delete',
          title: 'Delete Contract Version',
          subtitle: `Are you sure you want to delete ${versionNumber}?`,
          message: `This action will soft delete the contract version. The version will be moved to the deleted versions section and can be restored later if needed.\n\nNote: All files and data associated with this version will be preserved.`,
          confirmText: 'Delete Version',
          form,
        });
        return;
      }

      const restoreBtn = e.target.closest('button[data-contract-version-restore]');
      if (restoreBtn) {
        const versionId = restoreBtn.getAttribute('data-version-id');
        const versionNumber = restoreBtn.getAttribute('data-version-number') || '';
        const form = document.getElementById(`restoreVersionForm-${versionId}`);
        if (!form || typeof window.showConfirmModal !== 'function') return;

        window.showConfirmModal({
          type: 'restore',
          title: 'Restore Contract Version',
          subtitle: `Are you sure you want to restore ${versionNumber}?`,
          message: 'This action will restore the deleted version and make it active again. All associated files and data will be accessible.',
          confirmText: 'Restore Version',
          form,
        });
      }
    });
    document.__contractVersionActionsBound = true;
  }

  // FEATHER icons (safe)
  if (window.feather) {
    try { window.feather.replace(); } catch (e) {}
  }

  /* -----------------------------
     THEME TOGGLE (unchanged)
  ----------------------------- */
  const themeToggle = document.getElementById("themeToggle");
  const sun = document.getElementById("icon-sun");
  const moon = document.getElementById("icon-moon");

  function applyTheme(theme){
    if (!sun || !moon) {
      try { localStorage.setItem("cms-theme", theme); } catch(e) {}
      return;
    }
    if (theme === "dark") {
      document.documentElement.setAttribute("data-theme", "dark");
      sun.style.display = "none";
      moon.style.display = "block";
    } else {
      document.documentElement.removeAttribute("data-theme");
      sun.style.display = "block";
      moon.style.display = "none";
    }
    try { localStorage.setItem("cms-theme", theme); } catch(e) {}
  }

  const savedTheme = localStorage.getItem("cms-theme");
  if (savedTheme) applyTheme(savedTheme);
  else applyTheme(window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");

  if (themeToggle && !themeToggle.__bound) {
    themeToggle.addEventListener("click", () => {
      const isDark = document.documentElement.getAttribute("data-theme") === "dark";
      applyTheme(isDark ? "light" : "dark");
    });
    themeToggle.__bound = true;
  }

  /* -----------------------------
     SIDEBAR COLLAPSE / EXPAND (Option A)
     + JS-assisted hover push behavior
  ----------------------------- */
  const sidebar = document.getElementById("sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const appRoot = document.querySelector(".app");
  if (!sidebar || !appRoot) return; // no-op on pages without layout

  const STORAGE_KEY = "cms-sidebar-collapsed";
  let collapsed = localStorage.getItem(STORAGE_KEY) === "1";

  // Create mobile overlay
  let mobileOverlay = document.getElementById("sidebar-overlay");
  if (!mobileOverlay) {
    mobileOverlay = document.createElement("div");
    mobileOverlay.id = "sidebar-overlay";
    mobileOverlay.className = "sidebar-overlay";
    document.body.appendChild(mobileOverlay);
  }

  // Hover timers & state
  const HOVER_EXPAND_DELAY = 80;   // ms before expanding on hover
  const HOVER_COLLAPSE_DELAY = 140; // ms before collapsing after hover leaves
  let hoverExpandTimer = null;
  let hoverCollapseTimer = null;
  let isHoverExpanded = false; // whether temporary hover expansion is active

  // safely apply persistent collapsed classes (idempotent), with inline fallback if CSS not applied yet
  function applySidebarState() {
    if (collapsed) {
      sidebar.classList.add("collapsed");
      appRoot.classList.add("sidebar-collapsed");
      // fallback inline grid if CSS hasn't applied
      const grid = getComputedStyle(appRoot).display === "grid" || getComputedStyle(appRoot).gridTemplateColumns !== "none";
      if (!grid) appRoot.style.gridTemplateColumns = "70px 1fr";
      else appRoot.style.removeProperty("grid-template-columns");
    } else {
      // If not collapsed we must ensure any hover temporary classes are removed
      sidebar.classList.remove("collapsed");
      sidebar.classList.remove("collapsed-focus");
      appRoot.classList.remove("sidebar-collapsed");
      appRoot.classList.remove("sidebar-hovered");
      isHoverExpanded = false;
      const grid = getComputedStyle(appRoot).display === "grid" || getComputedStyle(appRoot).gridTemplateColumns !== "none";
      if (!grid) appRoot.style.gridTemplateColumns = "260px 1fr";
      else appRoot.style.removeProperty("grid-template-columns");
    }
  }

  // wait for CSS injection (Vite) and then apply persisted state
  let tries = 0, maxTries = 20;
  function waitForCssThenApply() {
    tries++;
    const sidebarWidth = getComputedStyle(sidebar).width;
    const appDisplay = getComputedStyle(appRoot).display;
    const gridPresent = appDisplay === "grid" || getComputedStyle(appRoot).gridTemplateColumns !== "none";

    if ((sidebarWidth && sidebarWidth !== "0px") && gridPresent) {
      applySidebarState();
      attachToggle();
      attachHoverHandlers(); // attach hover handlers after we have a stable layout
    } else if (tries < maxTries) {
      setTimeout(waitForCssThenApply, 60);
    } else {
      // fallback: apply anyway so UI is usable
      applySidebarState();
      attachToggle();
      attachHoverHandlers();
    }
  }
  waitForCssThenApply();

  function attachToggle() {
    if (!sidebarToggle) return;
    if (sidebarToggle.__bound) return;
    sidebarToggle.addEventListener("click", () => {
      // Check if we're on mobile (screen width <= 720px)
      const isMobile = window.innerWidth <= 720;

      if (isMobile) {
        // On mobile, toggle the mobile-open class for overlay behavior
        const isOpening = !sidebar.classList.contains("mobile-open");
        sidebar.classList.toggle("mobile-open");

        // Toggle overlay
        if (mobileOverlay) {
          if (isOpening) {
            mobileOverlay.classList.add("active");
            document.body.style.overflow = "hidden"; // Prevent background scroll
          } else {
            mobileOverlay.classList.remove("active");
            document.body.style.overflow = ""; // Restore scroll
          }
        }
      } else {
        // On desktop, toggle persistent collapsed state
        collapsed = !collapsed;
        try { localStorage.setItem(STORAGE_KEY, collapsed ? "1" : "0"); } catch (e) {}
        // clear any hover timers / states when clicking
        clearHoverTimers();
        removeHoverExpansion();
        applySidebarState();
      }
    }, { passive: true });
    sidebarToggle.__bound = true;
  }

  // keyboard accessibility: toggle via Enter/Space if focused
  if (sidebarToggle) {
    sidebarToggle.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        sidebarToggle.click();
      }
    });
  }

  // Helper: add the temporary hover-expanded state
  function applyHoverExpansion() {
    if (!collapsed) return; // only act when collapsed
    if (isHoverExpanded) return;
    isHoverExpanded = true;
    // add a class on the app that makes grid columns expanded (CSS handles it)
    appRoot.classList.add("sidebar-hovered");
    sidebar.classList.add("hovered");
    // remove inline fallback grid if CSS is now present
    const grid = getComputedStyle(appRoot).display === "grid" || getComputedStyle(appRoot).gridTemplateColumns !== "none";
    if (grid) appRoot.style.removeProperty("grid-template-columns");
  }

  // Helper: remove the temporary hover-expanded state
  function removeHoverExpansion() {
    if (!isHoverExpanded) return;
    isHoverExpanded = false;
    appRoot.classList.remove("sidebar-hovered");
    sidebar.classList.remove("hovered");
    // restore collapsed layout if still collapsed
    if (collapsed) {
      // ensure the persistent collapsed classes remain
      sidebar.classList.add("collapsed");
      appRoot.classList.add("sidebar-collapsed");
      // ensure inline fallback if necessary
      const grid = getComputedStyle(appRoot).display === "grid" || getComputedStyle(appRoot).gridTemplateColumns !== "none";
      if (!grid) appRoot.style.gridTemplateColumns = "70px 1fr";
    }
  }

  function clearHoverTimers() {
    if (hoverExpandTimer) { clearTimeout(hoverExpandTimer); hoverExpandTimer = null; }
    if (hoverCollapseTimer) { clearTimeout(hoverCollapseTimer); hoverCollapseTimer = null; }
  }

  // Attach hover handlers (idempotent)
  function attachHoverHandlers() {
    if (sidebar.__hoverHandlersAttached) return;
    // mouseenter -> delay then expand
    sidebar.addEventListener("mouseenter", () => {
      // do nothing if the sidebar is not collapsed (persistently expanded)
      if (!collapsed) return;
      // clear any collapse timer
      if (hoverCollapseTimer) { clearTimeout(hoverCollapseTimer); hoverCollapseTimer = null; }
      // schedule expand
      hoverExpandTimer = setTimeout(() => {
        applyHoverExpansion();
        hoverExpandTimer = null;
      }, HOVER_EXPAND_DELAY);
    }, { passive: true });

    // mouseleave -> delay then collapse
    sidebar.addEventListener("mouseleave", () => {
      if (!collapsed) return;
      // clear any pending expand timer
      if (hoverExpandTimer) { clearTimeout(hoverExpandTimer); hoverExpandTimer = null; }
      // schedule collapse
      hoverCollapseTimer = setTimeout(() => {
        removeHoverExpansion();
        hoverCollapseTimer = null;
      }, HOVER_COLLAPSE_DELAY);
    }, { passive: true });

    // keyboard focus: expand immediately on focus, collapse on blur (for accessibility)
    sidebar.addEventListener("focusin", () => {
      if (!collapsed) return;
      // cancel collapse timer and expand immediately
      if (hoverCollapseTimer) { clearTimeout(hoverCollapseTimer); hoverCollapseTimer = null; }
      if (hoverExpandTimer) { clearTimeout(hoverExpandTimer); hoverExpandTimer = null; }
      applyHoverExpansion();
    });

    sidebar.addEventListener("focusout", () => {
      if (!collapsed) return;
      // collapse after a short delay
      if (hoverExpandTimer) { clearTimeout(hoverExpandTimer); hoverExpandTimer = null; }
      hoverCollapseTimer = setTimeout(() => {
        removeHoverExpansion();
        hoverCollapseTimer = null;
      }, HOVER_COLLAPSE_DELAY);
    });

    sidebar.__hoverHandlersAttached = true;
  }

  // Mobile sidebar: close when clicking outside or on overlay
  function handleMobileClose(e) {
    const isMobile = window.innerWidth <= 720;
    if (!isMobile) return;

    if (sidebar.classList.contains("mobile-open") &&
        (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target))) {
      sidebar.classList.remove("mobile-open");
      if (mobileOverlay) {
        mobileOverlay.classList.remove("active");
        document.body.style.overflow = ""; // Restore scroll
      }
    }
  }

  document.addEventListener("click", handleMobileClose);

  // Close sidebar when clicking overlay
  if (mobileOverlay) {
    mobileOverlay.addEventListener("click", () => {
      sidebar.classList.remove("mobile-open");
      mobileOverlay.classList.remove("active");
      document.body.style.overflow = ""; // Restore scroll
    });
  }

  // keep CSS-driven focus class as well (existing)
  sidebar.addEventListener("focusin", () => {
    if (sidebar.classList.contains("collapsed")) sidebar.classList.add("collapsed-focus");
  });
  sidebar.addEventListener("focusout", () => {
    sidebar.classList.remove("collapsed-focus");
  });

    /* -----------------------------
     Collapsible "User Management" group
  ----------------------------- */
  const userMgmtGroup = document.getElementById("nav-user-mgmt");
  const userMgmtToggle = document.getElementById("nav-user-mgmt-toggle");
  const userMgmtSubmenu = document.getElementById("nav-user-mgmt-submenu");

  if (userMgmtGroup && userMgmtToggle && userMgmtSubmenu) {
    // sync initial aria-expanded with server-rendered "open" class
    const initiallyOpen = userMgmtGroup.classList.contains("open");
    userMgmtToggle.setAttribute("aria-expanded", initiallyOpen ? "true" : "false");

    userMgmtToggle.addEventListener("click", () => {
      const isOpen = userMgmtGroup.classList.toggle("open");
      userMgmtToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  /* -----------------------------
     Collapsible "Contract Management" group
  ----------------------------- */
  const contractMgmtGroup = document.getElementById("nav-contract-mgmt");
  const contractMgmtToggle = document.getElementById("nav-contract-mgmt-toggle");
  const contractMgmtSubmenu = document.getElementById("nav-contract-mgmt-submenu");

  if (contractMgmtGroup && contractMgmtToggle && contractMgmtSubmenu) {
    // sync initial aria-expanded with server-rendered "open" class
    const initiallyOpenContract = contractMgmtGroup.classList.contains("open");
    contractMgmtToggle.setAttribute("aria-expanded", initiallyOpenContract ? "true" : "false");

    contractMgmtToggle.addEventListener("click", () => {
      const isOpen = contractMgmtGroup.classList.toggle("open");
      contractMgmtToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  /* -----------------------------
     Collapsible "Notification Management" group
  ----------------------------- */
  const notificationMgmtGroup = document.getElementById("nav-notification-mgmt");
  const notificationMgmtToggle = document.getElementById("nav-notification-mgmt-toggle");
  const notificationMgmtSubmenu = document.getElementById("nav-notification-mgmt-submenu");

  if (notificationMgmtGroup && notificationMgmtToggle && notificationMgmtSubmenu) {
    // sync initial aria-expanded with server-rendered "open" class
    const initiallyOpenNotification = notificationMgmtGroup.classList.contains("open");
    notificationMgmtToggle.setAttribute("aria-expanded", initiallyOpenNotification ? "true" : "false");

    notificationMgmtToggle.addEventListener("click", () => {
      const isOpen = notificationMgmtGroup.classList.toggle("open");
      notificationMgmtToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  /* -----------------------------
     NOTIFICATION TOAST AUTO-FADE
  ----------------------------- */
  const notificationToast = document.getElementById("notification-toast");

  if (notificationToast) {
    // Initialize feather icons in the notification
    if (window.feather) {
      try { window.feather.replace(); } catch (e) {}
    }

    // Auto-hide after 5 seconds
    setTimeout(() => {
      notificationToast.classList.add("hiding");
      setTimeout(() => {
        notificationToast.remove();
      }, 300); // Wait for animation to complete
    }, 5000);
  }

});

// Global function to close notification manually
window.closeNotification = function() {
  const notificationToast = document.getElementById("notification-toast");
  if (notificationToast) {
    notificationToast.classList.add("hiding");
    setTimeout(() => {
      notificationToast.remove();
    }, 300);
  }
};
