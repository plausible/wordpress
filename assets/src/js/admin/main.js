document.addEventListener( 'DOMContentLoaded', () => {
	const saveSettings = document.getElementById( 'plausible-analytics-save-btn' );

	saveSettings.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		const formElement = document.getElementById( 'plausible-analytics-settings-form' );

		// Bailout, if `formElement` doesn't exist.
		if ( null === formElement ) {
			return;
		}

		const formData = new FormData();
		const spinner = formElement.querySelector( '.plausible-analytics-spinner' );
		const domainName = formElement.querySelector( 'input[name="plausible_analytics_settings[domain_name]"]' ).value;
		const customDomainPrefix = formElement.querySelector( 'input[name="plausible_analytics_settings[custom_domain_prefix]"]' ).value;
		const customDomainElement = formElement.querySelector( 'input[name="plausible_analytics_settings[custom_domain]"]:checked' );
		const isCustomDomain = null !== customDomainElement ? parseInt( customDomainElement.value ) : 0;
		const trackAdminElement = formElement.querySelector( 'input[name="plausible_analytics_settings[track_administrator]"]:checked' );
		const isTrackAdmin = null !== trackAdminElement ? parseInt( trackAdminElement.value ) : 0;
		const roadBlock = null !== formElement.querySelector( '.plausible-analytics-admin-settings-roadblock' ) ? document.querySelector( '.plausible-analytics-admin-settings-roadblock' ).value : '';

		spinner.style.display = 'block';
		saveSettings.setAttribute( 'disabled', 'disabled' );

		formData.append( 'action', 'plausible_analytics_save_admin_settings' );
		formData.append( 'roadblock', roadBlock );
		formData.append( 'domain_name', domainName );
		formData.append( 'custom_domain', isCustomDomain === 1 );
		formData.append( 'custom_domain_prefix', customDomainPrefix );
		formData.append( 'track_administrator', isTrackAdmin === 1 );

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
			}

			setTimeout( () => {
				spinner.style.display = 'none';
				saveSettings.removeAttribute( 'disabled' );
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-default-text' );
			}, 500 );
		} );
	} );
} );
