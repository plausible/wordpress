document.addEventListener( 'DOMContentLoaded', () => {
	const saveSettings = document.getElementById( 'plausible-analytics-save-btn' );
	const formElement = document.getElementById( 'plausible-analytics-settings-form' );

	// Bailout, if `formElement` doesn't exist.
	if ( null === formElement ) {
		return;
	}

	const customDomainElement = formElement.querySelector( 'input[name="plausible_analytics_settings[custom_domain]"]' );
	const selfHostedAnalyticsElement = formElement.querySelector( 'input[name="plausible_analytics_settings[is_self_hosted_analytics]"]' );

	customDomainElement.addEventListener( 'change', ( e ) => {
		if ( e.target.checked ) {
			selfHostedAnalyticsElement.removeAttribute( 'checked' );
			selfHostedAnalyticsElement.checked ? selfHostedAnalyticsElement.click() : '';
		}
	} );

	selfHostedAnalyticsElement.addEventListener( 'change', ( e ) => {
		if ( e.target.checked ) {
			customDomainElement.removeAttribute( 'checked' );
			customDomainElement.checked ? customDomainElement.click() : '';
		}
	} );

	saveSettings.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		const formData = new FormData();
		const spinner = formElement.querySelector( '.plausible-analytics-spinner' );
		const domainName = formElement.querySelector( 'input[name="plausible_analytics_settings[domain_name]"]' ).value;
		const customDomainPrefix = formElement.querySelector( 'input[name="plausible_analytics_settings[custom_domain_prefix]"]' ).value;
		const isCustomDomain = null !== customDomainElement ? customDomainElement.checked : false;
		const selfHostedDomain = formElement.querySelector( 'input[name="plausible_analytics_settings[self_hosted_domain]"]' ).value;
		const isSelfHostedAnalytics = null !== selfHostedAnalyticsElement ? selfHostedAnalyticsElement.checked : false;
		const trackAdminElement = formElement.querySelector( 'input[name="plausible_analytics_settings[track_administrator]"]:checked' );
		const isTrackAdmin = null !== trackAdminElement ? parseInt( trackAdminElement.value ) : 0;
		const roadBlock = null !== formElement.querySelector( '.plausible-analytics-admin-settings-roadblock' ) ? document.querySelector( '.plausible-analytics-admin-settings-roadblock' ).value : '';

		spinner.style.display = 'block';
		saveSettings.setAttribute( 'disabled', 'disabled' );

		formData.append( 'action', 'plausible_analytics_save_admin_settings' );
		formData.append( 'roadblock', roadBlock );
		formData.append( 'domain_name', domainName );
		formData.append( 'custom_domain', isCustomDomain === true );
		formData.append( 'custom_domain_prefix', customDomainPrefix );
		formData.append( 'is_self_hosted_analytics', isSelfHostedAnalytics === true );
		formData.append( 'self_hosted_domain', selfHostedDomain );
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
