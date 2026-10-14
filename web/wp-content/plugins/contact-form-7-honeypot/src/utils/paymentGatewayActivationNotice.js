import { __, sprintf } from '@wordpress/i18n';

export const hasCf7AppsPro = () =>
	Boolean(
		( typeof CF7Apps !== 'undefined' && CF7Apps.hasPro ) ||
			( typeof CF7AppsInternalSettings !== 'undefined' &&
				CF7AppsInternalSettings.hasPro )
	);

export const getPaymentGatewaySettingsUrl = ( gatewayId ) => {
	const base =
		( typeof CF7AppsInternalSettings !== 'undefined' &&
			CF7AppsInternalSettings.appIndexURL ) ||
		( typeof CF7Apps !== 'undefined' && CF7Apps.appIndexURL ) ||
		'';

	return `${ base }#/settings/${ gatewayId }`;
};

export const getPaymentGatewayActivationNoticeHtml = (
	gatewayId,
	gatewayTitle
) => {
	const activationUrl = getPaymentGatewaySettingsUrl( gatewayId );
	const activateLabel = sprintf(
		/* translators: %s: payment gateway name (Stripe, PayPal, Square) */
		__( 'Activate %s in CF7 Apps', 'cf7apps' ),
		gatewayTitle
	);

	return sprintf(
		__(
			'CF7 Apps must be activated to access and use the payment gateway features. %s',
			'cf7apps'
		),
		`<a class="cf7apps-payment-activation-notice-link" href="${ activationUrl }">${ activateLabel }</a>`
	);
};
