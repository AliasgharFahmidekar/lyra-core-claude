/*
 * Frontend behaviour. Rules: no inline scripts, use event delegation so the
 * code also works for widgets rendered later (Elementor editor, AJAX).
 * Example: any element with data-lyra-toggle="#target" toggles .is-open on the target.
 */
(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-lyra-toggle]');
		if (!trigger) {
			return;
		}
		var target = document.querySelector(trigger.getAttribute('data-lyra-toggle'));
		if (target) {
			var open = target.classList.toggle('is-open');
			trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	});
})();
