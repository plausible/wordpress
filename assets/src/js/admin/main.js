document.addEventListener( 'DOMContentLoaded', () => {
	const saveSettings = document.getElementById( 'plausible-analytics-save-btn' );
	const formElement = document.getElementById( 'plausible-analytics-settings-form' );

	// Bailout, if `formElement` doesn't exist.
	if ( null === formElement ) {
		return;
	}

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
			if ( response.success ) {
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-saved-text' );
				saveSettings.removeAttribute( 'disabled' );
			}

			setTimeout( () => {
				saveSettings.removeAttribute( 'disabled' );
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-default-text' );
			}, 500 );
		} );
	} );
} );
