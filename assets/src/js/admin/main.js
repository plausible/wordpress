document.addEventListener( 'DOMContentLoaded', () => {
	document.addEventListener( 'click', ( e ) => {
		const dismissButton = e.target.closest( '.notice-dismiss' );

		if ( dismissButton ) {
			const form = new FormData();

			form.append( 'action', 'plausible_analytics_notice_dismissed' );

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
