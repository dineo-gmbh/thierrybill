import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import CF7AppsSkeletonLoader from '../../components/CF7AppsSkeletonLoader';
import CF7AppsPopup from '../../components/CF7AppsPopup';
import { deleteCF7Entries, exportCF7Entries, getCF7Entries, getAllCF7Forms, deletePaymentLogs, getPaymentLogs } from '../../api/api';
import CF7AppsSelectField from '../../components/CF7AppsSelectField';
import CF7AppsTextField from "../../components/CF7AppsTextField";
import CF7AppsCalendar from '../../components/CF7AppsCalendar';
import PaymentTypeBadge from '../../components/PaymentTypeBadge';
import { getCF7AppsAssetsURL } from '../../utils/appCardTheme';
import { toast } from 'react-toastify';
import { DeleteOutlined, VisibilityOutlined, CalendarMonthOutlined } from '@mui/icons-material';
import { useNavigate } from 'react-router';

const getCf7AppsAdminUrl = () => {
	if ( typeof CF7Apps !== 'undefined' && CF7Apps.appIndexURL ) {
		return CF7Apps.appIndexURL;
	}

	return `${ window.location.origin }/wp-admin/admin.php?page=cf7apps`;
};

const getPaymentGatewaysUrl = () => `${ getCf7AppsAdminUrl() }#/payments`;

const hasCf7AppsPro = () => Boolean( typeof CF7Apps !== 'undefined' && CF7Apps.hasPro );

const getPaymentStatusClass = ( status ) => {
    const normalized = String( status || '' ).toLowerCase();
    if ( normalized === 'success' ) {
        return 'success';
    }
    if ( normalized === 'refunded' ) {
        return 'refunded';
    }
    return 'failed';
};

const formatPaymentAmount = ( currency, amount ) => {
    if ( ! currency ) {
        return amount;
    }
    const formattedAmount = Number( amount ).toLocaleString( undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    } );
    return `${ currency } ${ formattedAmount }`;
};

const getPaymentMethodKey = ( payment ) => {
    if ( ! payment ) {
        return '';
    }

    if ( payment.payment_method_key ) {
        return String( payment.payment_method_key ).toLowerCase();
    }

    const label = String( payment.payment_method || '' ).toLowerCase();
    if ( label.includes( 'square' ) ) {
        return 'square';
    }
    if ( label.includes( 'paypal' ) ) {
        return 'paypal';
    }
    if ( label.includes( 'stripe' ) ) {
        return 'stripe';
    }

    return '';
};

const getPaymentDashboardLink = ( payment ) => {
    if ( ! payment ) {
        return null;
    }

    const methodKey = getPaymentMethodKey( payment );
    const isTest = String( payment.environment || '' ).toLowerCase() === 'test';
    let url = payment.transaction_url || '';

    if ( ! url && payment.transaction_id ) {
        const encodedId = encodeURIComponent( payment.transaction_id );
        if ( methodKey === 'stripe' ) {
            url = `${ isTest ? 'https://dashboard.stripe.com/test/payments/' : 'https://dashboard.stripe.com/payments/' }${ encodedId }`;
        } else if ( methodKey === 'paypal' ) {
            url = `${ isTest ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com' }/activity/payment/${ encodedId }`;
        } else if ( methodKey === 'square' ) {
            url = `${ isTest ? 'https://squareupsandbox.com' : 'https://squareup.com' }/dashboard/sales/transactions/${ encodedId }`;
        }
    }

    if ( ! url ) {
        return null;
    }

    const labels = {
        stripe: __( 'Visit in Stripe Dashboard', 'cf7apps' ),
        paypal: __( 'Visit in PayPal Dashboard', 'cf7apps' ),
        square: __( 'Visit in Square Dashboard', 'cf7apps' ),
    };

    return {
        url,
        label: labels[ methodKey ] || __( 'Visit payment dashboard', 'cf7apps' ),
    };
};

/**
 * CF7Entries Component
 *
 * @since 3.1.0
 */
const Cf7Entries = ( { tabKey = 'entries' } ) => {
    const isPaymentLogsTab = tabKey === 'payment_logs';
    const navigate = useNavigate();

    const [ cf7entries, setCf7entries ]     = useState( false );
    const [ loading, setLoading ]           = useState( false );
    const [ showDetails, setShowDetails ]   = useState( false );
    const [ entryData, setEntryData ]       = useState( false );
    const [ totalEntries, setTotalEntries ] = useState( 0 );
    const [ currentPage, setCurrentPage ]   = useState( 1 );
    const [ searchParam, setSearchParam ]   = useState( { form_id: 0, search: '', payment_method: 'all' } );
    const [ forms, setForms ] = useState( {} );
    const [ selectedDate, setSelectedDate ] = useState( '' );
    const [ dateRange, setDateRange ] = useState( {
        startDate: new Date(),
        endDate: new Date(),
        key: 'selection',
    } );

    const [ logsState, setLogsState ] = useState( {
        logs: [],
        total: 0,
        entries_on: true,
        gateway_on: false,
        is_test_mode: false,
    } );
    const [ activePayment, setActivePayment ] = useState( null );
    const [ isExporting, setIsExporting ] = useState( false );
    const [ bulkAction, setBulkAction ] = useState( 'null' );
    const hasPro = hasCf7AppsPro();
    const lockLogsView = isPaymentLogsTab && hasPro && ! logsState.gateway_on;
    const assetsBase = getCF7AppsAssetsURL();

    const handleUnlockPaymentLogs = () => {
        if ( window.location.pathname.includes( 'admin.php' ) && window.location.search.includes( 'page=cf7apps' ) ) {
            navigate( '/payments' );
            return;
        }

        window.location.href = getPaymentGatewaysUrl();
    };

    const fetchEntries = async ( isInitial = false ) => {

        if ( isInitial ) {
            setLoading(true);
        }

        const entries  = await getCF7Entries();
        const cf7forms = await getAllCF7Forms();

        setCf7entries( entries.entries );
        setTotalEntries( entries.total );
        setCurrentPage( 1 );
        setForms( cf7forms );

        if ( isInitial ) {
            setLoading( false );
        }
    }

    const paymentMethodFilterOptions = {
        all: __( 'Filter', 'cf7apps' ),
        stripe: __( 'Stripe', 'cf7apps' ),
        paypal: __( 'PayPal', 'cf7apps' ),
        square: __( 'Square', 'cf7apps' ),
    };

    const fetchPaymentLogs = async ( isInitial = false, page = 1, filters = searchParam ) => {
        if ( isInitial ) {
            setLoading( true );
        }

        const logs = await getPaymentLogs( {
            page,
            perPage: 10,
            search: filters.search || '',
            payment_method: filters.payment_method || 'all',
        } );

        if ( logs ) {
            setLogsState( logs );
            setCurrentPage( page );
        } else {
            setLogsState( ( prev ) => ( {
                logs: [],
                total: 0,
                entries_on: true,
                gateway_on: false,
                is_test_mode: prev.is_test_mode,
            } ) );
        }

        if ( isInitial ) {
            setLoading( false );
        }
    }

    useEffect(() => {
        if ( isPaymentLogsTab ) {
            if ( hasPro ) {
                fetchPaymentLogs( true );
            } else {
                setLoading( false );
            }
        } else {
            fetchEntries( true );
        }
        const urlParams = new URLSearchParams(window.location.search);

        if ( urlParams.get('page') === 'cf7apps' && urlParams.get('tab') && urlParams.get('tab').startsWith('entries') ) {
            const submenuItems = document.querySelectorAll('#toplevel_page_wpcf7 .wp-submenu li');
            submenuItems.forEach(li => li.classList.remove('current'));
            const entriesMenu = Array.from(document.querySelectorAll('#toplevel_page_wpcf7 .wp-submenu a'))
                .find(link => link.textContent.trim() === 'Entries');
            if (entriesMenu) {
                entriesMenu.parentElement.classList.add('current');
            }
        }
    }, [ isPaymentLogsTab, hasPro ]);

    if ( isPaymentLogsTab && ! hasPro ) {
        return null;
    }

    let handleViewButton = ( e, data ) => {
        e.preventDefault();
        setShowDetails( true );
        setEntryData( data );
    };
    let handleDeleteButton = ( e, id ) => {
        e.preventDefault();

        if ( ! confirm( __( 'Are you sure you want to delete this entry?', 'cf7apps' ) ) ) {
            return;
        }

        const deleteRequest = isPaymentLogsTab ? deletePaymentLogs( [ id ] ) : deleteCF7Entries( [ id ] );
        deleteRequest
            .then( ( response ) => {
                if ( response ) {
                    toast.success( isPaymentLogsTab ? __( 'Payment log deleted successfully', 'cf7apps' ) : __( 'Entry deleted successfully', 'cf7apps' ) );
                    if ( isPaymentLogsTab ) {
                        const logs = logsState.logs.filter( log => log.id !== id );
                        setLogsState( {
                            ...logsState,
                            logs,
                            total: Math.max( 0, ( logsState.total || 0 ) - 1 ),
                        } );
                    } else {
                        const newEntries = cf7entries.filter( entry => entry.id !== id );
                        if ( ! newEntries.length ) {
                            fetchEntries();
                        } else {
                            setCf7entries( newEntries );
                        }
                    }
                } else {
                    toast.error( isPaymentLogsTab ? __( 'Failed to delete payment log', 'cf7apps' ) : __( 'Failed to delete entry', 'cf7apps' ) );
                }
            } )
            .catch( ( error ) => {
                toast.error( isPaymentLogsTab ? __( 'An error occurred while deleting the payment log', 'cf7apps' ) : __( 'An error occurred while deleting the entry', 'cf7apps' ) );
            } );
    }

    let checkAllEntries = ( e ) => {
        const checkboxes = document.querySelectorAll( '.cf7apps-entry-checkbox' );
        checkboxes.forEach( checkbox => {
            checkbox.checked = e.target.checked;
        } );
    }

    let handleBulkAction = ( action, entries ) => {
        if ( 'null' === action ) {
            toast.error( __( 'Please select an action', 'cf7apps' ) );
            return;
        }

        if ( 'export_csv' === action ) {
            handleExportEntries( entries );
            return;
        }

        if ( 'delete' === action ) {
            if ( ! confirm( __( 'Are you sure you want to delete the selected entries?', 'cf7apps' ) ) ) {
                return;
            }

            const bulkDeleteRequest = isPaymentLogsTab ? deletePaymentLogs( entries ) : deleteCF7Entries( entries );
            bulkDeleteRequest
                .then( ( response ) => {
                    if ( response ) {
                        toast.success( isPaymentLogsTab ? __( 'Payment logs deleted successfully', 'cf7apps' ) : __( 'Entries deleted successfully', 'cf7apps' ) );
                        if ( isPaymentLogsTab ) {
                            const newLogs = logsState.logs.filter( log => ! entries.includes( `${ log.id }` ) );
                            setLogsState( {
                                ...logsState,
                                logs: newLogs,
                                total: Math.max( 0, ( logsState.total || 0 ) - entries.length ),
                            } );
                        } else {
                            const newEntries = cf7entries.filter( entry => ! entries.includes( entry.id ) );
                            if ( ! newEntries.length ) {
                                fetchEntries();
                            } else {
                                setCf7entries( newEntries );
                            }
                        }
                    } else {
                        toast.error( isPaymentLogsTab ? __( 'Failed to delete payment logs', 'cf7apps' ) : __( 'Failed to delete entries', 'cf7apps' ) );
                    }
                } )
                .catch( ( error ) => {
                    toast.error( isPaymentLogsTab ? __( 'An error occurred while deleting payment logs', 'cf7apps' ) : __( 'An error occurred while deleting entries', 'cf7apps' ) );
                } );
        }


    }

    const handleExportEntries = async ( entryIds = [] ) => {
        setIsExporting( true );

        const exported = await exportCF7Entries( {
            entryIds,
            form_id: searchParam.form_id || 0,
            search: searchParam.search || '',
            start_date: searchParam.start_date || '',
            end_date: searchParam.end_date || '',
        } );

        setIsExporting( false );

        if ( exported ) {
            toast.success( __( 'CSV exported successfully.', 'cf7apps' ) );
        } else {
            toast.error( __( 'Failed to export CSV.', 'cf7apps' ) );
        }
    };

    let handleSearchFrom = ( type, value ) => {
        const nextParams = { ...searchParam };

        if ( 'form' === type ) {
            nextParams.form_id = value;
        }

        if ( 'text' === type ) {
            nextParams.search = value;
        }

        if ( 'payment_method' === type ) {
            nextParams.payment_method = value;
        }

        if ( 'daterange' === type ) {
            setDateRange( value );
            nextParams.start_date = value.startDate.toISOString().split('T')[0];
            nextParams.end_date   = value.endDate.toISOString().split('T')[0];
        }

        setSearchParam( nextParams );

        const entryParams = {
            page: currentPage,
            perPage: 10,
            search: nextParams.search,
            form_id: nextParams.form_id,
            start_date: nextParams.start_date,
            end_date: nextParams.end_date,
        };

        if ( isPaymentLogsTab ) {
            getPaymentLogs( {
                page: 1,
                perPage: 10,
                search: nextParams.search || '',
                payment_method: nextParams.payment_method || 'all',
            } ).then( ( logs ) => {
                if ( logs ) {
                    setCurrentPage( 1 );
                    setLogsState( logs );
                }
            } );
        } else {
            getCF7Entries( entryParams )
                .then( ( entries ) => {
                    setCf7entries( entries.entries );
                    setTotalEntries( entries.total );
                } );
        }
    };

    let handleDateRangeSelect = ( e ) => {
        handleSearchFrom( 'daterange', e.selection );
        var start_date = new Date( e.selection.startDate );
        var end_date   = new Date( e.selection.endDate );
        start_date = start_date.toLocaleString( 'en-US', { month: 'short', day: 'numeric', year: 'numeric' } );
        end_date   = end_date.toLocaleString( 'en-US', { month: 'short', day: 'numeric', year: 'numeric' } );
        setSelectedDate( start_date + ' - ' + end_date );
    }

    const CF7AppsPagination = ( { totalEntries } ) => {
        const totals = isPaymentLogsTab ? ( logsState.total || 0 ) : totalEntries;
        let pages = Math.ceil( totals / 10 );
        // if pages = NaN or 0 or 1 then return null
        if ( ! pages || pages === 1 ) {
            pages = 1;
        }

        const handlePageClick = ( page ) => {
            if ( isPaymentLogsTab ) {
                fetchPaymentLogs( false, page );
            } else {
                getCF7Entries( {
                    page: page,
                    perPage: 10,
                    search: searchParam.search,
                    form_id: searchParam.form_id,
                    start_date: searchParam.start_date,
                    end_date: searchParam.end_date
                } ).then( ( entries ) => {
                    setCurrentPage(page);
                    setCf7entries( entries.entries );
                } );
            }
        }

        const paginationItems = [];

        if ( totals ) {
            if ( pages <= 5 ) {
                for ( let i = 1; i <= pages; i++ ) {
                    paginationItems.push(
                        <Button
                            key={ i }
                            className={ currentPage === i ? 'cf7apps-btn tertiary-secondary' : '' }
                            onClick={ () => handlePageClick( i ) }
                        >{ i }</Button>
                    );
                }
            } else {
                paginationItems.push(
                    <Button
                        key={ 1 }
                        className={ currentPage === 1 ? 'cf7apps-btn tertiary-secondary' : '' }
                        onClick={ () => handlePageClick( 1 ) }
                    >1</Button>
                );

                if ( currentPage > 3 ) {
                    paginationItems.push(
                        <Button key={ 'start-ellipsis' }>...</Button>
                    );
                }

                for ( let i = Math.max(2, currentPage - 1 ); i <= Math.min( pages -1, currentPage + 1 ); i++ ) {
                    paginationItems.push(
                        <Button
                            key={ i }
                            className={ currentPage === i ? 'cf7apps-btn tertiary-secondary' : '' }
                            onClick={ () => handlePageClick( i ) }
                        >{ i }</Button>
                    );
                }

                if ( currentPage < pages - 2 ) {
                    paginationItems.push(
                        <Button key={ 'end-ellipsis' } disabled>...</Button>
                    );
                }

                paginationItems.push(
                    <Button
                        key={ pages }
                        className={ currentPage === pages ? 'cf7apps-btn tertiary-secondary' : '' }
                        onClick={ () => handlePageClick( pages ) }
                    >{ pages }</Button>
                );
            }
        }

        return (
            <div className={ 'cf7apps-entries-pagination' }>
                <Button
                    className={ 'cf7apps-btn tertiary-secondary' }
                    disabled={ currentPage === 1 }
                    onClick={ () => handlePageClick( currentPage - 1 ) }
                >&lt;</Button>

                { paginationItems }

                <Button
                    className={ 'cf7apps-btn tertiary-secondary' }
                    disabled={ currentPage === pages }
                    onClick={ () => handlePageClick( currentPage + 1 ) }
                >&gt;</Button>
            </div>
        );
    };

    return (
        ! loading ?
                <div className={ `cf7apps-template-cf7-entries${ isPaymentLogsTab ? ' cf7apps-payment-logs-view' : '' }` }>

                    { showDetails && entryData && ! isPaymentLogsTab && (
                            <CF7AppsPopup title={ __( 'Email Details', 'cf7apps' ) } onClose={ () => { setShowDetails( false ); setEntryData( false ); } }>
                                <p className={ 'cf7apps-entry-date' }>
                                    <CalendarMonthOutlined style={ { marginBottom: '-2px', marinRight: '5px', } } fontSize={ '14px' } />
                                    { entryData.date_time }
                                </p>

                                <div className="cf7apps-template-cf7-entries">
                                    <table width={ '100%' }>
                                        <thead>
                                        <tr>
                                            <th style={ { textAlign: 'left' } }>{ __( 'Fields', 'cf7apps' ) }</th>
                                            <th style={ { textAlign: 'left' } }>{ __( 'Values', 'cf7apps' ) }</th>
                                        </tr>
                                        </thead>

                                        <tbody>
                                        {
                                            entryData.data && Object.keys( entryData.data ).length > 0 ? (
                                                Object.keys( entryData.data ).map( ( key ) => {
                                                    return (
                                                        <tr key={ key }>
                                                            <td style={ { textAlign: 'left' } }>{ key }</td>
                                                            <td style={ { textAlign: 'left' } }>{ entryData.data[ key ] }</td>
                                                        </tr>
                                                    );
                                                } )
                                            ) : (
                                                <tr>
                                                    <td colSpan={ '2' }>{ __( 'No data found.', 'cf7apps' ) }</td>
                                                </tr>
                                            )
                                        }
                                        </tbody>
                                    </table>
                                </div>
                            </CF7AppsPopup>
                    ) }

                    { activePayment && (
                        <CF7AppsPopup title={ __( 'Payment Details', 'cf7apps' ) } onClose={ () => setActivePayment( null ) }>
                            <div className="cf7apps-template-cf7-entries cf7apps-payment-details-modal">
                                <table width={ '100%' }>
                                    <tbody>
                                        <tr><td>{ __( 'Transaction ID', 'cf7apps' ) }</td><td>{ activePayment.transaction_id || '-' }</td></tr>
                                        <tr><td>{ __( 'Form', 'cf7apps' ) }</td><td>{ activePayment.form_name || '-' }</td></tr>
                                        <tr><td>{ __( 'Environment', 'cf7apps' ) }</td><td>{ activePayment.environment || '-' }</td></tr>
                                        <tr><td>{ __( 'Customer Email', 'cf7apps' ) }</td><td>{ activePayment.customer_email || '-' }</td></tr>
                                        <tr><td>{ __( 'Payment Type', 'cf7apps' ) }</td><td>{ activePayment.payment_type || '-' }</td></tr>
                                        <tr><td>{ __( 'Currency', 'cf7apps' ) }</td><td>{ activePayment.currency || '-' }</td></tr>
                                        <tr><td>{ __( 'Amount', 'cf7apps' ) }</td><td>{ activePayment.currency ? `${ activePayment.currency } ${ activePayment.amount }` : activePayment.amount }</td></tr>
                                        <tr><td>{ __( 'Status', 'cf7apps' ) }</td><td>{ activePayment.status || '-' }</td></tr>
                                        <tr><td>{ __( 'HTTP Response', 'cf7apps' ) }</td><td>{ activePayment.http_response || '-' }</td></tr>
                                        <tr><td>{ __( 'Timestamp', 'cf7apps' ) }</td><td>{ activePayment.transaction_date || '-' }</td></tr>
                                    </tbody>
                                </table>
                                { ( () => {
                                    const dashboardLink = getPaymentDashboardLink( activePayment );
                                    if ( ! dashboardLink ) {
                                        return null;
                                    }

                                    return (
                                        <p style={ { marginTop: '12px', textAlign: 'left' } }>
                                            <a href={ dashboardLink.url } target="_blank" rel="noopener noreferrer">
                                                { dashboardLink.label }
                                            </a>
                                        </p>
                                    );
                                } )() }
                            </div>
                        </CF7AppsPopup>
                    ) }

                    { isPaymentLogsTab && ! logsState.entries_on && (
                        <div className="cf7apps-notice cf7apps-notice-warning" style={ { marginBottom: '16px' } }>
                            <p>
                                { __( 'Payment Logs require Entries to be enabled. Please enable it from Entries > Settings > General.', 'cf7apps' ) }
                            </p>
                        </div>
                    ) }

                    <div className={ `cf7apps-payment-logs-wrapper ${ lockLogsView ? 'is-locked' : '' }` }>
                    <div className={ `cf7apps-table-nav cf7apps-nav-header cf7apps-entries-toolbar ${ isPaymentLogsTab ? 'cf7apps-payment-logs-toolbar' : '' }` }>

                        { ! isPaymentLogsTab ? (
                        <div className={ 'cf7apps-bulk-action cf7apps-left cf7apps-entries-toolbar__left' }>

                            <div className="cf7apps-entries-toolbar__field">
                            <CF7AppsSelectField
                                className={ 'cf7apps-bulk-action-select-field' }
                                selected={ bulkAction }
                                onChange={ ( e ) => setBulkAction( e.target.value ) }
                                options={ {
                                    'null'       : __( 'Bulk Actions' ),
                                    'delete'     : __( 'Delete', 'cf7apps' ),
                                    'export_csv' : __( 'Export CSV', 'cf7apps' ),
                                } }
                            />
                            </div>

                            <Button
                                className={ 'cf7apps-btn tertiary-primary cf7apps-bulk-action-field cf7apps-entries-toolbar__apply' }
                                onClick={ () => {
                                    const selectedEntries = document.querySelectorAll( '.cf7apps-entry-checkbox:checked' );
                                    if ( selectedEntries.length === 0 ) {
                                        toast.error( __( 'Please select at least one entry', 'cf7apps' ) );
                                        return;
                                    }

                                    let entries = [];
                                    selectedEntries.forEach( entry => {
                                        entries.push( entry.value );
                                    } );

                                    handleBulkAction( bulkAction, entries );
                                    setBulkAction( 'null' );
                                } }
                            >{ __( 'Apply', 'cf7apps' ) }</Button>

                        </div>
                        ) : (
                        <div className="cf7apps-payment-logs-toolbar__controls">
                            <div className="cf7apps-entries-toolbar__field cf7apps-entries-toolbar__field--search">
                                <div className="cf7apps-payment-logs-search">
                                    { assetsBase && (
                                        <img
                                            className="cf7apps-payment-logs-search__icon"
                                            src={ `${ assetsBase }/images/dashboard/toolbar/search.svg` }
                                            alt=""
                                            width={ 12 }
                                            height={ 12 }
                                        />
                                    ) }
                                    <CF7AppsTextField
                                        className={ 'cf7apps-filter-text-field cf7apps-payment-logs-search__field' }
                                        placeholder={ __( 'Search Form', 'cf7apps' ) }
                                        onChange={ ( e ) => handleSearchFrom( 'text', e.target.value ) }
                                    />
                                </div>
                            </div>
                            <div className="cf7apps-entries-toolbar__field cf7apps-payment-logs-filter-field">
                                <CF7AppsSelectField
                                    className={ `cf7apps-filter-select-field cf7apps-payment-logs-filter${ ( searchParam.payment_method && 'all' !== searchParam.payment_method ) ? ' is-active' : '' }` }
                                    selected={ searchParam.payment_method || 'all' }
                                    options={ paymentMethodFilterOptions }
                                    onChange={ e => handleSearchFrom( 'payment_method', e.target.value ) }
                                />
                            </div>
                        </div>
                        ) }

                        { ! isPaymentLogsTab && (
                        <div className={ 'cf7apps-right cf7apps-filter cf7apps-entries-toolbar__right' }>
                            <div className="cf7apps-entries-toolbar__field">
                            <CF7AppsSelectField
                                className={ 'cf7apps-filter-select-field' }
                                selected={ searchParam.form_id }
                                options={ forms }
                                onChange={ e => handleSearchFrom( 'form', e.target.value ) }
                            />
                            </div>

                            <div className="cf7apps-entries-toolbar__field">
                            <CF7AppsTextField
                                className={ 'cf7apps-filter-text-field' }
                                placeholder={ __( 'Search', 'cf7apps' ) }
                                onChange={ ( e ) => handleSearchFrom( 'text', e.target.value ) }
                            />
                            </div>

                            <div className="cf7apps-entries-toolbar__field cf7apps-entries-toolbar__field--calendar">
                            <CF7AppsCalendar
                                selectedDate={ selectedDate }
                                selection={ [ dateRange ] }
                                onSelect={ handleDateRangeSelect }
                                placeHolder={ __( 'Select Date Range', 'cf7apps' ) }
                            />
                            </div>

                            <Button
                                onClick={ () => handleExportEntries() }
                                className={ 'cf7apps-btn tertiary-secondary cf7apps-entries-toolbar__export' }
                                disabled={ isExporting }
                                isBusy={ isExporting }
                            >{ __( 'Export CSV', 'cf7apps' ) }</Button>

                            <Button
                                onClick={ () => {
                                    setSearchParam( { form_id: 0, search: '' } );
                                    setSelectedDate( '' );
                                    document.querySelector( '.cf7apps-filter-select-field' ).value = 0;
                                    document.querySelector( '.cf7apps-filter-text-field' ).value = '';
                                    setDateRange( {
                                        startDate: new Date(),
                                        endDate: new Date(),
                                        key: 'selection',
                                    } );
                                    getCF7Entries( { page: currentPage, perPage: 10 } )
                                        .then( ( entries ) => {
                                            setCf7entries( entries.entries );
                                            setTotalEntries( entries.total );
                                        } );
                                } }
                                className={ 'cf7apps-btn tertiary-secondary cf7apps-entries-toolbar__clear' }
                            >{ __( 'Clear Filter', 'cf7apps' ) }</Button>
                        </div>
                        ) }

                    </div>

                    <table width="100%">
                        <thead>
                        <tr>
                            { ! isPaymentLogsTab && (
                            <th>
                                <input
                                    type={ 'checkbox' }
                                    onChange={ checkAllEntries }
                                />
                            </th>
                            ) }
                            { isPaymentLogsTab ? (
                                <>
                                    <th className="cf7apps-payment-logs-id">{ __( 'ID', 'cf7apps' ) }</th>
                                    <th className="cf7apps-payment-logs-email">{ __( 'Customer Email', 'cf7apps' ) }</th>
                                    <th className="cf7apps-payment-logs-type">{ __( 'Payment Type', 'cf7apps' ) }</th>
                                    <th className="cf7apps-payment-logs-amount-col">{ __( 'Amount', 'cf7apps' ) }</th>
                                    <th className="cf7apps-payment-logs-status-col">{ __( 'Status', 'cf7apps' ) }</th>
                                    <th className="cf7apps-payment-logs-date">{ __( 'Date & Time', 'cf7apps' ) }</th>
                                </>
                            ) : (
                                <>
                                    <th className="cf7apps-entries-form-name">{ __( 'Form Name', 'cf7apps' ) }</th>
                                    <th className="cf7apps-entries-email">{ __( 'Email', 'cf7apps' ) }</th>
                                    <th>{ __( 'Date & Time', 'cf7apps' ) }</th>
                                    <th>{ __( 'Payment Method', 'cf7apps' ) }</th>
                                </>
                            ) }
                            <th>{ __( 'Action', 'cf7apps' ) }</th>
                        </tr>
                        </thead>

                        <tbody>
                        {
                            ( isPaymentLogsTab ? logsState.logs.length > 0 : ( cf7entries && cf7entries.length > 0 ) ) ? (
                                <>
                                    { ( isPaymentLogsTab ? logsState.logs : cf7entries ).map( ( entry ) => {
                                        const entryPaymentMethod = entry.payment?.payment_method || entry.payment?.details?.payment_method || '';
                                        const entryPaymentMethodKey = entry.payment?.payment_method_key || entry.payment?.details?.payment_method_key || '';
                                        const paymentMethodText = entry.payment && entry.payment.is_gateway_enabled
                                            ? (
                                                entry.payment.has_payment
                                                    ? (
                                                        <a
                                                            href="#"
                                                            className="cf7apps-entry-action-view cf7apps-entry-payment-link"
                                                            onClick={ ( e ) => { e.preventDefault(); if ( entry.payment.details ) { setActivePayment( entry.payment.details ); } } }
                                                        >
                                                            <PaymentTypeBadge
                                                                paymentMethod={ entryPaymentMethod }
                                                                paymentMethodKey={ entryPaymentMethodKey }
                                                            />
                                                        </a>
                                                    )
                                                    : '-'
                                            )
                                            : '-';

                                        return (
                                            <tr key={ entry.id }>
                                                { ! isPaymentLogsTab && (
                                                <td>
                                                    <input className={ 'cf7apps-entry-checkbox' } value={ entry.id } type={ 'checkbox' } />
                                                </td>
                                                ) }
                                                { isPaymentLogsTab ? (
                                                    <>
                                                        <td className="cf7apps-payment-logs-id">{ entry.order_id }</td>
                                                        <td className="cf7apps-payment-logs-email">{ entry.customer_email }</td>
                                                        <td className="cf7apps-payment-logs-type">
                                                            <PaymentTypeBadge
                                                                paymentMethod={ entry.payment_method }
                                                                paymentMethodKey={ entry.payment_method_key }
                                                            />
                                                        </td>
                                                        <td className="cf7apps-payment-logs-amount cf7apps-payment-logs-amount-col">{ formatPaymentAmount( entry.currency, entry.amount ) }</td>
                                                        <td className="cf7apps-payment-logs-status-col">
                                                            <span className={ `cf7apps-payment-status ${ getPaymentStatusClass( entry.status ) }` }>
                                                                { entry.status }
                                                            </span>
                                                        </td>
                                                        <td className="cf7apps-payment-logs-date">{ entry.transaction_date }</td>
                                                    </>
                                                ) : (
                                                    <>
                                                        <td className="cf7apps-entries-form-name">{ entry.form_name }</td>
                                                        <td className="cf7apps-entries-email">{ entry.email }</td>
                                                        <td>{ entry.date_time }</td>
                                                        <td>{ paymentMethodText }</td>
                                                    </>
                                                ) }
                                                <td className="cf7apps-entry-actions">
                                                    <div className="cf7apps-entries-row-actions">
                                                        <button
                                                            type="button"
                                                            className="cf7apps-entries-row-action"
                                                            onClick={ e => { e.preventDefault(); isPaymentLogsTab ? setActivePayment( entry ) : handleViewButton( e, entry ) } }
                                                        >
                                                            <VisibilityOutlined fontSize={ '14px' } />
                                                            { __( 'View', 'cf7apps' ) }
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="cf7apps-entries-row-action cf7apps-entries-row-action--delete"
                                                            onClick={ e => handleDeleteButton( e, entry.id ) }
                                                        >
                                                            <DeleteOutlined fontSize={ '14px' } />
                                                            { __( 'Delete', 'cf7apps' ) }
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    } ) }
                                </>
                            ) : (
                                <tr>
                                    <td colSpan={ isPaymentLogsTab ? '7' : '6' }>
                                        { isPaymentLogsTab ? __( 'No payment entry has been recorded so far.', 'cf7apps' ) : __( 'No records found.', 'cf7apps' ) }
                                    </td>
                                </tr>
                            )
                        }
                        </tbody>
                    </table>

                    <div className={ 'cf7apps-table-nav cf7apps-nav-footer' }>

                        <div className="cf7apps-left">
                            <p className="cf7apps-datatable-count">
                                { __( 'Showing', 'cf7apps' ) } <b>{ isPaymentLogsTab ? logsState.logs.length : ( cf7entries ? cf7entries.length : 0 ) }</b> of <b>{ isPaymentLogsTab ? logsState.total : totalEntries }</b> { __( 'entries', 'cf7apps' ) } | { __( 'Page', 'cf7apps' ) } <b>{ currentPage }</b>
                            </p>
                        </div>
                        <div className="cf7apps-right">
                            <CF7AppsPagination
                                totalEntries={ totalEntries }
                            />
                        </div>

                    </div>
                    { lockLogsView && (
                        <div className="cf7apps-payment-logs-lock">
                            <Button
                                className="cf7apps-btn cf7apps-payment-logs-unlock-btn"
                                onClick={ handleUnlockPaymentLogs }
                            >
                                { __( 'Enable Payment Gateways to Unlock Logs', 'cf7apps' ) }
                            </Button>
                        </div>
                    ) }
                    </div>
                </div>
            :
            <>
                <CF7AppsSkeletonLoader count={ 1 } height={ 38 } />
                <CF7AppsSkeletonLoader count={ 2 } height={ 57 } />
            </>
    );
}

export default Cf7Entries;
