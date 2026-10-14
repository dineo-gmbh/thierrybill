import { __, sprintf } from "@wordpress/i18n";

const TestModeNoticeIcon = () => (
	<svg
		className="cf7apps-stripe-admin__test-notice-icon"
		width="16"
		height="16"
		viewBox="0 0 16 16"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M8 1.33301L13.3333 4.33301V10.333L8 13.333L2.66667 10.333V4.33301L8 1.33301Z"
			stroke="currentColor"
			strokeWidth="1.1"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
		<path
			d="M8 5.33301V8.33301"
			stroke="currentColor"
			strokeWidth="1.1"
			strokeLinecap="round"
		/>
		<path
			d="M8 10.6663H8.00533"
			stroke="currentColor"
			strokeWidth="1.4"
			strokeLinecap="round"
		/>
	</svg>
);

const PaymentGatewayEnvironmentNotices = ( {
	showTestNotice = false,
	showLiveNotice = false,
	gatewayLabel,
	onSwitchToLiveMode,
	onSwitchToTestMode,
} ) => {

	return (
		<>
			{ showTestNotice ? (
				<div className="cf7apps-stripe-admin__test-notice" role="status">
					<div className="cf7apps-stripe-admin__test-notice-row">
						<div className="cf7apps-stripe-admin__test-notice-copy">
							<TestModeNoticeIcon />
							<p className="cf7apps-stripe-admin__test-notice-message">
								<strong>{ __( "Test Mode is active", "cf7apps" ) }</strong>
								{ " — " }
								{ __( "Payments are currently simulated. Switch to Live Mode when you're ready to accept real payments.", "cf7apps" ) }
							</p>
						</div>
						<button
							type="button"
							className="cf7apps-stripe-admin__test-notice-action"
							onClick={ onSwitchToLiveMode }
						>
							{ __( "Switch to Live Mode", "cf7apps" ) }
						</button>
					</div>
				</div>
			) : null }

			{ showLiveNotice ? (
				<div className="cf7apps-stripe-admin__live-notice" role="status">
					<div className="cf7apps-stripe-admin__live-notice-row">
						<div className="cf7apps-stripe-admin__live-notice-copy">
							<TestModeNoticeIcon />
							<div className="cf7apps-stripe-admin__live-notice-text">
								<p className="cf7apps-stripe-admin__live-notice-title">
									<strong>{ __( "Live Mode selected", "cf7apps" ) }</strong>
								</p>
								<p className="cf7apps-stripe-admin__live-notice-message">
									{ sprintf(
										/* translators: %s: payment gateway name (Stripe, PayPal, Square) */
										__( "You are switching to Live Mode. Payments will now be processed through your live %s account.", "cf7apps" ),
										gatewayLabel
									) }
								</p>
								<p className="cf7apps-stripe-admin__live-notice-message">
									{ __( "Your Test Mode connection is still saved and can be used anytime by switching back to Test Mode.", "cf7apps" ) }
								</p>
							</div>
						</div>
						<button
							type="button"
							className="cf7apps-stripe-admin__live-notice-action"
							onClick={ onSwitchToTestMode }
						>
							{ __( "Switch to Test Mode", "cf7apps" ) }
						</button>
					</div>
				</div>
			) : null }
		</>
	);
};

export default PaymentGatewayEnvironmentNotices;
