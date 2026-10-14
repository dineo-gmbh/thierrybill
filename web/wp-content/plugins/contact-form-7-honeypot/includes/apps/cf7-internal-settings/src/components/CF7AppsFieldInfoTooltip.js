const CF7AppsFieldInfoTooltip = ( { text } ) => {
	const formEditorAssets =
		CF7AppsInternalSettings?.formEditorAssetsURL ||
		`${ CF7AppsInternalSettings?.pluginURL || '' }includes/apps/cf7-internal-settings/assets/images/form-editor/`;

	if ( ! text ) {
		return null;
	}

	return (
		<span className="cf7apps-payment-field__info-tooltip">
			<button
				type="button"
				className="cf7apps-payment-field__info-trigger"
				aria-label={ text }
			>
				<img
					src={ `${ formEditorAssets }info-icon.svg` }
					alt=""
					width={ 14 }
					height={ 14 }
					decoding="async"
				/>
			</button>
			<span className="cf7apps-payment-field__info-bubble" role="tooltip">
				{ text }
			</span>
		</span>
	);
};

export default CF7AppsFieldInfoTooltip;
