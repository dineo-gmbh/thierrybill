import { useCallback, useEffect, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { Button } from "@wordpress/components";
import CF7AppsFormToggle from "../../components/CF7AppsFormToggle";
import CF7AppsSelectField from "../../components/CF7AppsSelectField";
import PaymentGatewayConnectionStatus from "../../components/PaymentGatewayConnectionStatus";
import PaymentGatewayEnvironmentNotices from "../../components/PaymentGatewayEnvironmentNotices";
import { getApps, saveSettings } from "../../api/api";
import { getOAuthReturnStatus, clearOAuthReturnStatus } from "../../utils/oauthReturnParams";
import {
	canUsePaymentGatewayConnect,
	hasUnsavedPaymentGatewayEnableChange,
	shouldShowPaymentGatewayConnectButton,
	usePaymentGatewayEnvironmentNotice,
} from "../../utils/paymentGatewayAdminUtils";
import { toast } from "react-toastify";

const ExternalLinkIcon = () => (
	<svg
		className="cf7apps-stripe-admin__external-icon"
		width="9"
		height="9"
		viewBox="0 0 9 9"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M7.5 5.25V7.5H1.5V1.5H3.75M7.5 1.5L4.125 4.875M7.5 1.5H5.25M7.5 1.5V3.75"
			stroke="currentColor"
			strokeWidth="0.75"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const PayPalAdmin = ( { appSettings } ) => {
	const proBase = typeof CF7Apps !== "undefined" && CF7Apps.proRestBase ? CF7Apps.proRestBase : "";
	const assetsBase = typeof CF7Apps !== "undefined" && CF7Apps.proPayPalAssetsURL ? CF7Apps.proPayPalAssetsURL : "";

	const [ isEnabled, setIsEnabled ] = useState( false );
	const [ environmentMode, setEnvironmentMode ] = useState( "live" );
	const [ defaultCurrency, setDefaultCurrency ] = useState( "USD" );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ connection, setConnection ] = useState( null );
	const [ savedSettings, setSavedSettings ] = useState( {
		is_enabled: false,
		environment_mode: "live",
		default_currency: "USD",
	} );

	const hydrateFromApi = useCallback( async () => {
		const data = await getApps( "paypal" );
		if ( ! data ) {
			return null;
		}

		let nextEnvironmentMode = "live";
		if ( data.paypal_settings ) {
			const nextSettings = {
				is_enabled: !! data.paypal_settings.is_enabled,
				environment_mode: data.paypal_settings.environment_mode === "test" ? "test" : "live",
				default_currency: data.paypal_settings.default_currency || "USD",
			};
			nextEnvironmentMode = nextSettings.environment_mode;
			setIsEnabled( nextSettings.is_enabled );
			setEnvironmentMode( nextSettings.environment_mode );
			setDefaultCurrency( nextSettings.default_currency );
			setSavedSettings( nextSettings );
		}

		const nextConnection = data.paypal_connection || null;
		if ( nextConnection ) {
			setConnection( nextConnection );
		}

		return {
			environmentMode: nextEnvironmentMode,
			connection: nextConnection,
		};
	}, [] );

	useEffect( () => {
		hydrateFromApi();
	}, [ hydrateFromApi ] );

	const currencyOptions = appSettings?.paypal_settings?.currency_options || { USD: "USD - US Dollar" };

	const onSave = async () => {
		setIsSaving( true );
		const ok = await saveSettings( "paypal", {
			is_enabled: isEnabled,
			environment_mode: environmentMode,
			default_currency: defaultCurrency,
		} );
		setIsSaving( false );
		if ( ok ) {
			setSavedSettings( {
				is_enabled: isEnabled,
				environment_mode: environmentMode,
				default_currency: defaultCurrency,
			} );
			toast.success( __( "Great! Settings Saved Successfully", "cf7apps" ) );
			hydrateFromApi();
		} else {
			toast.error( __( "Error! Something Went Wrong", "cf7apps" ) );
		}
	};

	const connectPayPal = async () => {
		if ( ! canConnect ) {
			return;
		}
		if ( ! proBase ) {
			toast.error( __( "CF7 Apps Pro is not active.", "cf7apps" ) );
			return;
		}
		const url = `${ proBase }paypal/connect-url?mode=${ encodeURIComponent( environmentMode ) }`;
		const res = await fetch( url, { headers: { "X-WP-Nonce": CF7Apps.nonce } } );
		const json = await res.json().catch( () => ( {} ) );
		if ( ! res.ok || ! json.url ) {
			toast.error( json.message || __( "Could not start PayPal connection.", "cf7apps" ) );
			return;
		}
		window.location.href = json.url;
	};

	const disconnectPayPal = async () => {
		if ( ! proBase ) {
			return;
		}
		setIsSaving( true );
		const res = await fetch( `${ proBase }paypal/disconnect`, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": CF7Apps.nonce,
			},
			body: JSON.stringify( { mode: environmentMode } ),
		} );
		setIsSaving( false );
		if ( res.ok ) {
			toast.success( __( "Disconnected from PayPal.", "cf7apps" ) );
			hydrateFromApi();
		} else {
			toast.error( __( "Could not disconnect.", "cf7apps" ) );
		}
	};

	const connectedNow = connection && (
		( environmentMode === "test" && connection.connected_test ) ||
		( environmentMode === "live" && connection.connected_live )
	);
	const showConnectButton = shouldShowPaymentGatewayConnectButton( {
		connectedNow,
		isEnabled,
		savedIsEnabled: savedSettings.is_enabled,
	} );
	const hasUnsavedEnableChange = hasUnsavedPaymentGatewayEnableChange(
		isEnabled,
		savedSettings.is_enabled
	);
	const canConnect = canUsePaymentGatewayConnect( {
		savedIsEnabled: savedSettings.is_enabled,
		hasUnsavedEnableChange,
		isSaving,
	} );

	const showTestConnectionSuccess = environmentMode === "test" && connectedNow;
	const {
		showTestNotice,
		showLiveNotice,
		applyModeSelection,
		showNoticeAfterAuth,
	} = usePaymentGatewayEnvironmentNotice( connection, savedSettings.environment_mode );

	useEffect( () => {
		const st = getOAuthReturnStatus( "cf7apps_paypal" );
		if ( st === "connected" ) {
			toast.success( __( "PayPal account connected.", "cf7apps" ) );
			clearOAuthReturnStatus( "cf7apps_paypal" );
			hydrateFromApi().then( ( snapshot ) => {
				if ( snapshot ) {
					showNoticeAfterAuth( snapshot.environmentMode, snapshot.connection );
				}
			} );
		} else if ( st === "error" ) {
			toast.error( __( "Connection failed. Please try again.", "cf7apps" ) );
			clearOAuthReturnStatus( "cf7apps_paypal" );
		}
	}, [ hydrateFromApi, showNoticeAfterAuth ] );

	const paypalMarkSrc = assetsBase ? `${ assetsBase }paypal-mark.svg` : "";

	return (
		<div className="cf7apps-stripe-admin cf7apps-stripe-admin--paypal">
			<div className="cf7apps-stripe-admin__card">
				<PaymentGatewayEnvironmentNotices
					showTestNotice={ showTestNotice }
					showLiveNotice={ showLiveNotice }
					gatewayLabel={ __( "PayPal", "cf7apps" ) }
					onSwitchToLiveMode={ () => applyModeSelection( "live", setEnvironmentMode ) }
					onSwitchToTestMode={ () => applyModeSelection( "test", setEnvironmentMode ) }
				/>

				<div className="cf7apps-stripe-admin__fields">
					<div className="cf7apps-stripe-admin__field">
						<CF7AppsFormToggle
							label={ __( "Enable PayPal Integration", "cf7apps" ) }
							help={ __( "Accept payments in Contact Form 7 using PayPal", "cf7apps" ) }
							isSelected={ isEnabled }
							onChange={ setIsEnabled }
						/>
					</div>

					<div className="cf7apps-stripe-admin__field">
						<div className="cf7apps-stripe-admin__label">{ __( "Environment Mode", "cf7apps" ) }</div>
						<div className="cf7apps-stripe-admin__radios">
							<label className="cf7apps-stripe-admin__radio-row">
								<input
									type="radio"
									name="cf7apps_paypal_env"
									checked={ environmentMode === "live" }
									onChange={ () => applyModeSelection( "live", setEnvironmentMode ) }
								/>
								<span className="cf7apps-stripe-admin__radio-label">
									<strong>{ __( "Live Mode", "cf7apps" ) }</strong>
									{ " " }
									<span className="cf7apps-stripe-admin__radio-label-note">
										{ __( "(Process real transactions against your PayPal account)", "cf7apps" ) }
									</span>
								</span>
							</label>
							<label className="cf7apps-stripe-admin__radio-row">
								<input
									type="radio"
									name="cf7apps_paypal_env"
									checked={ environmentMode === "test" }
									onChange={ () => applyModeSelection( "test", setEnvironmentMode ) }
								/>
								<span className="cf7apps-stripe-admin__radio-label">
									<strong>{ __( "Test Mode", "cf7apps" ) }</strong>
									{ " " }
									<span className="cf7apps-stripe-admin__radio-label-note">
										{ __( "(Simulate transactions for testing purposes)", "cf7apps" ) }
									</span>
								</span>
							</label>
						</div>
					</div>

					<div className="cf7apps-stripe-admin__field cf7apps-stripe-admin__field--currency">
						<CF7AppsSelectField
							key={ `paypal-currency-${ defaultCurrency }` }
							label={ __( "Default Currency", "cf7apps" ) }
							name="default_currency"
							options={ currencyOptions }
							selected={ defaultCurrency }
							onChange={ ( e ) => setDefaultCurrency( e.target.value ) }
							className="cf7apps-stripe-admin__select"
							variant="app"
						/>
					</div>

					<div className="cf7apps-stripe-admin__field">
						<div className="cf7apps-stripe-admin__label">{ __( "PayPal Connection", "cf7apps" ) }</div>
						<div className="cf7apps-stripe-admin__connect-row">
							{ showConnectButton && (
								<button
									type="button"
									className="cf7apps-stripe-admin__connect-btn cf7apps-stripe-admin__connect-btn--paypal"
									onClick={ connectPayPal }
									disabled={ ! canConnect }
								>
									<span className="cf7apps-stripe-admin__connect-inner">
										{ paypalMarkSrc ? (
											<img
												src={ paypalMarkSrc }
												alt=""
												className="cf7apps-stripe-admin__connect-logo cf7apps-stripe-admin__connect-logo--paypal"
												width="18"
												height="21"
												decoding="async"
											/>
										) : null }
										<span className="cf7apps-stripe-admin__connect-text">
											{ __( "Connect to PayPal", "cf7apps" ) }
										</span>
									</span>
								</button>
							) }
							{ connectedNow && (
								<PaymentGatewayConnectionStatus
									onDisconnect={ disconnectPayPal }
									isSaving={ isSaving }
									showTestConnectionSuccess={ showTestConnectionSuccess }
								/>
							) }
						</div>
						{ showConnectButton && ! canConnect && (
							<p className="cf7apps-stripe-admin__connect-help">
								{ __( "Save your settings before connecting your payment account.", "cf7apps" ) }
							</p>
						) }
						<p className="cf7apps-stripe-admin__dashboard-link-wrap">
							<ExternalLinkIcon />
							<a
								className="cf7apps-stripe-admin__dashboard-link"
								href="https://www.paypal.com/businessmanage/account/home"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( "Visit PayPal Dashboard", "cf7apps" ) }
							</a>
							<span className="cf7apps-stripe-admin__dashboard-link-note">
								{ __( "to manage your account settings", "cf7apps" ) }
							</span>
						</p>
					</div>
				</div>

				<div className="cf7apps-stripe-admin__actions cf7apps-form-settings-save">
					<Button className="cf7apps-btn tertiary-primary" onClick={ onSave } isBusy={ isSaving }>
						{ __( "Save Settings", "cf7apps" ) }
					</Button>
				</div>
			</div>
		</div>
	);
};

export default PayPalAdmin;
