import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import CF7AppsSkeletonLoader from './CF7AppsSkeletonLoader';
import { fetchDashboardOverview } from '../api/api';

const formatStat = ( value ) => {
	const number = Number( value ) || 0;
	return number.toLocaleString();
};

const CF7AppsOverview = () => {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ stats, setStats ] = useState( {
		total_entries: 0,
		spam_detections: 0,
	} );

	const assetsBase = CF7Apps?.assetsURL || '';
	const icon = ( file ) =>
		assetsBase ? `${ assetsBase }/images/dashboard/overview/${ file }` : '';

	const loadStats = useCallback( async () => {
		setIsLoading( true );
		const data = await fetchDashboardOverview();
		setStats( {
			total_entries: data?.total_entries ?? 0,
			spam_detections: data?.spam_detections ?? 0,
		} );
		setIsLoading( false );
	}, [] );

	useEffect( () => {
		loadStats();
	}, [ loadStats ] );

	return (
		<section
			className="cf7apps-sidebar-block cf7apps-overview"
			aria-label={ __( 'Overview', 'cf7apps' ) }
		>
			<h3 className="cf7apps-sidebar-block__title cf7apps-overview__title">
				{ __( 'Overview', 'cf7apps' ) }
			</h3>
			{ isLoading ? (
				<div className="cf7apps-overview__cards">
					<CF7AppsSkeletonLoader height={ 126 } width="100%" />
					<CF7AppsSkeletonLoader height={ 126 } width="100%" />
				</div>
			) : (
				<div className="cf7apps-overview__cards">
					<article className="cf7apps-overview-card">
						<div className="cf7apps-overview-card__head">
							<div className="cf7apps-overview-card__icon-wrap">
								<img
									className="cf7apps-overview-card__icon"
									src={ icon( 'total-entries.png' ) }
									alt=""
									width={ 35 }
									height={ 35 }
								/>
							</div>
							<p className="cf7apps-overview-card__label">
								{ __( 'Total Form Entries', 'cf7apps' ) }
							</p>
						</div>
						<p className="cf7apps-overview-card__value">
							{ formatStat( stats.total_entries ) }
						</p>
					</article>
					<article className="cf7apps-overview-card">
						<div className="cf7apps-overview-card__head">
							<div className="cf7apps-overview-card__icon-wrap">
								<img
									className="cf7apps-overview-card__icon"
									src={ icon( 'spam-detections.png' ) }
									alt=""
									width={ 35 }
									height={ 35 }
								/>
							</div>
							<p className="cf7apps-overview-card__label">
								{ __( 'Spam Detections', 'cf7apps' ) }
							</p>
						</div>
						<p className="cf7apps-overview-card__value">
							{ formatStat( stats.spam_detections ) }
						</p>
					</article>
				</div>
			) }
		</section>
	);
};

export default CF7AppsOverview;
