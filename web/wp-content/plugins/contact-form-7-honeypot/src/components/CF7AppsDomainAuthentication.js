import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import CF7AppsSkeletonLoader from './CF7AppsSkeletonLoader';
import CF7AppsDomainAuthPostSmtpPromo from './CF7AppsDomainAuthPostSmtpPromo';
import {
	fetchDomainAuthentication,
	getRecommendedPlugins,
	installActivateRecommendedPlugin,
} from '../api/api';

const POST_SMTP_SLUG = 'post-smtp';

const CF7AppsDomainAuthentication = () => {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ authData, setAuthData ] = useState( null );
	const [ postSmtpPlugin, setPostSmtpPlugin ] = useState( null );
	const [ busySlug, setBusySlug ] = useState( '' );

	const assetsBase = CF7Apps?.assetsURL || '';
	const icon = ( file ) =>
		assetsBase ? `${ assetsBase }/images/dashboard/domain-auth/${ file }` : '';

	const loadData = useCallback( async ( refresh = false ) => {
		if ( refresh ) {
			setIsRefreshing( true );
		} else {
			setIsLoading( true );
		}

		const [ auth, plugins ] = await Promise.all( [
			fetchDomainAuthentication( refresh ),
			getRecommendedPlugins(),
		] );

		setAuthData( ( prev ) => {
			if ( ! auth ) {
				if ( refresh && prev ) {
					toast.error(
						__(
							'Could not refresh domain authentication. Showing previous results.',
							'cf7apps'
						)
					);
					return prev;
				}
				return null;
			}

			const domain =
				auth.domain && 'yourdomain.com' !== auth.domain
					? auth.domain
					: prev?.domain || auth.domain;

			return {
				...auth,
				domain,
			};
		} );

		const smtpPlugin = Array.isArray( plugins )
			? plugins.find( ( item ) => item.slug === POST_SMTP_SLUG )
			: null;
		setPostSmtpPlugin(
			smtpPlugin || {
				slug: POST_SMTP_SLUG,
				title: __( 'Post SMTP', 'cf7apps' ),
				status: 'not_installed',
				can_install: true,
				button_label: __( 'Install & Activate', 'cf7apps' ),
			}
		);

		setIsLoading( false );
		setIsRefreshing( false );
	}, [] );

	useEffect( () => {
		loadData();
	}, [ loadData ] );

	const handleRecheck = () => {
		loadData( true );
	};

	const handleInstallPostSmtp = async () => {
		if (
			! postSmtpPlugin ||
			postSmtpPlugin.status === 'active' ||
			! postSmtpPlugin.can_install
		) {
			return;
		}

		setBusySlug( POST_SMTP_SLUG );

		const result = await installActivateRecommendedPlugin( POST_SMTP_SLUG );

		setBusySlug( '' );

		if ( ! result || result.error ) {
			toast.error(
				result?.error ||
					__( 'Could not install or activate Post SMTP. Please try again.', 'cf7apps' )
			);
			return;
		}

		const updated = Array.isArray( result.data )
			? result.data.find( ( item ) => item.slug === POST_SMTP_SLUG )
			: null;
		setPostSmtpPlugin( updated || null );
		toast.success(
			postSmtpPlugin.status === 'installed'
				? __( 'Post SMTP activated successfully.', 'cf7apps' )
				: __( 'Post SMTP installed and activated successfully.', 'cf7apps' )
		);

		if ( postSmtpPlugin.redirect_url ) {
			setTimeout( () => {
				window.location.href = postSmtpPlugin.redirect_url;
			}, 450 );
			return;
		}

		loadData( true );
	};

	const getStatusLabel = ( check ) => {
		if ( 'smtp' === check.id && 'pass' === check.status ) {
			return __( 'CONNECTED', 'cf7apps' );
		}

		return 'pass' === check.status
			? __( 'PASS', 'cf7apps' )
			: __( 'FAIL', 'cf7apps' );
	};

	const showPostSmtpPromo =
		! isLoading && postSmtpPlugin && postSmtpPlugin.status !== 'active';

	return (
		<section
			className="cf7apps-sidebar-block cf7apps-domain-auth"
			aria-label={ __( 'Domain Authentication', 'cf7apps' ) }
		>
			<div className="cf7apps-domain-auth__header">
				<h3 className="cf7apps-sidebar-block__title cf7apps-domain-auth__title">
					{ __( 'Domain Authentication', 'cf7apps' ) }
				</h3>
				<button
					type="button"
					className="cf7apps-domain-auth__recheck"
					onClick={ handleRecheck }
					disabled={ isLoading || isRefreshing }
				>
					<img
						className="cf7apps-domain-auth__recheck-icon"
						src={ icon( 'recheck.svg' ) }
						alt=""
						width={ 10 }
						height={ 13 }
					/>
					<span>{ __( 'Re-check', 'cf7apps' ) }</span>
				</button>
			</div>

			{ isLoading ? (
				<>
					<CF7AppsSkeletonLoader height={ 34 } width="100%" />
					<CF7AppsSkeletonLoader height={ 223 } width="100%" />
					<CF7AppsSkeletonLoader height={ 115 } width="100%" />
				</>
			) : (
				<>
					<div className="cf7apps-domain-auth__score">
						<div className="cf7apps-domain-auth__score-row">
							<div
								className="cf7apps-domain-auth__domain"
								title={ authData?.domain || '' }
							>
								<img
									className="cf7apps-domain-auth__domain-icon"
									src={ icon( 'domain.svg' ) }
									alt=""
									width={ 15 }
									height={ 13 }
								/>
								<span>{ authData?.domain || '' }</span>
							</div>
							<p className="cf7apps-domain-auth__score-label">
								{ __( 'Deliverability score', 'cf7apps' ) }{ ' ' }
								<strong>{ authData?.deliverability_score ?? 0 }%</strong>
							</p>
						</div>
						<div
							className="cf7apps-domain-auth__progress"
							role="progressbar"
							aria-valuenow={ authData?.deliverability_score ?? 0 }
							aria-valuemin={ 0 }
							aria-valuemax={ 100 }
						>
							<div
								className="cf7apps-domain-auth__progress-fill"
								style={ {
									width: `${ authData?.deliverability_score ?? 0 }%`,
								} }
							/>
						</div>
					</div>

					<div className="cf7apps-domain-auth__checks">
						<ul className="cf7apps-domain-auth__check-list">
							{ ( authData?.checks || [] )
								.filter( ( check ) => check.id !== 'bimi' )
								.map( ( check ) => (
								<li
									key={ check.id }
									className="cf7apps-domain-auth__check-row"
								>
									<div className="cf7apps-domain-auth__check-label">
										<span className="cf7apps-domain-auth__check-icon-wrap">
											<img
												className="cf7apps-domain-auth__check-icon"
												src={ icon(
													'pass' === check.status
														? 'status-pass.svg'
														: 'status-fail.svg'
												) }
												alt=""
												width={ 13 }
												height={ 13 }
											/>
										</span>
										<span>{ check.label }</span>
									</div>
									<span
										className={
											'pass' === check.status
												? 'cf7apps-domain-auth__badge cf7apps-domain-auth__badge--pass'
												: 'cf7apps-domain-auth__badge cf7apps-domain-auth__badge--fail'
										}
									>
										{ getStatusLabel( check ) }
									</span>
								</li>
							) ) }
						</ul>

						{ showPostSmtpPromo && (
							<CF7AppsDomainAuthPostSmtpPromo
								plugin={ postSmtpPlugin }
								busySlug={ busySlug }
								onInstall={ handleInstallPostSmtp }
								assetsBase={ assetsBase }
							/>
						) }
					</div>
				</>
			) }
		</section>
	);
};

export default CF7AppsDomainAuthentication;
