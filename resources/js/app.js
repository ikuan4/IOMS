import './bootstrap';

function initHeader() {
	const toggle = document.getElementById('userMenuToggle');
	const dropdown = document.getElementById('userMenuDropdown');

	if (!toggle || !dropdown) return;
	if (toggle.__bound) return;

	toggle.addEventListener('click', function (e) {
		e.stopPropagation();
		const isOpen = dropdown.classList.toggle('open');
		toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	});

	// Close when clicking outside
	document.addEventListener('click', function (e) {
		if (!dropdown.classList.contains('open')) return;

		// ignore clicks inside dropdown/toggle
		if (dropdown.contains(e.target) || toggle.contains(e.target)) return;

		dropdown.classList.remove('open');
		toggle.setAttribute('aria-expanded', 'false');
	});

	toggle.__bound = true;
}

function initFeather() {
	if (!window.feather) return;
	try { window.feather.replace(); } catch (e) { /* noop */ }
}

// Global UI initializer (called on first load and after PJAX/SPA navigations)
window.initAppUI = function () {
	initHeader();
	initFeather();

	if (typeof window.initDashboardUI === 'function') {
		window.initDashboardUI();
	}
};

document.addEventListener('DOMContentLoaded', () => {
	if (typeof window.initAppUI === 'function') {
		window.initAppUI();
	}
});

// Custom SPA system used in this codebase
window.addEventListener('spa:navigated', () => {
	if (typeof window.initAppUI === 'function') {
		window.initAppUI();
	}
});

// Compatibility hooks if PJAX is used elsewhere
document.addEventListener('pjax:complete', () => {
	if (typeof window.initAppUI === 'function') {
		window.initAppUI();
	}
});

if (window.jQuery) {
	try {
		window.jQuery(document).on('pjax:end', function () {
			if (typeof window.initAppUI === 'function') {
				window.initAppUI();
			}
		});
	} catch (e) {
		// ignore if pjax isn't present
	}
}
