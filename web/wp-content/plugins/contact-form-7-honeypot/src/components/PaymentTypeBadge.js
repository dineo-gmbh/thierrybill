import { getCF7AppsAssetsURL } from '../utils/appCardTheme';

const PAYMENT_TYPE_ICONS = {
	stripe: 'payment-stripe-icon.svg',
	square: 'payment-square-icon.svg',
	paypal: 'payment-paypal-icon.svg',
};

const PAYMENT_TYPE_LABELS = {
	stripe: 'Stripe',
	paypal: 'PayPal',
	square: 'Square',
};

const getProPaymentMarkSrc = ( key ) => {
	if ( typeof CF7Apps === 'undefined' ) {
		return '';
	}

	if ( key === 'stripe' && CF7Apps.proAssetsURL ) {
		return `${ CF7Apps.proAssetsURL }stripe-mark.svg`;
	}

	if ( key === 'paypal' && CF7Apps.proPayPalAssetsURL ) {
		return `${ CF7Apps.proPayPalAssetsURL }paypal-mark.svg`;
	}

	if ( key === 'square' && CF7Apps.proSquareAssetsURL ) {
		return `${ CF7Apps.proSquareAssetsURL }square-connect-mark.svg`;
	}

	return '';
};

export const getPaymentMethodKey = ( method ) => {
	const normalized = String( method || '' ).toLowerCase().trim();

	if ( ! normalized ) {
		return '';
	}

	if ( normalized.includes( 'stripe' ) || normalized === 'st' ) {
		return 'stripe';
	}

	if ( normalized.includes( 'paypal' ) || normalized === 'pl' ) {
		return 'paypal';
	}

	if ( normalized.includes( 'square' ) || normalized === 'sq' ) {
		return 'square';
	}

	return normalized;
};

const resolvePaymentIconSrc = ( key ) => {
	const proMark = getProPaymentMarkSrc( key );
	if ( proMark ) {
		return proMark;
	}

	const iconFile = PAYMENT_TYPE_ICONS[ key ];
	const assetsBase = getCF7AppsAssetsURL();

	if ( ! iconFile || ! assetsBase ) {
		return '';
	}

	return `${ assetsBase }/images/dashboard/${ iconFile }`;
};

/**
 * Small payment provider badge with icon + label.
 *
 * @since 3.8.0
 */
const PaymentTypeBadge = ( { paymentMethod, paymentMethodKey = '' } ) => {
	const key = getPaymentMethodKey( paymentMethodKey || paymentMethod );
	const iconSrc = resolvePaymentIconSrc( key );
	const label = PAYMENT_TYPE_LABELS[ key ] || paymentMethod || '-';

	if ( ! iconSrc ) {
		return <span className="cf7apps-payment-type-badge">{ label }</span>;
	}

	return (
		<span className="cf7apps-payment-type-badge">
			<img
				className="cf7apps-payment-type-badge__icon"
				src={ iconSrc }
				alt=""
				width={ 16 }
				height={ 16 }
			/>
			<span className="cf7apps-payment-type-badge__label">{ label }</span>
		</span>
	);
};

export default PaymentTypeBadge;
