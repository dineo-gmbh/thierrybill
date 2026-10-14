import { __ } from '@wordpress/i18n';

const POST_SMTP_SLUG = 'post-smtp';

const CF7AppsDomainAuthPostSmtpPromo = ( {
	plugin,
	busySlug,
	onInstall,
	assetsBase = '',
} ) => {
	if ( plugin?.status === 'active' ) {
		return null;
	}

	const iconUrl =
		plugin?.icon_url ||
		( assetsBase
			? `${ assetsBase }/images/dashboard/domain-auth/post-smtp.svg`
			: '' );

	const buttonLabel =
		plugin?.status === 'installed'
			? plugin.button_label || __( 'Activate', 'cf7apps' )
			: __( 'Install & Activate', 'cf7apps' );

	const isBusy = busySlug === POST_SMTP_SLUG;
	const canInstall = plugin?.can_install !== false;

	return (
		<article
			className="cf7apps-domain-auth__promo"
			aria-label={ __( 'Post SMTP promotion', 'cf7apps' ) }
		>
			<div className="cf7apps-domain-auth__promo-brand">
				{ iconUrl ? (
					<img
						className="cf7apps-domain-auth__promo-icon"
						src={ iconUrl }
						alt=""
						width={ 22 }
						height={ 23 }
					/>
				) : null }
				<h4 className="cf7apps-domain-auth__promo-title">
					{ plugin?.title || __( 'Post SMTP', 'cf7apps' ) }
				</h4>
			</div>
			<p className="cf7apps-domain-auth__promo-desc">
				{ __(
					'Fix Your WordPress Email Delivery Issues with Post SMTP',
					'cf7apps'
				) }
			</p>
			{ canInstall ? (
				<button
					type="button"
					className="cf7apps-domain-auth__promo-btn"
					disabled={ isBusy }
					onClick={ onInstall }
				>
					{ isBusy ? __( 'Please wait…', 'cf7apps' ) : buttonLabel }
				</button>
			) : (
				<span className="cf7apps-domain-auth__promo-btn cf7apps-domain-auth__promo-btn--disabled">
					{ buttonLabel }
				</span>
			) }
		</article>
	);
};

export default CF7AppsDomainAuthPostSmtpPromo;
