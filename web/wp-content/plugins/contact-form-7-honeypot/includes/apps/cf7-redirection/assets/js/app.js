( function( $ ) {
    var PAYMENT_CHECKOUT_KEYS = [
        'cf7apps_stripe_checkout_url',
        'cf7apps_paypal_checkout_url',
        'cf7apps_square_checkout_url',
    ];

    function getPaymentCheckoutUrl( detail ) {
        if ( ! detail ) {
            return '';
        }

        var sources = [ detail, detail.apiResponse ].filter( Boolean );

        for ( var i = 0; i < sources.length; i++ ) {
            for ( var j = 0; j < PAYMENT_CHECKOUT_KEYS.length; j++ ) {
                if ( sources[ i ][ PAYMENT_CHECKOUT_KEYS[ j ] ] ) {
                    return sources[ i ][ PAYMENT_CHECKOUT_KEYS[ j ] ];
                }
            }
        }

        return '';
    }

    function shouldSkipRedirection( detail ) {
        if ( getPaymentCheckoutUrl( detail ) ) {
            return true;
        }

        if ( window.cf7appsPaymentRedirectPending ) {
            return true;
        }

        if (
            cf7appsRedirection &&
            cf7appsRedirection.paymentGatewayForms &&
            detail &&
            detail.contactFormId &&
            cf7appsRedirection.paymentGatewayForms[ String( detail.contactFormId ) ]
        ) {
            return true;
        }

        return false;
    }

    function maybeRedirect( detail ) {
        if ( ! detail || ! detail.contactFormId ) {
            return;
        }

        if ( shouldSkipRedirection( detail ) ) {
            return;
        }

        var formId = detail.contactFormId;

        // Defer so payment gateway redirect handlers run first.
        window.setTimeout( function() {
            if ( window.cf7appsPaymentRedirectPending ) {
                return;
            }

            $.post(
                cf7appsRedirection.ajaxurl,
                { action: 'cf7apps_fetch_settings', formId: formId },
                function( response ) {
                    if ( ! response.success ) {
                        return;
                    }

                    if ( window.cf7appsPaymentRedirectPending ) {
                        return;
                    }

                    var data = response.data || {};
                    var redirectTo = data.redirectTo;
                    var newTab = data.newTab;
                    var isEnabled = data.isEnabled;

                    if ( ! isEnabled || ! redirectTo ) {
                        return;
                    }

                    if ( newTab ) {
                        window.open( redirectTo, '_blank' );
                    } else {
                        window.location.href = redirectTo;
                    }
                }
            );
        }, 350 );
    }

    function onCf7Submit( e ) {
        var detail = e.detail || {};
        var status = detail.status || '';

        if ( status && 'mail_sent' !== status && 'sent' !== status ) {
            return;
        }

        maybeRedirect( detail );
    }

    document.addEventListener( 'wpcf7mailsent', onCf7Submit );
    document.addEventListener( 'wpcf7submit', onCf7Submit );
} )( jQuery );
