import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { getPaymentGatewaySettingsUrl } from '../../../../../src/utils/paymentGatewayActivationNotice';

const CF7AppsDisabledOverlay = ({ appId, appTitle, showActivationMessage = false }) => {
    const redirectToSettings = () => {
        window.location.href = getPaymentGatewaySettingsUrl( appId );
    };

    return (
        <div className="cf7apps-disabled-overlay">
            <div className="cf7apps-disabled-message">
                { showActivationMessage ? (
                    <p>
                        { __(
                            'CF7 Apps must be activated to access and use the payment gateway features.',
                            'cf7apps'
                        ) }
                    </p>
                ) : null }
                <Button
                    className="cf7apps-enable-button"
                    onClick={redirectToSettings}
                    variant="primary"
                    size="large"
                >
                    { showActivationMessage
                        ? sprintf(
                            /* translators: %s: app title (Stripe, PayPal, Square, etc.) */
                            __( 'Activate %s', 'cf7apps' ),
                            appTitle
                          )
                        : __( 'To Enable, Go to Settings', 'cf7apps' ) }
                </Button>
            </div>
        </div>
    );
};

export default CF7AppsDisabledOverlay;
