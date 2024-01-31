/**
 * Plausible Analytics
 *
 * Admin JS
 */
document.addEventListener('DOMContentLoaded', () => {
	if (!document.location.href.includes('plausible_analytics')) {
		return;
	}

	let plausible = {
		/**
		 * Properties
		 */
		showWizardElem: document.getElementById('show_wizard'),
		createAPITokenElem: document.getElementById('plausible-create-api-token'),
		domainNameElem: document.getElementById('domain_name'),
		apiTokenElem: document.getElementById('api_token'),
		buttonElems: document.getElementsByClassName('plausible-analytics-button'),
		stepElems: document.getElementsByClassName('plausible-analytics-wizard-next-step'),
		quitWizardElems: document.getElementsByClassName('plausible-analytics-wizard-quit'),
		nonceElem: document.getElementById('_wpnonce'),
		nonce: '',

		/**
		 * Bind events.
		 */
		init: function () {
			if (document.location.hash === '' && document.getElementById('plausible-analytics-wizard') !== null) {
				document.location.hash = '#welcome_slide';
			}

			if (this.nonceElem !== null) {
				this.nonce = this.nonceElem.value;
			}

			this.toggleWizardStep();

			window.addEventListener('hashchange', this.toggleWizardStep);

			if (this.showWizardElem !== null) {
				this.showWizardElem.addEventListener('click', this.showWizard);
			}

			if (this.createAPITokenElem !== null) {
				this.createAPITokenElem.addEventListener('click', this.createAPIToken);
			}

			if (this.domainNameElem !== null) {
				this.domainNameElem.addEventListener('change', this.disableConnectButton);
			}

			if (this.apiTokenElem !== null) {
				this.apiTokenElem.addEventListener('change', this.disableConnectButton);
			}

			if (this.buttonElems.length > 0) {
				for (let i = 0; i < this.buttonElems.length; i++) {
					this.buttonElems[i].addEventListener('click', this.saveOption);
				}
			}

			/**
			 * Due to the structure of the toggles, any events bound to them would be triggered twice, that's why we bind it to the documents' click'
			 * event.
			 */
			document.addEventListener('click', this.toggleOption);

			if (this.stepElems.length > 0) {
				for (let i = 0; i < this.stepElems.length; i++) {
					this.stepElems[i].addEventListener('click', this.saveOptionOnNext);
				}
			}

			if (this.quitWizardElems.length > 0) {
				for (let i = 0; i < this.quitWizardElems.length; i++) {
					this.quitWizardElems[i].addEventListener('click', this.quitWizard);
				}
			}
		},

		/**
		 * Toggle Option and store in DB.
		 *
		 * @param e
		 */
		toggleOption: function (e, showNotice = true) {
			/**
			 * Make sure event target is a toggle.
			 */
			if (e.target.classList === null || !e.target.classList.contains('plausible-analytics-toggle')) {
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
			form.append('_nonce', plausible.nonce);

			let reload = false;

			/**
			 * When either of these options are enabled, we need the page to reload, to display notices/warnings.
			 */
			if (button.name === 'proxy_enabled' || button.name === 'enable_analytics_dashboard') {
				reload = true;
				showNotice = false;
			}

			let result = plausible.ajax(form, null, showNotice, reload);

			result.then(function (success) {
				if (success === false) {
					plausible.toggleOption(e, false);
				}
			});
		},

		/**
		 * Save value of input or text area to DB.
		 *
		 * @param e
		 */
		saveOption: function (e) {
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
			form.append('_nonce', plausible.nonce);

			button.children[0].classList.remove('hidden');
			button.setAttribute('disabled', 'disabled');

			plausible.ajax(form, button);
		},

		/**
		 * Quit wizard.
		 *
		 * @param e
		 */
		quitWizard: function (e) {
			const form = new FormData();

			form.append('action', 'plausible_analytics_quit_wizard');
			form.append('_nonce', e.target.dataset.nonce);

			plausible.ajax(form, null, false, true);
		}
		,

		/**
		 * Save Options on Next click for API Token and Domain Name slides.
		 *
		 * @param e
		 */
		saveOptionOnNext: function (e) {
			let hash = document.location.hash.replace('#', '');

			if (hash === 'api_token_slide' || hash === 'domain_name_slide') {
				let form = e.target.closest('.plausible-analytics-wizard-step-section');
				let inputs = form.getElementsByTagName('INPUT');
				let options = [];

				for (let input of inputs) {
					options.push({name: input.name, value: input.value});
				}

				let data = new FormData();
				data.append('action', 'plausible_analytics_save_options');
				data.append('options', JSON.stringify(options));
				data.append('_nonce', plausible.nonce);

				plausible.ajax(data, null, false);
			}
		},

		/**
		 * Disable Connect button if Domain Name or API Token field is empty.
		 *
		 * @param e
		 */
		disableConnectButton: function (e) {
			let target = e.target;
			let button = document.getElementById('connect_plausible_analytics');
			let buttonIsHref = false;

			if (button === null) {
				let slide_id = document.location.hash;
				button = document.querySelector(slide_id + ' .plausible-analytics-wizard-next-step');
				buttonIsHref = true;
			}

			if (button === null) {
				return;
			}

			if (target.value !== '') {
				if (!buttonIsHref) {
					button.disabled = false;
				} else {
					button.classList.remove('pointer-events-none');
					button.classList.replace('bg-gray-200', 'bg-indigo-600')
				}

				return;
			}

			if (!buttonIsHref) {
				button.disabled = true;
			} else {
				button.classList += ' pointer-events-none';
				button.classList.replace('bg-indigo-600', 'bg-gray-200')
			}
		},

		/**
		 * Open create API token dialog.
		 *
		 * @param e
		 */
		createAPIToken: function (e) {
			e.preventDefault();

			let domain = document.getElementById('domain_name').value;

			window.open(`https://plausible.io/${domain}/settings/integrations?new_token=WordPress`, '_blank', 'location=yes,height=768,width=1024,scrollbars=yes,status=no');
		},

		/**
		 * Show wizard.
		 *
		 * @param e
		 */
		showWizard: function (e) {
			let data = new FormData();
			data.append('action', 'plausible_analytics_show_wizard');
			data.append('_nonce', e.target.dataset.nonce);

			plausible.ajax(data, null, false, true);
		},

		/**
		 * Toggles the active/inactive/current state of the steps.
		 */
		toggleWizardStep: function () {
			if (document.getElementById('plausible-analytics-wizard') === null) {
				return;
			}

			const hash = document.location.hash.substring(1).replace('_slide', '');

			/**
			 * Reset all steps to inactive.
			 */
			let allSteps = document.querySelectorAll('.plausible-analytics-wizard-step');
			let activeSteps = document.querySelectorAll('.plausible-analytics-wizard-active-step');
			let completedSteps = document.querySelectorAll('.plausible-analytics-wizard-completed-step');

			for (let i = 0; i < allSteps.length; i++) {
				allSteps[i].classList.remove('hidden');
			}

			for (let i = 0; i < activeSteps.length; i++) {
				activeSteps[i].classList += ' hidden';
			}

			for (let i = 0; i < completedSteps.length; i++) {
				completedSteps[i].classList += ' hidden';
			}

			/**
			 * Mark current step as active.
			 */
			let currentStep = document.getElementById('active-step-' + hash);
			let inactiveCurrentStep = document.getElementById('step-' + hash);

			currentStep.classList.remove('hidden');
			inactiveCurrentStep.classList += ' hidden';

			/**
			 * Mark steps as completed.
			 *
			 * @type {string[]}
			 */
			let currentlyCompletedSteps = currentStep.dataset.completedSteps.split(',');

			/**
			 * Filter empty array elements.
			 * @type {string[]}
			 */
			currentlyCompletedSteps = currentlyCompletedSteps.filter(n => n);

			if (currentlyCompletedSteps.length < 1) {
				return;
			}

			currentlyCompletedSteps.forEach(function (step) {
				let completedStep = document.getElementById('completed-step-' + step);
				let inactiveStep = document.getElementById('step-' + step);

				completedStep.classList.remove('hidden');
				inactiveStep.classList += ' hidden';
			});
		},

		/**
		 * Do AJAX request and (optionally) show a notice or (optionally) reload the page.
		 *
		 * @param data
		 * @param button
		 * @param showNotice
		 * @param reload
		 *
		 * @return object
		 */
		ajax: function (data, button = null, showNotice = true, reload = false) {
			return fetch(
				ajaxurl,
				{
					method: 'POST',
					body: data,
				}
			).then(response => {
				if (button) {
					button.children[0].classList += ' hidden';
					button.removeAttribute('disabled');
				}

				if (response.status === 200) {
					return response.json();
				}

				return false;
			}).then(response => {
				if (showNotice === true) {
					if (response.success === true) {
						plausible.notice(response.data);
					} else {
						plausible.notice(response.data, true);
					}
				}

				let event = new CustomEvent('plausibleAjaxDone', {detail: response});

				document.dispatchEvent(event);

				if (reload === true) {
					window.location.reload();
				}

				return response.success;
			});
		},

		/**
		 * Displays a notice or error message.
		 *
		 * @param message
		 * @param isError
		 */
		notice: function (message, isError = false) {
			if (isError === true) {
				document.getElementById('icon-error').classList.remove('hidden');
				document.getElementById('icon-success').classList += ' hidden';
			} else {
				document.getElementById('icon-success').classList.remove('hidden');
				document.getElementById('icon-error').classList += ' hidden';
			}

			let notice = document.getElementById('plausible-analytics-notice');

			document.getElementById('plausible-analytics-notice-text').innerHTML = message;

			notice.classList.remove('hidden');

			setTimeout(function () {
				notice.classList.replace('opacity-0', 'opacity-100');
			}, 200)

			if (isError === false) {
				setTimeout(function () {
					notice.classList.replace('opacity-100', 'opacity-0');
					setTimeout(function () {
						notice.classList += ' hidden';
					}, 200)
				}, 2000);
			}
		}
	}

	plausible.init();
});
