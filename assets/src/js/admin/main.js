/**
 * Plausible Analytics
 *
 * Admin JS
 */

document.addEventListener('DOMContentLoaded', (e) => {
	if (!document.location.href.includes('plausible_analytics')) {
		return;
	}

	if (document.location.hash === '' && document.getElementById('plausible-analytics-wizard') !== null) {
		document.location.hash = 'welcome';
	}

	let hash = document.location.hash.replace('#', '');

	plausibleToggleWizardStep(document.getElementById('step-' + hash));

	/**
	 * Toggle bold state for wizard menu item.
	 */
	window.addEventListener('hashchange', (e) => {
		let hash = document.location.hash.replace('#', '');
		let step = document.getElementById('step-' + hash);

		plausibleToggleWizardStep(step);
	});

	document.addEventListener('click', (e) => {
		if (e.target.id !== 'plausible-analytics-wizard-quit') {
			return;
		}

		const form = new FormData();

		form.append('action', 'plausible_analytics_quit_wizard');
		form.append('_nonce', e.target.dataset.nonce);

		fetch(
			ajaxurl,
			{
				method: 'POST',
				body: form,
			}
		).then(response => {
			window.location.reload();
		});
	});

	/**
	 * Save Options.
	 */
	document.addEventListener('click', (e) => {
		if (!e.target.classList.contains('plausible-analytics-button')) {
			return;
		}

		const button = e.target;
		const section = button.closest('.plausible-analytics-section');
		const inputs = section.querySelectorAll('input, textarea');
		const form = new FormData();
		const options = [];

		inputs.forEach(function (input) {
			options.push({name: input.name, value: input.value});
		});

		form.append('action', 'plausible_analytics_save_options');
		form.append('options', JSON.stringify(options));
		form.append('_nonce', document.getElementById('_wpnonce').value);

		button.children[0].classList.remove('hidden');
		button.setAttribute('disabled', 'disabled');

		fetch(
			ajaxurl,
			{
				method: 'POST',
				body: form,
			}
		).then(response => {
			button.children[0].classList += ' hidden';
			button.removeAttribute('disabled');

			if (response.status === 200) {
				return response.json();
			}

			return false;
		}).then(response => {
			plausibleShowNotice(response.data);

			return response.success;
		})
	});
});

/**
 * Show notice.
 *
 * @param message
 * @param isError
 */
function plausibleShowNotice(message, isError = false) {
	if (isError === true) {
		document.getElementById('icon-error').classList.remove('hidden');
		document.getElementById('icon-success').classList += ' hidden';
	} else {
		document.getElementById('icon-success').classList.remove('hidden');
		document.getElementById('icon-error').classList += ' hidden';
	}

	document.getElementById('plausible-analytics-notice-text').innerHTML = message;
	document.getElementById('plausible-analytics-notice').classList.replace('opacity-0', 'opacity-100');

	if (isError === false) {
		setTimeout(function () {
			document.getElementById('plausible-analytics-notice').classList.replace('opacity-100', 'opacity-0');
		}, 2500);
	}
}

/**
 * Toggle Options.
 */
document.addEventListener('click', (e) => {
	if (!e.target.classList.contains('plausible-analytics-toggle')) {
		return;
	}

	const button = e.target.closest('button');
	let toggle = '';
	let toggleStatus = '';

	// The button element is clicked.
	if (e.target.type === 'submit') {
		toggle = button.querySelector('span');
	} else {
		// The span element is clicked.
		toggle = e.target.closest('span');
	}

	if (button.classList.contains('bg-indigo-600')) {
		// Toggle: off
		button.classList.replace('bg-indigo-600', 'bg-gray-200');
		toggle.classList.replace('translate-x-5', 'translate-x-0');
		toggleStatus = '';
	} else {
		// Toggle: on
		button.classList.replace('bg-gray-200', 'bg-indigo-600');
		toggle.classList.replace('translate-x-0', 'translate-x-5');
		toggleStatus = 'on';
	}

	const form = new FormData();
	form.append('action', 'plausible_analytics_toggle_option');
	form.append('option_name', button.name);
	form.append('option_value', button.value);
	form.append('option_label', button.nextElementSibling.innerHTML);
	form.append('toggle_status', toggleStatus);
	form.append('is_list', button.dataset.list);
	form.append('_nonce', document.getElementById('_wpnonce').value);

	fetch(
		ajaxurl,
		{
			method: 'POST',
			body: form,
		}
	).then(response => {
		if (response.status === 200) {
			return response.json();
		}

		return false;
	}).then(response => {
		if (response.success === true) {
			plausibleShowNotice(response.data);
		} else {
			plausibleShowNotice(response.data, true);
		}

		return response.success;
	});
});

/**
 * Toggles the font-weight of the wizard's steps.
 * @param target
 */
function plausibleToggleWizardStep(target) {
	if (target.classList === undefined || !target.classList.contains('plausible-analytics-wizard-step')) {
		return;
	}

	let steps = document.querySelectorAll('.plausible-analytics-wizard-step');

	steps.forEach(function (step) {
		step.classList.remove('font-bold');
	});

	target.classList += ' font-bold';
}
