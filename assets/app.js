import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Global sidebar toggle (centralized)
document.addEventListener('DOMContentLoaded', () => {
	const sidebar = document.getElementById('globalSidebar');
	const btn = document.getElementById('acSidebarToggle');
	if (!sidebar || !btn) return;

	function syncState() {
		const open = sidebar.classList.contains('open');
		btn.innerHTML = open ? '<i class="bi bi-chevron-left"></i>' : '<i class="bi bi-chevron-right"></i>';
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
	}

	btn.addEventListener('click', () => {
		sidebar.classList.toggle('open');
		syncState();
	});

	btn.addEventListener('keydown', (e) => {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			btn.click();
		}
	});

	syncState();
});
