document.addEventListener( 'DOMContentLoaded', () => {
	const saveSettings = document.getElementById( 'plausible-analytics-save-btn' );
	const formElement = document.getElementById( 'plausible-analytics-settings-form' );

	// Bailout, if `formElement` doesn't exist.
	if ( null === formElement ) {
		return;
	}

	const tabsWrap = formElement.querySelector( '.plausible-analytics-admin-tabs' );
	const tabs = Array.from( tabsWrap.querySelectorAll( 'a' ) );
	const tabContents = Array.from( formElement.querySelectorAll( '.plausible-analytics-content' ) );

	tabs.forEach( ( tab ) => {
		tab.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const tabName = e.target.getAttribute( 'data-tab' );

			tabs.map( ( tabElement ) => tabElement.classList.remove( 'active' ) );
			tabContents.map( ( tabContent ) => tabContent.classList.remove( 'plausible-analytics-show' ) );

			e.target.classList.add( 'active' );
			formElement.querySelector( `#plausible-analytics-content-${ tabName }` ).classList.add( 'plausible-analytics-show' );
		} );
	} );

	const isProxyElement = formElement.querySelector( 'input[name="plausible_analytics_settings[is_proxy]"]' );
	const isCustomPathElement = formElement.querySelector( 'input[name="plausible_analytics_settings[is_custom_path]"]' );
	const selfHostedAnalyticsElement = formElement.querySelector( 'input[name="plausible_analytics_settings[is_self_hosted_analytics]"]' );
	const selfHostedDomainElement = formElement.querySelector( 'input[name="plausible_analytics_settings[self_hosted_domain]"]' );
	const scriptPathElement = formElement.querySelector( 'input[name="plausible_analytics_settings[script_path]"]' );
	const eventPathElement = formElement.querySelector( 'input[name="plausible_analytics_settings[event_path]"]' );
	const embedAnalyticsElement = formElement.querySelector( 'input[name="plausible_analytics_settings[embed_analytics]"]' );
	const trackAdminElement = formElement.querySelector( 'input[name="plausible_analytics_settings[track_administrator]"]' );
	const sharedLinkElement = formElement.querySelector( 'input[name="plausible_analytics_settings[shared_link]"]' );

	const advancedProxyElement = formElement.querySelector( '#advanced-proxy' );
	const advancedProxyLinkElement = formElement.querySelector( 'a[href="#advanced-proxy"]' );

	formElement.addEventListener( 'click', function( e ) {
		if ( e.target && ( e.target === advancedProxyLinkElement ) ) {
			e.preventDefault();
			advancedProxyElement.classList.toggle( 'plausible-analytics-hidden' );
		}
	} );

	formElement.addEventListener( 'change', function( e ) {
		// eslint-disable-next-line no-console
		console.log( e.target );

		if ( e.target && e.target === isProxyElement ) {
			if ( isProxyElement.checked && ! isCustomPathElement.checked ) {
				advancedProxyElement.classList.add( 'plausible-analytics-hidden' );
			} else if ( isProxyElement.checked && isCustomPathElement.checked ) {
				advancedProxyElement.classList.remove( 'plausible-analytics-hidden' );
			} else if ( ! isProxyElement.checked && isCustomPathElement.checked ) {
				advancedProxyElement.classList.add( 'plausible-analytics-hidden' );
				isCustomPathElement.checked = false;
			}
		}

		if ( e.target && e.target === isCustomPathElement ) {
			if ( isCustomPathElement.checked ) {
				isProxyElement.checked = true;
			}
		}

		if ( e.target && ( e.target === isCustomPathElement || e.target === isProxyElement ) ) {
			scriptPathElement.disabled = ! ( isCustomPathElement.checked && isProxyElement.checked );
			eventPathElement.disabled = ! ( isCustomPathElement.checked && isProxyElement.checked );
		}

		if ( e.target && ( e.target === embedAnalyticsElement ) ) {
			sharedLinkElement.disabled = ! ( embedAnalyticsElement.checked );
		}

		if ( e.target && ( e.target === selfHostedAnalyticsElement ) ) {
			selfHostedDomainElement.disabled = ! ( selfHostedAnalyticsElement.checked );
		}
	} );

	saveSettings.addEventListener( 'click', ( e ) => {
		e.preventDefault();

		const formData = new FormData();
		const spinner = formElement.querySelector( '.plausible-analytics-spinner' );
		const domainName = formElement.querySelector( 'input[name="plausible_analytics_settings[domain_name]"]' ).value;
		const isProxy = null !== isProxyElement ? isProxyElement.checked : false;
		const isCustomPath = null !== isCustomPathElement ? isCustomPathElement.checked : false;
		const scriptPath = scriptPathElement.value;
		const eventPath = eventPathElement.value;
		const selfHostedDomain = formElement.querySelector( 'input[name="plausible_analytics_settings[self_hosted_domain]"]' ).value;
		const isSelfHostedAnalytics = null !== selfHostedAnalyticsElement ? selfHostedAnalyticsElement.checked : false;
		const isTrackAdmin = null !== trackAdminElement ? trackAdminElement.checked : false;
		const canEmbedAnalytics = null !== embedAnalyticsElement ? embedAnalyticsElement.checked : false;
		const roadBlock = null !== formElement.querySelector( '.plausible-analytics-admin-settings-roadblock' ) ? document.querySelector( '.plausible-analytics-admin-settings-roadblock' ).value : '';
		const sharedLink = null !== sharedLinkElement ? sharedLinkElement.value : 0;

		spinner.style.display = 'block';
		saveSettings.setAttribute( 'disabled', 'disabled' );

		formData.append( 'action', 'plausible_analytics_save_admin_settings' );
		formData.append( 'roadblock', roadBlock );
		formData.append( 'domain_name', domainName );
		formData.append( 'is_proxy', isProxy === true );
		formData.append( 'is_custom_path', isCustomPath === true );
		formData.append( 'script_path', scriptPath );
		formData.append( 'event_path', eventPath );
		formData.append( 'is_self_hosted_analytics', isSelfHostedAnalytics === true );
		formData.append( 'self_hosted_domain', selfHostedDomain );
		formData.append( 'embed_analytics', canEmbedAnalytics === true );
		formData.append( 'shared_link', sharedLink );
		formData.append( 'track_administrator', isTrackAdmin === true );

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
			}

			setTimeout( () => {
				spinner.style.display = 'none';
				saveSettings.removeAttribute( 'disabled' );
				saveSettings.querySelector( 'span' ).innerText = saveSettings.getAttribute( 'data-default-text' );
			}, 500 );
		} );
	} );
} );
