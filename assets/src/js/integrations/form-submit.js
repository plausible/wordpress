/**
 * Plausible Analytics
 *
 * Track Form Submissions JS
 */
document.addEventListener('DOMContentLoaded', () => {
	let plausible_track_form_submit = {
		forms: document.querySelectorAll('form'),

		/**
		 * Initialization.
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind Events.
		 */
		bindEvents: function () {
			let self = this;

			this.forms.forEach((form) => {
				form.addEventListener('submit', (e) => {
					if (e.target.checkValidity()) {
						self.trackSubmission();
					}
				})
			})
		},

		/**
		 * Send a custom event to Plausible.
		 */
		trackSubmission: function () {
			plausible(plausible_analytics_i18n.form_completions);
		}
	};

	plausible_track_form_submit.init();
});
