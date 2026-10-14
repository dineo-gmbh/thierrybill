import { Link } from 'react-router';
import { __, sprintf } from '@wordpress/i18n';
import {
	getAppSettingsHeaderIcon,
	getCF7AppsAssetsURL,
} from '../utils/appCardTheme';

const BackChevronIcon = () => (
	<svg
		className="cf7apps-app-settings-header__back-icon"
		width="7"
		height="12"
		viewBox="0 0 7 12"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path
			d="M6 1L1 6L6 11"
			stroke="#0D2149"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</svg>
);

const CF7AppsAppSettingsHeader = ( {
	appSettings,
	backTo = '/',
	backLabel = __( 'Back to Dashboard', 'cf7apps' ),
	assetsBase = getCF7AppsAssetsURL(),
	showBack = true,
	title,
	subtitle,
	showDescription = true,
	documentationUrl,
} ) => {
	const settingsTitle =
		title ??
		( appSettings?.id === 'acf-integration'
			? appSettings.title
			: sprintf( __( '%s Settings', 'cf7apps' ), appSettings?.title || '' ) );
	const docUrl = documentationUrl ?? appSettings?.documentation_url;
	const iconSrc = getAppSettingsHeaderIcon( appSettings, assetsBase );

	return (
		<header className="cf7apps-app-settings-header">
			<div className="cf7apps-app-settings-header__top">
				<div className="cf7apps-app-settings-header__brand">
					{ iconSrc && (
						<img
							className="cf7apps-app-settings-header__icon"
							src={ iconSrc }
							alt=""
							width={ 70 }
							height={ 70 }
						/>
					) }
					<div className="cf7apps-app-settings-header__copy">
						{ subtitle ? (
							<div className="cf7apps-app-settings-header__heading">
								<div
									className="cf7apps-app-settings-header__title"
									role="heading"
									aria-level="2"
								>
									{ settingsTitle }
								</div>
								<div className="cf7apps-app-settings-header__subtitle">
									{ subtitle }
								</div>
							</div>
						) : (
							<h2 className="cf7apps-app-settings-header__title">
								{ settingsTitle }
							</h2>
						) }
						{ showDescription && appSettings?.description && (
							<p className="cf7apps-app-settings-header__desc">
								<span className="cf7apps-app-settings-header__desc-text">
									{ appSettings.description }
								</span>
								{ docUrl && (
									<>
										{ ' ' }
										<a
											className="cf7apps-app-settings-header__doc-link"
											href={ docUrl }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __( 'Documentation', 'cf7apps' ) }
										</a>
									</>
								) }
							</p>
						) }
					</div>
				</div>
				{ showBack && (
					<Link
						to={ backTo }
						className="cf7apps-app-settings-header__back-pill"
						aria-label={ backLabel }
					>
						<BackChevronIcon />
						<span className="cf7apps-app-settings-header__back-label">
							{ backLabel }
						</span>
					</Link>
				) }
			</div>
			<div className="cf7apps-app-settings-header__divider" aria-hidden="true" />
		</header>
	);
};

export default CF7AppsAppSettingsHeader;
