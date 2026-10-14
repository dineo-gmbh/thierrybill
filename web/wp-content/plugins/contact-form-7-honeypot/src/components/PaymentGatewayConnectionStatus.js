import { __ } from "@wordpress/i18n";
import { CheckCircleOutlineSharp } from "@mui/icons-material";

const DisconnectIcon = () => (
	<svg
		className="cf7apps-stripe-admin__disconnect-icon"
		width="8"
		height="8"
		viewBox="0 0 8 8"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M1 1L7 7M7 1L1 7"
			stroke="currentColor"
			strokeWidth="1.2"
			strokeLinecap="round"
		/>
	</svg>
);

const PaymentGatewayConnectionStatus = ( {
	onDisconnect,
	isSaving = false,
	showTestConnectionSuccess = false,
} ) => (
	<div className="cf7apps-stripe-admin__connection-status">
		<button
			type="button"
			className="cf7apps-stripe-admin__disconnect-btn"
			onClick={ onDisconnect }
			disabled={ isSaving }
		>
			<DisconnectIcon />
			<span className="cf7apps-stripe-admin__disconnect-label">
				{ __( "Disconnect", "cf7apps" ) }
			</span>
		</button>
		{ showTestConnectionSuccess && (
			<span className="cf7apps-stripe-admin__connection-success">
				<CheckCircleOutlineSharp aria-hidden="true" />
				{ __( "Test Mode Activated Successfully", "cf7apps" ) }
			</span>
		) }
	</div>
);

export default PaymentGatewayConnectionStatus;
