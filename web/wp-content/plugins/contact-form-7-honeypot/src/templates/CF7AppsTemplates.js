let CF7AppsTemplates = {};

CF7AppsTemplates['HoneypotForms'] = require('./honeypot/HoneypotForms.js').default;
CF7AppsTemplates['cf7Entries']    = require( './cf7Entries/cf7Entries.js' ).default;
CF7AppsTemplates['StripeAdmin']   = require( './stripe/StripeAdmin.js' ).default;
CF7AppsTemplates['PayPalAdmin']   = require( './paypal/PayPalAdmin.js' ).default;
CF7AppsTemplates['SquareAdmin']  = require( './square/SquareAdmin.js' ).default;
CF7AppsTemplates['weeklySummaryAdmin'] = require( './weeklySummary/WeeklySummaryAdmin.js' ).default;

export default CF7AppsTemplates;