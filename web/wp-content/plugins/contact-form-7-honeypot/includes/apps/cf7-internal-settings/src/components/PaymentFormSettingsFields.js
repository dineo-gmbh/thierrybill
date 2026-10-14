import { Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import parse from 'html-react-parser';
import CF7AppsNotice from '../../../../../src/components/CF7AppsNotice';
import CF7AppsFormNotice from '../components/CF7AppsFormNotice';
import CF7AppsTextField from '../../../../../src/components/CF7AppsTextField';
import CF7AppsHelpText from '../../../../../src/components/CF7AppsHelpText';
import CF7AppsNumberField from '../../../../../src/components/CF7AppsNumberField';
import CF7AppsFormToggle from './CF7AppsFormToggle';
import CF7AppsSelectField from '../../../../../src/components/CF7AppsSelectField';
import CF7AppsRadioField from '../../../../../src/components/CF7AppsRadioField';
import CF7AppsFieldInfoTooltip from './CF7AppsFieldInfoTooltip';

const SQUARE_PAYMENT_SETTINGS_KEYS = [
	'customer_email',
	'default_currency',
	'amount_field',
	'quantity_field',
	'product_name',
	'product_description',
];

const isRequiredPaymentField = ( fieldKey, field ) =>
	Boolean( field?.required );

const AccordionChevron = ( { isOpen = false } ) => (
	<span
		className={
			isOpen
				? 'cf7apps-payment-collapsible__chevron is-open'
				: 'cf7apps-payment-collapsible__chevron'
		}
		aria-hidden="true"
	/>
);

const CollapsibleSection = ( {
	sectionKey,
	field,
	openMap,
	setOpenMap,
	disabled,
	children,
} ) => {
	const defaultOpen =
		field.default_open !== undefined
			? !! field.default_open
			: ! field.collapsible;

	const open =
		openMap && openMap[ sectionKey ] !== undefined
			? openMap[ sectionKey ]
			: defaultOpen;

	const toggleOpen = () => {
		if ( disabled ) {
			return;
		}

		setOpenMap( ( prev ) => ( {
			...prev,
			[ sectionKey ]:
				prev && prev[ sectionKey ] !== undefined
					? ! prev[ sectionKey ]
					: ! defaultOpen,
		} ) );
	};

	return (
		<div className="cf7apps-payment-collapsible">
			<button
				type="button"
				className="cf7apps-payment-collapsible__trigger"
				onClick={ toggleOpen }
				disabled={ disabled }
				aria-expanded={ !! open }
			>
				<span className="cf7apps-payment-collapsible__title">
					{ field.title }
				</span>
				<AccordionChevron isOpen={ open } />
			</button>
			{ open && (
				<div className="cf7apps-payment-collapsible__body">
					{ field.description ? (
						<p className="cf7apps-payment-collapsible__description">
							{ field.description }
						</p>
					) : null }
					{ children }
				</div>
			) }
		</div>
	);
};

const PaymentTextField = ( {
	fieldKey,
	field,
	value,
	onChange,
	required = false,
	readOnly = false,
	infoTooltip = false,
} ) => {
	const description =
		field.description != null ? String( field.description ) : '';
	const showInfoTooltip = infoTooltip && description;

	return (
	<div className="cf7apps-payment-field">
		<div className="cf7apps-payment-field__label-row">
			<label className="cf7apps-payment-field__label" htmlFor={ fieldKey }>
				{ field.title }
				{ required ? (
					<span className="cf7apps-payment-field__required">*</span>
				) : null }
			</label>
			{ showInfoTooltip ? (
				<CF7AppsFieldInfoTooltip text={ description } />
			) : null }
		</div>
		<input
			id={ fieldKey }
			type="text"
			className="cf7apps-form-input cf7apps-payment-field__input"
			name={ fieldKey }
			value={ value ?? '' }
			onChange={ onChange }
			placeholder={ field.placeholder || '' }
			required={ required }
			readOnly={ readOnly }
			disabled={ field.disabled }
		/>
		{ ! showInfoTooltip ? (
			<CF7AppsHelpText
				description={ parse( description ) }
			/>
		) : null }
	</div>
	);
};

const PaymentCheckboxField = ( {
	fieldKey,
	field,
	checked,
	disabled,
	onChange,
} ) => (
	<label
		className={ `cf7apps-payment-checkbox-row${
			checked ? ' is-checked' : ''
		}${ disabled ? ' is-disabled' : '' }` }
	>
		<input
			type="checkbox"
			className="cf7apps-payment-checkbox-row__input"
			name={ fieldKey }
			checked={ !! checked }
			disabled={ disabled }
			onChange={ ( event ) => onChange( event.target.checked ) }
		/>
		<span className="cf7apps-payment-checkbox-row__box" aria-hidden="true">
			<svg
				className="cf7apps-payment-checkbox-row__icon"
				width="10"
				height="8"
				viewBox="0 0 10 8"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					d="M1 4L3.5 6.5L9 1"
					stroke="currentColor"
					strokeWidth="1.5"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		</span>
		<span className="cf7apps-payment-checkbox-row__label">
			{ field.title }
		</span>
	</label>
);

const isPaymentFormEnabled = ( value ) =>
	value === true ||
	value === 1 ||
	value === '1' ||
	value === 'true';

const resolvePaymentCurrencyValue = ( formData, currencyField, globalDefaultCurrency = '' ) => {
	const fromForm = formData?.currency ? String( formData.currency ).trim() : '';
	if ( fromForm ) {
		return fromForm;
	}

	const fromSelected = currencyField?.selected
		? String( currencyField.selected ).trim()
		: '';
	if ( fromSelected ) {
		return fromSelected;
	}

	const fromDefault = currencyField?.default
		? String( currencyField.default ).trim()
		: '';
	if ( fromDefault ) {
		return fromDefault;
	}

	return globalDefaultCurrency ? String( globalDefaultCurrency ).trim() : '';
};

const PaymentFormSettingsFields = ( {
	app,
	fields,
	formData,
	setFormData,
	openMap,
	setOpenMap,
	fieldErrors = {},
	handleInputChange,
	saveAppSettings,
	isSaving,
	notice,
	isUnsavedForm = false,
	globalDefaultCurrency = '',
	enabledFormPaymentGateway = '',
	enabledFormPaymentGatewayLabel = '',
} ) => {
	const normalizedFields = { ...fields };
	if ( ! normalizedFields.accordion_payment && normalizedFields.customer_email ) {
		const paymentChildKeys = ( app === 'square'
			? [
					'customer_email',
					'default_currency',
					'amount_field',
					'quantity_field',
					'product_name',
					'product_description',
			  ]
			: [
					'customer_email',
					'currency',
					'amount_field',
					'quantity_field',
					'product_name',
					'product_description',
			  ]
		).filter( ( k ) => !! normalizedFields[ k ] );

		const reordered = {};
		Object.keys( normalizedFields ).forEach( ( key ) => {
			if ( key === 'customer_email' ) {
				reordered.accordion_payment = {
					type: 'collapsible_section',
					title: __( 'Payment Settings', 'cf7apps' ),
					collapsible: true,
					default_open: true,
					child_keys: paymentChildKeys,
				};
			}
			reordered[ key ] = normalizedFields[ key ];
		} );
		Object.keys( reordered ).forEach( ( k ) => {
			normalizedFields[ k ] = reordered[ k ];
		} );
	}

	const childKeySet = new Set();

	Object.keys( normalizedFields ).forEach( ( key ) => {
		const field = normalizedFields[ key ];
		if (
			field &&
			field.type === 'collapsible_section' &&
			Array.isArray( field.child_keys )
		) {
			field.child_keys.forEach( ( childKey ) => childKeySet.add( childKey ) );
		}
	} );

	const isHiddenByPaymentRules = ( fieldKey, field ) => {
		if ( ! field ) {
			return true;
		}

		const paymentEnabled = isPaymentFormEnabled( formData.is_enabled );

		if (
			! paymentEnabled &&
			fieldKey !== 'notice' &&
			fieldKey !== 'is_enabled' &&
			field.type !== 'save_button'
		) {
			return true;
		}

		return false;
	};

	const descHtml = ( field ) =>
		parse( String( field.description != null ? field.description : '' ) );

	const renderField = ( fieldKey ) => {
		const field = normalizedFields[ fieldKey ];

		if ( ! field || field.type === 'collapsible_section' ) {
			return null;
		}

		if ( isHiddenByPaymentRules( fieldKey, field ) ) {
			return null;
		}

		if ( fieldKey === 'tax_behavior' ) {
			return null;
		}

		const className = field.class || '';
		const help = field.help;
		const placeholder = field.placeholder || '';

		if (
			( app === 'stripe' || app === 'paypal' ) &&
			fieldKey === 'currency'
		) {
			return null;
		}

		if (
			( app === 'stripe' || app === 'paypal' ) &&
			fieldKey === 'customer_email'
		) {
			const currencyField = normalizedFields.currency;
			const currencyValue = resolvePaymentCurrencyValue(
				formData,
				currencyField,
				globalDefaultCurrency
			);

			return (
				<div
					key="payment-email-currency-row"
					className="cf7apps-payment-email-currency-row"
				>
					<div className="cf7apps-payment-field">
						<div className="cf7apps-payment-field__label-row">
							<label
								className="cf7apps-payment-field__label"
								htmlFor="customer_email"
							>
								{ field.title }
								{ isRequiredPaymentField( 'customer_email', field ) ? (
									<span className="cf7apps-payment-field__required">*</span>
								) : null }
							</label>
						</div>
						<input
							id="customer_email"
							type="text"
							value={ formData.customer_email || '' }
							onChange={ handleInputChange }
							name="customer_email"
							className="cf7apps-form-input cf7apps-payment-field__input"
							placeholder={ field.placeholder }
							required={ isRequiredPaymentField( 'customer_email', field ) }
							disabled={ field.disabled }
						/>
						{ field.description ? (
							<CF7AppsHelpText
								description={ descHtml( field ) }
							/>
						) : null }
					</div>
					{ currencyField ? (
						<div className="cf7apps-payment-field">
							<div className="cf7apps-payment-field__label-row">
								<label
									className="cf7apps-payment-field__label"
									htmlFor="currency"
								>
									{ currencyField.title }
									{ isRequiredPaymentField( 'currency', currencyField ) ? (
										<span className="cf7apps-payment-field__required">*</span>
									) : null }
								</label>
							</div>
							<select
								id="currency"
								className="cf7apps-form-input cf7apps-payment-field__input"
								name="currency"
								onChange={ handleInputChange }
								value={ currencyValue }
								disabled={ currencyField.disabled }
							>
								{ Object.keys( currencyField.options || {} ).map(
									( key ) => (
										<option key={ key } value={ key }>
											{ currencyField.options[ key ] }
										</option>
									)
								) }
							</select>
							{ currencyField.description ? (
								<CF7AppsHelpText
									description={ descHtml( currencyField ) }
								/>
							) : null }
						</div>
					) : null }
				</div>
			);
		}

		if ( app === 'square' && fieldKey === 'customer_email' ) {
			return (
				<PaymentTextField
					key={ fieldKey }
					fieldKey={ fieldKey }
					field={ field }
					value={ formData.customer_email || '' }
					onChange={ handleInputChange }
					required={ isRequiredPaymentField( fieldKey, field ) }
					infoTooltip
				/>
			);
		}

		if ( app === 'square' && fieldKey === 'default_currency' ) {
			const currencyValue =
				formData.default_currency ??
				field?.value ??
				field?.placeholder ??
				'';

			return (
				<PaymentTextField
					key={ fieldKey }
					fieldKey={ fieldKey }
					field={ field }
					value={ currencyValue }
					onChange={ handleInputChange }
					required={ isRequiredPaymentField( fieldKey, field ) }
					readOnly
				/>
			);
		}

		if ( field.type === 'notice' ) {
			return null;
		}

		if ( field.type === 'text' ) {
			if ( app === 'square' ) {
				return (
					<PaymentTextField
						key={ fieldKey }
						fieldKey={ fieldKey }
						field={ field }
						value={ formData[ fieldKey ] }
						onChange={ handleInputChange }
						required={ isRequiredPaymentField( fieldKey, field ) }
					/>
				);
			}

			const inputClassName = className.replace( /\blg\b/g, '' ).trim();
			const hasFieldError = Boolean( fieldErrors[ fieldKey ] );
			const isRedirectUrlField =
				fieldKey === 'success_url' || fieldKey === 'failure_url';

			return (
				<div
					key={ fieldKey }
					className={
						hasFieldError
							? 'cf7apps-payment-field cf7apps-payment-field--invalid'
							: 'cf7apps-payment-field'
					}
				>
					<CF7AppsTextField
						label={ field.title }
						description={ descHtml( field ) }
						className={ `cf7apps-payment-text-field ${ inputClassName }${
							hasFieldError ? ' is-invalid' : ''
						}` }
						placeholder={ placeholder }
						value={ formData[ fieldKey ] }
						name={ fieldKey }
						onChange={ handleInputChange }
						required={ isRequiredPaymentField( fieldKey, field ) }
						disabled={ field.disabled }
					/>
					{ hasFieldError && isRedirectUrlField ? (
						<p className="cf7apps-payment-field__error">
							{ fieldKey === 'success_url'
								? __( 'Success URL is required.', 'cf7apps' )
								: __( 'Failure URL is required.', 'cf7apps' ) }
						</p>
					) : null }
				</div>
			);
		}

		if ( field.type === 'number' ) {
			return (
				<CF7AppsNumberField
					key={ fieldKey }
					label={ field.title }
					description={ descHtml( field ) }
					className={ className }
					name={ fieldKey }
					placeholder={ placeholder }
					value={ formData[ fieldKey ] }
					onChange={ handleInputChange }
					disabled={ field.disabled }
					min={ field.min }
				/>
			);
		}

		if ( field.type === 'checkbox' ) {
			const checkboxKeys = [
				'billing_address_required',
				'phone_required',
				'promotion_codes',
				'terms_of_service',
				'automatic_taxes',
			];

			if ( checkboxKeys.includes( fieldKey ) ) {
				if ( fieldKey === 'automatic_taxes' ) {
					const taxBehaviorField = normalizedFields.tax_behavior;
					const taxesEnabled = isPaymentFormEnabled(
						formData.automatic_taxes
					);

					return (
						<div
							key="automatic_taxes_group"
							className="cf7apps-payment-tax-behavior-group"
						>
							<PaymentCheckboxField
								fieldKey={ fieldKey }
								field={ field }
								checked={ formData[ fieldKey ] }
								disabled={ field.disabled }
								onChange={ ( nextValue ) => {
									setFormData( ( prev ) => ( {
										...prev,
										[ fieldKey ]: nextValue,
									} ) );
								} }
							/>
							{ taxesEnabled && taxBehaviorField ? (
								<div className="cf7apps-payment-tax-behavior-field">
									<CF7AppsSelectField
										label={ taxBehaviorField.title }
										className={ `cf7apps-payment-select-field ${ taxBehaviorField.class || '' }` }
										name="tax_behavior"
										selected={
											formData.tax_behavior !== undefined
												? formData.tax_behavior
												: taxBehaviorField.selected
										}
										options={ taxBehaviorField.options }
										onChange={ handleInputChange }
										description={ descHtml( taxBehaviorField ) }
										disabled={ taxBehaviorField.disabled }
									/>
								</div>
							) : null }
						</div>
					);
				}

				return (
					<PaymentCheckboxField
						key={ fieldKey }
						fieldKey={ fieldKey }
						field={ field }
						checked={ formData[ fieldKey ] }
						disabled={ field.disabled }
						onChange={ ( nextValue ) => {
							setFormData( ( prev ) => ( {
								...prev,
								[ fieldKey ]: nextValue,
							} ) );
						} }
					/>
				);
			}

			return (
				<div key={ fieldKey } className="cf7apps-form-toggle-field">
					<CF7AppsFormToggle
						name={ fieldKey }
						label={ field.title }
						help={ help }
						className={ className }
						isSelected={ formData[ fieldKey ] }
						disabled={ field.disabled }
						onChange={ ( nextValue ) => {
							setFormData( ( prev ) => {
								const next = {
									...prev,
									[ fieldKey ]: nextValue,
								};

								if (
									fieldKey === 'is_enabled' &&
									nextValue &&
									( app === 'stripe' || app === 'paypal' )
								) {
									const currencyField = fields.currency;
									const resolvedCurrency = resolvePaymentCurrencyValue(
										prev,
										currencyField,
										globalDefaultCurrency
									);
									if ( resolvedCurrency ) {
										next.currency = resolvedCurrency;
									}
								}

								return next;
							} );
						} }
					/>
				</div>
			);
		}

		if ( field.type === 'select' ) {
			return (
				<CF7AppsSelectField
					key={ fieldKey }
					label={ field.title }
					className={ `cf7apps-payment-select-field ${ className }` }
					name={ fieldKey }
					selected={
						formData[ fieldKey ] !== undefined
							? formData[ fieldKey ]
							: field.selected
					}
					options={ field.options }
					onChange={ handleInputChange }
					description={ descHtml( field ) }
					disabled={ field.disabled }
				/>
			);
		}

		if ( field.type === 'save_button' ) {
			const formEditorAssets =
				CF7AppsInternalSettings?.formEditorAssetsURL ||
				`${ CF7AppsInternalSettings?.pluginURL || '' }includes/apps/cf7-internal-settings/assets/images/form-editor/`;
			const saveIcon = formEditorAssets
				? `${ formEditorAssets }save-icon.svg`
				: '';

			return (
				<div
					key={ fieldKey }
					className="cf7apps-form-group cf7apps-form-settings-save cf7apps-payment-form-save"
				>
					<Button
						className="cf7apps-btn tertiary-primary cf7apps-form-settings-save-btn"
						onClick={ saveAppSettings }
						isBusy={ isSaving }
						disabled={ field.disabled }
					>
						{ saveIcon ? (
							<img
								src={ saveIcon }
								alt=""
								width={ 18 }
								height={ 18 }
								className="cf7apps-form-settings-save-btn__icon"
								aria-hidden="true"
							/>
						) : null }
						{ field.text }
					</Button>
				</div>
			);
		}

		if ( field.type === 'radio' ) {
			const subKey = formData[ fieldKey ];
			let subField = field.sub_fields && field.sub_fields[ subKey ];

			if ( subField ) {
				subField =
					subField.type === 'select'
						? { ...subField, selected: formData[ subKey ] }
						: { ...subField, value: formData[ subKey ] };
			}

			return (
				<CF7AppsRadioField
					key={ fieldKey }
					label={ field.title }
					className={ className }
					options={ field.options }
					name={ fieldKey }
					onChange={ handleInputChange }
					value={ formData[ fieldKey ] }
					subFields={ subField }
					disabled={ field.disabled }
				/>
			);
		}

		return null;
	};

	const otherGatewayId =
		enabledFormPaymentGateway && enabledFormPaymentGateway !== app
			? enabledFormPaymentGateway
			: '';
	const otherGatewayLabel =
		enabledFormPaymentGatewayLabel || otherGatewayId;
	const isSwitchingGateway =
		otherGatewayId && isPaymentFormEnabled( formData.is_enabled );

	return (
		<div
			className={ `cf7apps-form cf7apps-payment-form-settings${
				app === 'square' ? ' cf7apps-square-settings' : ''
			}` }
		>
			<div className="MuiTabPanel-root">
				{ notice.show && ! isUnsavedForm ? (
					<CF7AppsNotice type="warning" text={ notice.text } />
				) : null }

				{ otherGatewayId && ! isPaymentFormEnabled( formData.is_enabled ) ? (
					<CF7AppsNotice
						type="warning"
						text={ sprintf(
							/* translators: %s: payment gateway name (Stripe, PayPal, Square) */
							__(
								'%s is already enabled for this form. Only one payment gateway can be active per form. Enable this gateway and save to switch.',
								'cf7apps'
							),
							otherGatewayLabel
						) }
					/>
				) : null }

				{ isSwitchingGateway ? (
					<CF7AppsNotice
						type="info"
						text={ sprintf(
							/* translators: %s: payment gateway name (Stripe, PayPal, Square) */
							__(
								'Saving will disable %s and enable this gateway for this form.',
								'cf7apps'
							),
							otherGatewayLabel
						) }
					/>
				) : null }

				{ Object.keys( normalizedFields ).map( ( fieldKey ) => {
					const field = normalizedFields[ fieldKey ];

					if ( childKeySet.has( fieldKey ) ) {
						return null;
					}

					if ( isHiddenByPaymentRules( fieldKey, field ) ) {
						return null;
					}

					if ( field.type === 'collapsible_section' ) {
						const accordionDisabled =
							( app === 'stripe' ||
								app === 'square' ||
								app === 'paypal' ) &&
							! isPaymentFormEnabled( formData.is_enabled );

						return (
							<CollapsibleSection
								key={ fieldKey }
								sectionKey={ fieldKey }
								field={ field }
								openMap={ openMap }
								setOpenMap={ setOpenMap }
								disabled={ accordionDisabled || field.disabled }
							>
								{ ( field.child_keys || [] ).map( ( childKey ) => (
									<Fragment key={ childKey }>
										{ renderField( childKey ) }
									</Fragment>
								) ) }
							</CollapsibleSection>
						);
					}

					return renderField( fieldKey );
				} ) }
			</div>
		</div>
	);
};

export default PaymentFormSettingsFields;
