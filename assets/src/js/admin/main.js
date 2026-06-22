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
		nonceElem: document.getElementById('_wpnonce'),
		nonce: '',
		showWizardElem: document.getElementById('show_wizard'),
		domainNameElem: document.getElementById('domain_name'),
		apiTokenElem: document.getElementById('api_token'),
		createAPITokenElems: document.getElementsByClassName('plausible-create-api-token'),
		buttonElems: document.getElementsByClassName('plausible-analytics-button'),
		stepElems: document.getElementsByClassName('plausible-analytics-wizard-next-step'),
		multilangPairInputs: document.querySelectorAll('.multilang-domain-pair input'),
		multilangSelector: document.getElementById('multilang_domain_selector'),
		multilangConnectButtons: document.querySelectorAll('.multilang-domain-pair .plausible-analytics-connect-button'),

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

			if (this.domainNameElem !== null) {
				this.domainNameElem.addEventListener('keyup', this.disableConnectButton);
			}

			if (this.apiTokenElem !== null) {
				this.apiTokenElem.addEventListener('keyup', this.disableConnectButton);
			}

			if (this.createAPITokenElems.length > 0) {
				for (let i = 0; i < this.createAPITokenElems.length; i++) {
					this.createAPITokenElems[i].addEventListener('click', this.createAPIToken);
				}
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
			/**
			 * Select All/None toggles.
			 */
			document.addEventListener('click', this.bulkToggle);

			if (this.stepElems.length > 0) {
				for (let i = 0; i < this.stepElems.length; i++) {
					this.stepElems[i].addEventListener('click', this.saveOptionOnNext);
				}
			}

			if (this.multilangPairInputs.length > 0) {
				for (let i = 0; i < this.multilangPairInputs.length; i++) {
					this.multilangPairInputs[i].addEventListener('keyup', this.disableConnectButton);
				}
			}

			if (this.multilangSelector !== null) {
				this.multilangSelector.addEventListener('change', this.switchMultilangDomain);
			}

			if (this.multilangConnectButtons.length > 0) {
				for (let i = 0; i < this.multilangConnectButtons.length; i++) {
					this.multilangConnectButtons[i].addEventListener('click', this.saveMultilangOption);
				}
			}

			/**
			 * Run once on pageload.
			 */
			this.showMessages();
		},

		/**
		 * Toggle Option and store in DB.
		 *
		 * @param e
		 */
		toggleOption: async function (e) {
			/**
			 * Make sure the event target is a toggle.
			 */
			if (e.target.classList === null || !e.target.classList.contains('plausible-analytics-toggle')) {
				return;
			}

			const button = e.target.closest('button');
			let toggle;

			// The button element is clicked.
			if (e.target.type === 'submit') {
				toggle = button.querySelector('span');
			} else {
				// The span element is clicked.
				toggle = e.target.closest('span');
			}

			let toggleStatus;

			if (button.classList.contains('bg-indigo-600')) {
				// Toggle: off
				button.classList.replace('bg-indigo-600', 'bg-gray-200');
				toggle.classList.replace('translate-x-5', 'translate-x-0');
				button.dataset.status = 'off';
				toggleStatus = '';
			} else {
				// Toggle: on
				button.classList.replace('bg-gray-200', 'bg-indigo-600');
				toggle.classList.replace('translate-x-0', 'translate-x-5');
				button.dataset.status = 'on';
				toggleStatus = 'on';
			}

			if (button.dataset.addtlOpts === '1') {
				plausible.toggleSection(button.value.replace('-', '_'));
			}

			const container = button.closest('.plausible-analytics-section');
			plausible.syncBulkToggle(container);

			const form = new FormData();
			form.append('action', 'plausible_analytics_toggle_option');
			form.append('option_name', button.name);
			form.append('option_value', button.value);
			form.append('option_label', button.nextElementSibling.innerHTML);
			form.append('toggle_status', toggleStatus);
			form.append('is_list', button.dataset.list);
			form.append('_nonce', plausible.nonce);

			let data = await plausible.ajax(form);

			if (data.capabilities === undefined) {
				return;
			}

			plausible.maybeDisableOptions(data.capabilities);
		},

		/**
		 * Toggles all underlying toggles when the Select All/None toggle is clicked.
		 *
		 * @param e
		 * @returns {Promise<void>}
		 */
		bulkToggle: async function (e) {
			/**
			 * Make sure the event target is a bulk toggle.
			 */
			if (e.target.classList === null || !e.target.classList.contains('plausible-analytics-bulk-toggle')) {
				return;
			}

			const button = e.target.closest('button');
			const checked = button.dataset.status !== 'on';
			const container = button.closest('.plausible-analytics-section');
			const toggles = container.querySelectorAll('button.plausible-analytics-toggle');
			const options = [];

			/**
			 * Trigger animations for each toggle.
			 */
			toggles.forEach(function (toggle) {
				const span = toggle.querySelector('span');

				if (checked) {
					toggle.classList.replace('bg-gray-200', 'bg-indigo-600');
					span.classList.replace('translate-x-0', 'translate-x-5');
					toggle.dataset.status = 'on';
				} else {
					toggle.classList.replace('bg-indigo-600', 'bg-gray-200');
					span.classList.replace('translate-x-5', 'translate-x-0');
					toggle.dataset.status = 'off';
				}

				options.push({
					name: toggle.name,
					value: toggle.value,
					status: checked ? 'on' : '',
				});
			});

			/**
			 * Toggle collapsable sections.
			 */
			toggles.forEach(function (toggle) {
				if (toggle.dataset.addtlOpts !== '1') {
					return;
				}

				const sectionName = toggle.value.replace('-', '_');
				const section = document.getElementById(sectionName + '_content');

				if (section === null) {
					return;
				}

				const isHidden = section.classList.contains('hidden');

				if (checked && isHidden) {
					plausible.toggleSection(sectionName);
				} else if (!checked && !isHidden) {
					plausible.toggleSection(sectionName);
				}
			});

			button.dataset.status = checked ? 'on' : 'off';

			const bulkSpan = button.querySelector('span');

			if (checked) {
				button.classList.replace('bg-gray-200', 'bg-indigo-600');
				bulkSpan.classList.replace('translate-x-0', 'translate-x-5');
			} else {
				button.classList.replace('bg-indigo-600', 'bg-gray-200');
				bulkSpan.classList.replace('translate-x-5', 'translate-x-0');
			}

			const form = new FormData();
			form.append('action', 'plausible_analytics_bulk_toggle');
			form.append('options', JSON.stringify(options));
			form.append('_nonce', plausible.nonce);

			await plausible.ajax(form);
		},

		/**
		 * Sets the initial state of the Select All toggle.
		 *
		 * @param container
		 */
		syncBulkToggle: function (container) {
			const bulkToggle = container.querySelector('.plausible-analytics-bulk-toggle');

			if (bulkToggle === null) {
				return;
			}

			const toggles = container.querySelectorAll('button.plausible-analytics-toggle');
			const allOn = Array.from(toggles).every(t => t.dataset.status === 'on');
			const bulkSpan = bulkToggle.querySelector('span');

			if (allOn) {
				bulkToggle.classList.replace('bg-gray-200', 'bg-indigo-600');
				bulkSpan.classList.replace('translate-x-0', 'translate-x-5');
				bulkToggle.dataset.status = 'on';
			} else {
				bulkToggle.classList.replace('bg-indigo-600', 'bg-gray-200');
				bulkSpan.classList.replace('translate-x-5', 'translate-x-0');
				bulkToggle.dataset.status = 'off';
			}
		},

		/**
		 * Adds an input node.
		 *
		 * @param target
		 */
		addField: function (target) {
			let clone = document.getElementsByClassName(target.replace('_', '-') + '-field')[0].cloneNode(true);
			let rows = document.getElementsByClassName(target.replace('_', '-') + '-field');
			let current_row = rows.length;
			let input = clone.querySelector('input');
			let trash = clone.querySelector('a');

			input.value = '';
			input.setAttribute('id', target + '[' + current_row + ']');
			input.setAttribute('name', target + '[' + current_row + ']');
			trash.setAttribute('onclick', 'plausibleRemoveField("' + target + '[' + current_row + ']")');
			trash.classList.remove('hidden');

			document.getElementById(target + '_list').appendChild(clone);
		},

		/**
		 * Removes an input node.
		 *
		 * @param target
		 */
		removeField: function (target) {
			let rowClass = target.replace(/\[[0-9]+]/, '').replace('_', '-');
			let rows = document.getElementsByClassName(rowClass + '-field');
			let input = document.getElementById(target);
			let listItem = input.closest('.' + rowClass + '-field');

			listItem.remove();

			plausible.resetListItems(rows, target.replace(/\[[0-9]+]/, ''));
		},

		/**
		 * Make sure all items in a list have properly incremented id, name and onclick attributes.
		 *
		 * @param listItems
		 * @param list
		 */
		resetListItems: function (listItems, list) {
			if (listItems === null || listItems === undefined || listItems.length === 0) {
				return;
			}

			for (let i = 0; i < listItems.length; i++) {
				let item = listItems[i];
				let input = item.querySelector('input');
				let trash = item.querySelector('a');

				input.setAttribute('id', list + '[' + i + ']');
				input.setAttribute('name', list + '[' + i + ']');
				trash.setAttribute('onclick', 'plausibleRemoveField("' + list + '[' + i + ']")');
			}
		},

		/**
		 * Toggles a collapsable section and rotates a chevron if it exists.
		 *
		 * @param target
		 */
		toggleSection: function (target) {
			let section = document.getElementById(target + '_content');
			let chevron = document.getElementById(target + '_chevron');

			if (section.className.indexOf('hidden') !== -1) {
				section.classList.add('block');
				section.classList.remove('hidden');

				if (chevron !== null) {
					chevron.classList.add('rotate-180');
				}
			} else {
				section.classList.add('hidden');
				section.classList.remove('block');

				if (chevron !== null) {
					chevron.classList.remove('rotate-180');
				}
			}
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
			let options = [];

			inputs.forEach(function (input) {
				input = plausible.validateInput(input);

				options.push({name: input.name, value: input.value});
			});

			form.append('action', 'plausible_analytics_save_options');
			form.append('options', JSON.stringify(options));
			form.append('_nonce', plausible.nonce);

			if (button.children.length > 0) {
				button.children[0].classList.remove('hidden');
			}

			button.setAttribute('disabled', 'disabled');

			plausible.ajax(form, button);
		},

		/**
		 * Switch visible multilang domain pair.
		 *
		 * @param e
		 */
		switchMultilangDomain: function (e) {
			const selectedDomain = e.target.value;
			const pairs = document.querySelectorAll('.multilang-domain-pair');

			pairs.forEach(function (pair) {
				if (pair.dataset.wpmlDomain === selectedDomain) {
					pair.classList.remove('hidden');
				} else {
					pair.classList.add('hidden');
				}
			});
		},

		/**
		 * Save multilang option (Domain Name + Plugin Token pair).
		 *
		 * @param e
		 */
		saveMultilangOption: function (e) {
			e.preventDefault();

			const button = e.target.closest('button');
			const pair = button.closest('.multilang-domain-pair');
			const inputs = pair.querySelectorAll('input');
			const form = new FormData();
			let options = [];

			inputs.forEach(function (input) {
				input = plausible.validateInput(input);

				options.push({name: input.name, value: input.value});
			});

			form.append('action', 'plausible_analytics_save_options');
			form.append('options', JSON.stringify(options));
			form.append('_nonce', plausible.nonce);

			if (button.children.length > 1) {
				button.children[1].classList.remove('hidden');
			}

			button.setAttribute('disabled', 'disabled');

			plausible.ajax(form, button);
		},

		/**
		 * Disable options based on the capabilities retrieved from the API.
		 *
		 * @param capabilities
		 */
		maybeDisableOptions: function (capabilities) {
			let options = document.querySelectorAll('button[data-caps]');

			options.forEach(function (option) {
				let caps = option.dataset.caps.split(',');
				let disable = false;

				caps.forEach(function (cap) {
					if (capabilities[cap] === false) {
						disable = true;
					}
				});

				if (disable === true) {
					// Trigger a click to make sure the option is disabled.
					if (option.dataset.status === 'on') {
						option.dispatchEvent(new Event('click', {bubbles: true}));
					}
				}
			});
		},

		/**
		 * Currently only validates the domain_name input, but can be used in the future for other custom input validations.
		 *
		 * @param input
		 * @returns {*}
		 */
		validateInput: function (input) {
			// Strip http(s)://(www.) from domain_name before sending it.
			if ((input.name === 'domain_name' || input.name.startsWith('domain_name[')) && input.value.match(/^(https?:\/\/)?(www.)?/).length > 0) {
				input.value = input.value.replace(/^(https?:\/\/)?(www.)?/, '');
			}

			return input;
		},

		/**
		 * Save Options on Next click for API Token and Domain Name slides.
		 *
		 * @param e
		 */
		saveOptionOnNext: function (e) {
			let hash = document.location.hash.replace('#', '');

			if (hash !== 'api_token_slide' && hash !== 'domain_name_slide') {
				return;
			}

			let form = e.target.closest('.plausible-analytics-wizard-step-section');
			let inputs = form.getElementsByTagName('INPUT');
			let options = [];

			for (let input of inputs) {
				input = plausible.validateInput(input);

				options.push({name: input.name, value: input.value});
			}

			let data = new FormData();

			data.append('action', 'plausible_analytics_save_options');
			data.append('options', JSON.stringify(options));
			data.append('_nonce', plausible.nonce);

			plausible.ajax(data).then(response => {
				/**
				 * Disable View Stats button, if API token is entered and valid.
				 */
				if (hash === 'api_token_slide' && response.success === true) {
					let stats_button = document.getElementById('enable_analytics_dashboard_view_stats_in_wordpress');

					stats_button.removeAttribute('disabled');
				}
			});
		},

		/**
		 * Disable the Connect button if the Domain Name or API Token field is empty.
		 *
		 * @param e
		 */
		disableConnectButton: function (e) {
			let target = e.target;
			let pair = target.closest('.multilang-domain-pair');
			let button;
			let buttonIsHref = false;

			if (pair !== null) {
				button = pair.querySelector('.plausible-analytics-connect-button');
				let allFilled = Array.from(pair.querySelectorAll('input')).every(input => input.value.trim() !== '');

				if (button === null) {
					return;
				}

				button.disabled = !allFilled;

				return;
			}

			button = document.getElementById('connect_plausible_analytics');

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
					button.classList.replace('bg-gray-200', 'bg-indigo-600');
				}

				return;
			}

			if (!buttonIsHref) {
				button.disabled = true;
				button.innerHTML = button.innerHTML.replace('Connected', 'Connect');
			} else {
				button.classList += ' pointer-events-none';
				button.classList.replace('bg-indigo-600', 'bg-gray-200');
			}
		},

		/**
		 * Open create API token dialog.
		 *
		 * @param e
		 */
		createAPIToken: function (e) {
			e.preventDefault();

			let domainElem = document.querySelector('.multilang-domain-pair:not(.hidden) [id^="domain_name"]');

			if (domainElem === null) {
				domainElem = document.getElementById('domain_name');
			}

			let domain = domainElem ? domainElem.value : '';
			domain = domain.replaceAll('/', '%2F');

			window.open(`${plausible_analytics_hosted_domain}/${domain}/settings/integrations?new_token=WordPress`, '_blank', 'location=yes,height=768,width=1024,scrollbars=yes,status=no');
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

			plausible.ajax(data);
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
		 * Do AJAX request.
		 *
		 * @param data
		 * @param button
		 * @param showMessages
		 *
		 * @return object
		 */
		ajax: function (data, button = null, showMessages = true) {
			return fetch(
				ajaxurl,
				{
					method: 'POST',
					body: data,
				}
			).then(response => {
				if (button) {
					if (button.children.length > 0) {
						let spinner = button.querySelector('svg');
						if (spinner) {
							spinner.classList.add('hidden');
						}
					}

					if ((button.id === 'connect_plausible_analytics' || button.classList.contains('plausible-analytics-connect-button')) && response.status === 200) {
						button.innerText = plausible_analytics_i18n.connected;
					} else {
						button.removeAttribute('disabled');
					}
				}

				// We still want the data, if it's a Payment Required error.
				if (response.status === 200 || response.status === 402) {
					return response.json();
				}

				return false;
			}).then(response => {
				if (showMessages === true) {
					plausible.showMessages();
				}

				let event = new CustomEvent('plausibleAjaxDone', {detail: response});

				document.dispatchEvent(event);

				if (response.data !== undefined) {
					return response.data;
				} else {
					return response;
				}
			});
		},

		/**
		 * Show messages on screen.
		 */
		showMessages: function () {
			let messages = plausible.fetchMessages();

			messages.then(function (messages) {
				if (messages.error !== false) {
					plausible.showMessage(messages.error, 'error');
				} else if (messages.notice !== false) {
					plausible.showMessage(messages.notice, 'notice');
				} else if (messages.success !== false) {
					plausible.showMessage(messages.success, 'success');
				}

				if (messages.additional.length === 0 || document.getElementById('plausible-analytics-wizard') !== null) {
					return;
				}

				if (messages.additional.id !== undefined && messages.additional.message) {
					plausible.showAdditionalMessage(messages.additional.message, messages.additional.id);
				} else if (messages.additional.id !== undefined && messages.additional.message === '') {
					plausible.removeAdditionalMessage(messages.additional.id);
				}
			});
		},

		/**
		 * Fetch the messages for display.
		 */
		fetchMessages: function () {
			let data = new FormData();
			data.append('action', 'plausible_analytics_messages');

			let result = plausible.ajax(data, null, false);

			return result.then(function (response) {
				return response;
			});
		},

		/**
		 * Displays a notice or error message.
		 *
		 * @param message
		 * @param type error|warning|success Defaults to success.
		 */
		showMessage: function (message, type = 'success') {
			if (type === 'error') {
				document.getElementById('icon-error').classList.remove('hidden');
				document.getElementById('icon-success').classList.add('hidden');
				document.getElementById('icon-notice').classList.add('hidden');
			} else if (type === 'notice') {
				document.getElementById('icon-notice').classList.remove('hidden');
				document.getElementById('icon-error').classList.add('hidden');
				document.getElementById('icon-success').classList.add('hidden');
			} else {
				document.getElementById('icon-success').classList.remove('hidden');
				document.getElementById('icon-error').classList.add('hidden');
				document.getElementById('icon-notice').classList.add('hidden');
			}

			let notice = document.getElementById('plausible-analytics-notice');

			document.getElementById('plausible-analytics-notice-text').innerHTML = message;

			notice.classList.remove('hidden');

			setTimeout(function () {
				notice.classList.replace('opacity-0', 'opacity-100');
			}, 200)

			if (type !== 'error') {
				setTimeout(function () {
					notice.classList.replace('opacity-100', 'opacity-0');
					setTimeout(function () {
						notice.classList += ' hidden';
					}, 200)
				}, 2000);
			}
		},

		/**
		 * Renders a HTML box containing additional information about the enabled option.
		 *
		 * @param html
		 * @param target
		 */
		showAdditionalMessage: function (html, target) {
			let targetElem = document.querySelector(`[name='${target}']`);
			let container = targetElem.closest('.plausible-analytics-group');

			if (container.children.length > 0) {
				for (let i = 0; i < container.children.length; i++) {
					if (container.children[i].id.includes(target)) {
						// This message already exists.
						return;
					}
				}
			}

			container.innerHTML += html;
		},

		/**
		 * Removes the additional information box when the option is disabled.
		 *
		 * @param target
		 */
		removeAdditionalMessage: function (target) {
			let targetElem = document.querySelector(`[name="${target}"]`);
			let container = targetElem.closest('.plausible-analytics-group');
			let additionalMessage;

			if (container.children.length > 0) {
				for (let i = 0; i < container.children.length; i++) {
					if (container.children[i].classList.contains('plausible-analytics-hook')) {
						additionalMessage = container.children[i];

						break;
					}
				}
			}

			if (additionalMessage !== undefined && !additionalMessage.classList.contains('plausible-analytics-persist')) {
				container.removeChild(additionalMessage);
			}
		}
	}

	plausibleToggleSection = plausible.toggleSection;
	plausibleAddField = plausible.addField;
	plausibleRemoveField = plausible.removeField;

	plausible.init();
});
