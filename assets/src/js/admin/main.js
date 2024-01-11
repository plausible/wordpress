/**
 * Plausible Analytics
 *
 * Admin JS
 */
document.addEventListener( 'DOMContentLoaded', () => {
	document.addEventListener( 'click', ( e ) => {
		if ( ! e.target.classList.contains( 'plausible-analytics-toggle' ) ) {
			return;
		}

		const button = e.target.closest( 'button' );
		let toggle = '';
		let toggleStatus = ';';

		// The button element is clicked.
		if ( e.target.type === 'submit' ) {
			toggle = button.querySelector( 'span' );
		} else {
			// The span element is clicked.
			toggle = e.target.closest( 'span' );
		}

		if ( button.classList.contains( 'bg-indigo-600' ) ) {
			// Toggle: off
			button.classList.replace( 'bg-indigo-600', 'bg-gray-200' );
			toggle.classList.replace( 'translate-x-5', 'translate-x-0' );
			toggleStatus = '';
		} else {
			// Toggle: on
			button.classList.replace( 'bg-gray-200', 'bg-indigo-600' );
			toggle.classList.replace( 'translate-x-0', 'translate-x-5' );
			toggleStatus = 'on';
		}

		const form = new FormData();
		form.append( 'action', 'plausible_analytics_toggle_option' );
		form.append( 'option_name', button.name );
		form.append( 'option_value', button.value );
		form.append( 'toggle_status', toggleStatus );
		form.append( 'is_list', button.dataset.list );
		form.append( '_nonce', document.getElementById( '_wpnonce' ).value );

		fetch(
			ajaxurl,
			{
				method: 'POST',
				body: form,
			}
		).then( response => {
			return response.status;
		} );
	} );

	document.addEventListener( 'click', ( e ) => {
		const dismissButton = e.target.closest( '.notice-dismiss' );

		if ( dismissButton ) {
			const form = new FormData();

			form.append( 'action', 'plausible_analytics_notice_dismissed' );
			form.append( 'id', dismissButton.parentElement.id );

			fetch(
				ajaxurl,
				{
					method: 'POST',
					body: form,
				}
			);
		}
	} );

	const formElement = document.getElementById( 'plausible-analytics-settings-form' );

	// Bailout, if `formElement` doesn't exist.
	if ( null === formElement ) {
		return;
	}

	const saveSettings = document.getElementById( 'plausible-analytics-save-btn' );

	saveSettings.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		const formData = new FormData( formElement );

		saveSettings.setAttribute( 'disabled', 'disabled' );

		formData.append( 'action', 'plausible_analytics_save_admin_settings' );

		fetch(
			ajaxurl,
			{
				method: 'POST',
				body: formData,
			}
		).then( response => {
			if ( 200 === response.status ) {
				return response.json();
			}

			return false;
		} ).then( response => {
			if ( response.error ) {
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-saved-error' );
			}

			if ( response.success ) {
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-saved-text' );
				saveSettings.removeAttribute( 'disabled' );
			}

			setTimeout( () => {
				saveSettings.removeAttribute( 'disabled' );
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-default-text' );
			}, 500 );

			document.location.reload();
		} );
	} );
} );
