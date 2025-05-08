/**
 * Cloaked (Affiliate) Links tracking JS
 *
 * @since 2.4.0
 */

const plausibleLinksTracking = {
	middleMouseButton: 1,

	/**
	 * Intialize.
	 */
	init: () => {
		plausibleLinksTracking.bindEvents();
	},

	/**
	 * Bind Events.
	 */
	bindEvents: () => {
		document.addEventListener('click', plausibleLinksTracking.handleLinkClick);
		document.addEventListener('auxclick', plausibleLinksTracking.handleLinkClick);
	},

	/**
	 * Handle Link Clicks.
	 *
	 * @param e
	 */
	handleLinkClick: (e) => {
		if (e.type === 'auxclick' && e.button !== plausibleLinksTracking.middleMouseButton) {
			return;
		}

		var link = plausibleLinksTracking.getLinkEl(e.target);

		if (link && plausibleLinksTracking.shouldTrackLink(link)) {
			var eventName = 'Cloaked Link: Click';
			var eventProps = {url: link.href};

			return plausibleLinksTracking.sendLinkClickEvent(e, link, eventName, eventProps);
		}
	},

	/**
	 * Retrieves a link element from an event target.
	 *
	 * @param link
	 *
	 * @returns {{href}|*}
	 */
	getLinkEl: (link) => {
		while (link && (typeof link.tagName === 'undefined' || link.tagName.toLowerCase() !== 'a' || !link.href)) {
			link = link.parentNode;
		}

		return link;
	},

	/**
	 * Should we track this link?
	 *
	 * @param link
	 * @returns {boolean}
	 */
	shouldTrackLink: (link) => {
		let affiliateLinks = plausibleAffiliateLinks;

		let foundMatch = affiliateLinks.filter((affiliateLink) => {
			return link.href.match(affiliateLink);
		});

		return foundMatch.length > 0;
	},

	/**
	 * Sends the click event to the Plausible API.
	 *
	 * @param event
	 * @param link
	 * @param eventName
	 * @param eventProps
	 */
	sendLinkClickEvent: (event, link, eventName, eventProps) => {
		var followedLink = false;

		function followLink() {
			if (!followedLink) {
				followedLink = true;
				window.location = link.href;
			}
		}

		if (plausibleLinksTracking.shouldFollowLink(event, link)) {
			plausible(eventName, {props: eventProps, callback: followLink});
			setTimeout(followLink, 5000);
			event.preventDefault();
		} else {
			plausible(eventName, {props: eventProps});
		}
	},

	/**
	 *
	 * @param event
	 * @param link
	 * @returns {*|boolean}
	 */
	shouldFollowLink: (event, link) => {
		// If default has been prevented by an external script, Plausible should not intercept navigation.
		if (event.defaultPrevented) {
			return false;
		}

		var targetsCurrentWindow = !link.target || link.target.match(/^_(self|parent|top)$/i);
		var isRegularClick = !(event.ctrlKey || event.metaKey || event.shiftKey) && event.type === 'click';

		return targetsCurrentWindow && isRegularClick;
	}
}

plausibleLinksTracking.init();
