import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { toast } from 'react-toastify';
import CF7AppsFormToggle from '../../components/CF7AppsFormToggle';
import CF7AppsTextField from '../../components/CF7AppsTextField';
import { saveSettings } from '../../api/api';

const HELP_TEXT = __(
	'Enable automated generation of weekly summary reports for Contact Form 7 entries, delivered directly to users.',
	'cf7apps'
);

const RECIPIENTS_HELP = __(
	'Optional. Comma-separated email addresses that will also receive the weekly summary report. The admin email is always included.',
	'cf7apps'
);

const WeeklySummaryAdmin = ( { formData } ) => {
	const [ isEnabled, setIsEnabled ] = useState( false );
	const [ additionalRecipients, setAdditionalRecipients ] = useState( '' );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		if ( formData?.is_enabled !== undefined ) {
			setIsEnabled( !! formData.is_enabled );
		}
	}, [ formData?.is_enabled ] );

	useEffect( () => {
		if ( formData?.additional_recipients !== undefined ) {
			setAdditionalRecipients( formData.additional_recipients || '' );
		}
	}, [ formData?.additional_recipients ] );

	const handleSave = async () => {
		setIsSaving( true );

		const saved = await saveSettings( 'weekly-summary', {
			is_enabled: isEnabled,
			additional_recipients: additionalRecipients.trim(),
		} );

		setIsSaving( false );

		if ( saved ) {
			toast.success( __( 'Settings saved successfully.', 'cf7apps' ) );
			return;
		}

		toast.error( __( 'Error! Something Went Wrong', 'cf7apps' ) );
	};

	const saveIcon = window.CF7Apps?.assetsURL
		? `${ window.CF7Apps.assetsURL }/images/dashboard/save-icon.svg`
		: '';

	return (
		<>
			<CF7AppsFormToggle
				label={ __( 'Enable Email Summary Report', 'cf7apps' ) }
				isSelected={ isEnabled }
				onChange={ setIsEnabled }
				help={ HELP_TEXT }
			/>
			<CF7AppsTextField
				label={ __( 'Additional Recipients', 'cf7apps' ) }
				value={ additionalRecipients }
				onChange={ ( event ) => setAdditionalRecipients( event.target.value ) }
				name="additional_recipients"
				placeholder={ __( 'email1@example.com, email2@example.com', 'cf7apps' ) }
				description={ RECIPIENTS_HELP }
				variant="app"
			/>
			<div className="cf7apps-form-group cf7apps-form-settings-save">
				<Button
					className="cf7apps-btn tertiary-primary"
					onClick={ handleSave }
					isBusy={ isSaving }
				>
					{ saveIcon && (
						<img
							src={ saveIcon }
							alt=""
							width={ 18 }
							height={ 18 }
							aria-hidden="true"
						/>
					) }
					{ __( 'Save Settings', 'cf7apps' ) }
				</Button>
			</div>
		</>
	);
};

export default WeeklySummaryAdmin;

