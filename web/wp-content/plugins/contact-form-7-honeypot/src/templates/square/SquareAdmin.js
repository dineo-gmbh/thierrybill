import { useCallback, useEffect, useMemo, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { Button } from "@wordpress/components";
import CF7AppsFormToggle from "../../components/CF7AppsFormToggle";
import CF7AppsSelectField from "../../components/CF7AppsSelectField";
import CF7AppsNotice from "../../components/CF7AppsNotice";
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

const SquareAdmin = ( { appSettings } ) => {
	const proBase = typeof CF7Apps !== "undefined" && CF7Apps.proRestBase ? CF7Apps.proRestBase : "";
	const assetsBase = typeof CF7Apps !== "undefined" && CF7Apps.proSquareAssetsURL ? CF7Apps.proSquareAssetsURL : "";

	const [ isEnabled, setIsEnabled ] = useState( false );
	const [ environmentMode, setEnvironmentMode ] = useState( "live" );
	const [ defaultCurrency, setDefaultCurrency ] = useState( "" );
	const [ locationId, setLocationId ] = useState( "" );
	const [ locationIds, setLocationIds ] = useState( { live: "", test: "" } );
	const [ locationOptions, setLocationOptions ] = useState( {} );
	const [ locationOptionsByMode, setLocationOptionsByMode ] = useState( { live: {}, test: {} } );
	const [ currencyByMode, setCurrencyByMode ] = useState( { live: "", test: "" } );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ connection, setConnection ] = useState( null );
	const [ savedSettings, setSavedSettings ] = useState( {
		is_enabled: false,
		environment_mode: "live",
		default_currency: "",
		location_id: "",
		location_ids: { live: "", test: "" },
	} );

	const hydrateFromApi = useCallback( async () => {
		const data = await getApps( "square" );
		if ( ! data ) {
			return null;
		}

		let nextEnvironmentMode = "live";
		if ( data.square_settings ) {
			const mode = data.square_settings.environment_mode === "test" ? "test" : "live";
			nextEnvironmentMode = mode;
			const nextLocationIds = data.square_settings.location_ids && typeof data.square_settings.location_ids === "object"
				? {
					live: data.square_settings.location_ids.live || "",
					test: data.square_settings.location_ids.test || "",
				}
				: { live: "", test: "" };
			const nextLocationOptionsByMode = data.square_settings.location_options_by_mode && typeof data.square_settings.location_options_by_mode === "object"
				? {
					live: data.square_settings.location_options_by_mode.live || {},
					test: data.square_settings.location_options_by_mode.test || {},
				}
				: { live: {}, test: {} };
			const nextCurrencyByMode = data.square_settings.default_currency_by_mode && typeof data.square_settings.default_currency_by_mode === "object"
				? {
					live: data.square_settings.default_currency_by_mode.live || "",
					test: data.square_settings.default_currency_by_mode.test || "",
				}
				: { live: "", test: "" };

			const nextLocationId = nextLocationIds[ mode ] || data.square_settings.location_id || "";
			const nextDefaultCurrency = nextCurrencyByMode[ mode ] || data.square_settings.default_currency || "";

			setIsEnabled( !! data.square_settings.is_enabled );
			setEnvironmentMode( mode );
			setLocationIds( nextLocationIds );
			setLocationOptionsByMode( nextLocationOptionsByMode );
			setCurrencyByMode( nextCurrencyByMode );
			setLocationId( nextLocationId );
			setLocationOptions( nextLocationOptionsByMode[ mode ] || data.square_settings.location_options || {} );
			setDefaultCurrency( nextDefaultCurrency );
			setSavedSettings( {
				is_enabled: !! data.square_settings.is_enabled,
				environment_mode: mode,
				default_currency: nextDefaultCurrency,
				location_id: nextLocationId,
				location_ids: nextLocationIds,
			} );
		}
		const nextConnection = data.square_connection || null;
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

	const locationSelectOptions = useMemo( () => {
		const raw = locationOptions && typeof locationOptions === "object" ? locationOptions : {};
		return { "": __( "Select a location", "cf7apps" ), ...raw };
	}, [ locationOptions ] );

	const applyEnvironmentMode = ( mode ) => {
		setEnvironmentMode( mode );
		setLocationId( locationIds[ mode ] || "" );
		setLocationOptions( locationOptionsByMode[ mode ] || {} );
		setDefaultCurrency( currencyByMode[ mode ] || "" );
	};

	const onLocationChange = ( value ) => {
		setLocationId( value );
		setLocationIds( ( prev ) => ( {
			...prev,
			[ environmentMode ]: value,
		} ) );
	};

	const onSave = async () => {
		setIsSaving( true );
		const nextLocationIds = {
			...locationIds,
			[ environmentMode ]: locationId,
		};
		const ok = await saveSettings( "square", {
			is_enabled: isEnabled,
			environment_mode: environmentMode,
			default_currency: defaultCurrency,
			location_id: locationId,
			location_ids: nextLocationIds,
		} );
		setIsSaving( false );
		if ( ok ) {
			setSavedSettings( {
				is_enabled: isEnabled,
				environment_mode: environmentMode,
				default_currency: defaultCurrency,
				location_id: locationId,
				location_ids: nextLocationIds,
			} );
			toast.success( __( "Great! Settings Saved Successfully", "cf7apps" ) );
			hydrateFromApi();
		} else {
			toast.error( __( "Error! Something Went Wrong", "cf7apps" ) );
		}
	};

	const connectSquare = async () => {
		if ( ! canConnect ) {
			return;
		}
		if ( ! proBase ) {
			toast.error( __( "CF7 Apps Pro is not active.", "cf7apps" ) );
			return;
		}
		const url = `${ proBase }square/connect-url?mode=${ encodeURIComponent( environmentMode ) }`;
		const res = await fetch( url, { headers: { "X-WP-Nonce": CF7Apps.nonce } } );
		const json = await res.json().catch( () => ( {} ) );
		if ( ! res.ok || ! json.url ) {
			toast.error( json.message || __( "Could not start Square connection.", "cf7apps" ) );
			return;
		}
		window.location.href = json.url;
	};

	const disconnectSquare = async () => {
		if ( ! proBase ) {
			return;
		}
		setIsSaving( true );
		const res = await fetch( `${ proBase }square/disconnect`, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": CF7Apps.nonce,
			},
			body: JSON.stringify( { mode: environmentMode } ),
		} );
		setIsSaving( false );
		if ( res.ok ) {
			toast.success( __( "Disconnected from Square.", "cf7apps" ) );
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
		const st = getOAuthReturnStatus( "cf7apps_square" );
		if ( st === "connected" ) {
			toast.success( __( "Square account connected.", "cf7apps" ) );
			clearOAuthReturnStatus( "cf7apps_square" );
			hydrateFromApi().then( ( snapshot ) => {
				if ( snapshot ) {
					showNoticeAfterAuth( snapshot.environmentMode, snapshot.connection );
				}
			} );
		} else if ( st === "error" ) {
			toast.error( __( "Connection failed. Please try again.", "cf7apps" ) );
			clearOAuthReturnStatus( "cf7apps_square" );
		}
	}, [ hydrateFromApi, showNoticeAfterAuth ] );

	const squareConnectMarkSrc = assetsBase ? `${ assetsBase }square-connect-mark.svg` : "";
	const dashboardUrl = connection?.dashboard_url || "https://squareup.com/dashboard";

	return (
		<div className="cf7apps-stripe-admin cf7apps-stripe-admin--square">
			<div className="cf7apps-stripe-admin__card">
				<PaymentGatewayEnvironmentNotices
					showTestNotice={ showTestNotice }
					showLiveNotice={ showLiveNotice }
					gatewayLabel={ __( "Square", "cf7apps" ) }
					onSwitchToLiveMode={ () => applyModeSelection( "live", applyEnvironmentMode ) }
					onSwitchToTestMode={ () => applyModeSelection( "test", applyEnvironmentMode ) }
				/>
				{ environmentMode === "test" ? (
					<CF7AppsNotice
						type="info"
						text={ __(
							"Square Sandbox and test transactions are not shown in Payment Logs. Only live Square payments are recorded under Entries &gt; Payment Logs.",
							"cf7apps"
						) }
					/>
				) : null }

				<div className="cf7apps-stripe-admin__fields">
					<div className="cf7apps-stripe-admin__field">
						<CF7AppsFormToggle
							label={ __( "Enable Square Integration", "cf7apps" ) }
							help={ __( "Accept payments in Contact Form 7 using Square", "cf7apps" ) }
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
									name="cf7apps_square_env"
									checked={ environmentMode === "live" }
									onChange={ () => applyModeSelection( "live", applyEnvironmentMode ) }
								/>
								<span className="cf7apps-stripe-admin__radio-label">
									<strong>{ __( "Live Mode", "cf7apps" ) }</strong>
									{ " " }
									<span className="cf7apps-stripe-admin__radio-label-note">
										{ __( "(Process real transactions against your Square account)", "cf7apps" ) }
									</span>
								</span>
							</label>
							<label className="cf7apps-stripe-admin__radio-row">
								<input
									type="radio"
									name="cf7apps_square_env"
									checked={ environmentMode === "test" }
									onChange={ () => applyModeSelection( "test", applyEnvironmentMode ) }
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
						<div className="cf7apps-stripe-admin__label">{ __( "Default Currency", "cf7apps" ) }</div>
						<input
							type="text"
							className="cf7apps-stripe-admin__readonly-input"
							value={ defaultCurrency }
							readOnly
							disabled
							placeholder={ __( "Connect Square to sync currency", "cf7apps" ) }
							aria-label={ __( "Default currency (read-only)", "cf7apps" ) }
						/>
						<p className="cf7apps-stripe-admin__field-help">
							{ __(
								"The currency is automatically synced with your Square account and reflects the default currency configured in your merchant dashboard.",
								"cf7apps"
							) }
						</p>
					</div>

					<div className="cf7apps-stripe-admin__field cf7apps-stripe-admin__field--currency">
						<CF7AppsSelectField
							key={ `square-location-${ environmentMode }-${ Object.keys( locationOptions ).join( "," ) }` }
							label={ __( "Location", "cf7apps" ) }
							name="location_id"
							options={ locationSelectOptions }
							selected={ locationId }
							onChange={ ( e ) => onLocationChange( e.target.value ) }
							className="cf7apps-stripe-admin__select"
							variant="app"
						/>
						<p className="cf7apps-stripe-admin__field-help">
							{ __(
								"Your selected Square location is used to process payments. If you have multiple locations, choose the one you want to associate with this form or site.",
								"cf7apps"
							) }
						</p>
					</div>

					<div className="cf7apps-stripe-admin__field">
						<div className="cf7apps-stripe-admin__label">{ __( "Square Connection", "cf7apps" ) }</div>
						<div className="cf7apps-stripe-admin__connect-row">
							{ showConnectButton && (
								<button
									type="button"
									className="cf7apps-stripe-admin__connect-btn cf7apps-stripe-admin__connect-btn--square"
									onClick={ connectSquare }
									disabled={ ! canConnect }
								>
									<span className="cf7apps-stripe-admin__connect-inner">
										{ squareConnectMarkSrc ? (
											<img
												src={ squareConnectMarkSrc }
												alt=""
												className="cf7apps-stripe-admin__connect-logo cf7apps-stripe-admin__connect-logo--square"
												width="18"
												height="18"
												decoding="async"
											/>
										) : null }
										<span className="cf7apps-stripe-admin__connect-text">
											{ __( "Connect to Square", "cf7apps" ) }
										</span>
									</span>
								</button>
							) }
							{ connectedNow && (
								<PaymentGatewayConnectionStatus
									onDisconnect={ disconnectSquare }
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
								href={ dashboardUrl }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( "Visit Square Dashboard", "cf7apps" ) }
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

export default SquareAdmin;
