/**
 * WooCommerce Subscriptions Troubleshooter Admin Scripts
 */

( function( $ ) {
    'use strict';

    const WCST = {
        currentSubscriptionId: null,
        analysisData: null,

        init: function() {
            this.bindEvents();
            this.initializeInterface();
        },

        bindEvents: function() {
            // Analyze button click
            $( '#wcst-analyze-btn' ).on( 'click', this.handleAnalyzeClick.bind( this ) );
            
            // Search input events
            $( '#wcst-subscription-search' )
                .on( 'input', this.handleSearchInput.bind( this ) )
                .on( 'keypress', this.handleSearchKeypress.bind( this ) );
            
            // Search result clicks
            $( document ).on( 'click', '.wcst-search-result-item', this.handleSearchResultClick.bind( this ) );
            
            
        },

        initializeInterface: function() {
            // Initialize any default states
            $( '#wcst-results' ).hide();
            $( '#wcst-progress' ).hide();
        },

        handleAnalyzeClick: function( e ) {
            e.preventDefault();
            
            const subscriptionId = $( '#wcst-subscription-search' ).val().trim();
            
            if ( ! subscriptionId ) {
                this.showError( wcst_ajax.strings.invalid_subscription_id );
                return;
            }
            
            this.analyzeSubscription( subscriptionId );
        },

        handleSearchInput: function( e ) {
            const searchTerm = $( e.target ).val().trim();
            
            if ( searchTerm.length < 2 ) {
                $( '#wcst-search-results' ).hide();
                return;
            }
            
            // Debounce search
            clearTimeout( this.searchTimeout );
            this.searchTimeout = setTimeout( () => {
                this.searchSubscriptions( searchTerm );
            }, 300 );
        },

        handleSearchKeypress: function( e ) {
            if ( 13 === e.which ) { // Enter key
                $( '#wcst-analyze-btn' ).click();
            }
        },

        handleSearchResultClick: function( e ) {
            const subscriptionId = $( e.currentTarget ).data( 'id' );
            $( '#wcst-subscription-search' ).val( subscriptionId );
            $( '#wcst-search-results' ).hide();
            this.analyzeSubscription( subscriptionId );
        },

        

        analyzeSubscription: function( subscriptionId ) {
            this.showProgress();
            this.currentSubscriptionId = subscriptionId;
            
            $.ajax( {
                url: wcst_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcst_analyze_subscription',
                    subscription_id: subscriptionId,
                    nonce: wcst_ajax.nonce
                },
                success: ( response ) => {
                    if ( response.success ) {
                        this.analysisData = response.data;
                        this.displayResults( response.data );
                    } else {
                        this.showError( response.data || wcst_ajax.strings.error );
                    }
                },
                error: () => {
                    this.showError( wcst_ajax.strings.error );
                },
                complete: () => {
                    this.hideProgress();
                }
            } );
        },

        searchSubscriptions: function( searchTerm ) {
            $.ajax( {
                url: wcst_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcst_search_subscriptions',
                    search_term: searchTerm,
                    nonce: wcst_ajax.nonce
                },
                success: ( response ) => {
                    if ( response.success ) {
                        this.displaySearchResults( response.data );
                    }
                }
            } );
        },

        displaySearchResults: function( results ) {
            const $container = $( '#wcst-search-results' );
            
            if ( 0 === results.length ) {
                $container.html( '<div class="wcst-search-result-item">No subscriptions found.</div>' );
            } else {
                let html = '';
                results.forEach( ( result ) => {
                    html += '<div class="wcst-search-result-item" data-id="' + result.id + '">';
                    html += '<strong>' + result.title + '</strong><br>';
                    html += '<small>Status: ' + result.status + ' | Customer: ' + result.customer + '</small>';
                    html += '</div>';
                } );
                $container.html( html );
            }
            
            $container.show();
        },

        displayResults: function( data ) {
            this.displayAnatomy( data.anatomy );
            this.displayExpectedBehavior( data.expected );
            this.displayTimeline( data.timeline );
            this.displaySummary( data.summary );
            
            $( '#wcst-results' ).show();
            this.updateProgressComplete();
        },

        displayAnatomy: function( anatomy ) {
            const html = this.renderAnatomyContent( anatomy );
            $( '#wcst-anatomy-content' ).html( html );
        },

        displayExpectedBehavior: function( expected ) {
            const html = this.renderExpectedBehaviorContent( expected );
            $( '#wcst-expected-content' ).html( html );
        },

        displayTimeline: function( timeline ) {
            const html = this.renderTimelineContent( timeline );
            $( '#wcst-timeline-content' ).html( html );
        },

        displaySummary: function( summary ) {
            const html = this.renderSummaryContent( summary );
            $( '#wcst-summary-content' ).html( html );
        },

        renderAnatomyContent: function( anatomy ) {
            let html = '';
            
            // Basic Info Summary Panel
            html += '<div class="wcst-summary-panel">';
            html += '<div class="wcst-summary-card">';
            html += '<h3>Status</h3>';
            html += '<div class="wcst-summary-value">';
            html += '<span class="wcst-status-badge ' + this.getStatusClass( anatomy.basic_info.status ) + '">' + anatomy.basic_info.status + '</span>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Customer</h3>';
            html += '<div class="wcst-summary-value">' + anatomy.basic_info.customer_id + '</div>';
            html += '<small>ID: ' + anatomy.basic_info.customer_id + '</small>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Total</h3>';
            html += '<div class="wcst-summary-value">' + anatomy.basic_info.total + ' ' + anatomy.basic_info.currency + '</div>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Next Payment</h3>';
            html += '<div class="wcst-summary-value">';
            if ( anatomy.billing_schedule.next_payment ) {
                html += this.formatDate( anatomy.billing_schedule.next_payment );
            } else {
                html += 'N/A';
            }
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Payment Method Details
            html += '<h3>Payment Method</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Gateway</th><th>Type</th><th>Status</th></tr>';
            html += '<tr>';
            html += '<td>' + ( anatomy.payment_method.title || 'N/A' ) + '</td>';
            html += '<td>' + ( anatomy.payment_method.requires_manual ? 'Manual' : 'Automatic' ) + '</td>';
            html += '<td>';
            if ( anatomy.payment_method.status.is_valid ) {
                html += '<span class="wcst-status-badge success">Valid</span>';
            } else {
                html += '<span class="wcst-status-badge error">Issues Found</span>';
            }
            html += '</td>';
            html += '</tr>';
            html += '</table>';
            
            // Warnings
            if ( anatomy.payment_method.status.warnings && anatomy.payment_method.status.warnings.length > 0 ) {
                html += '<div class="wcst-warning">';
                html += '<strong>Payment Method Warnings:</strong><br>';
                html += anatomy.payment_method.status.warnings.join( '<br>' );
                html += '</div>';
            }
            
            // Billing Schedule
            html += '<h3>Billing Schedule</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Property</th><th>Value</th></tr>';
            html += '<tr><td>Billing Interval</td><td>' + anatomy.billing_schedule.interval + ' ' + anatomy.billing_schedule.period + '</td></tr>';
            html += '<tr><td>Start Date</td><td>' + this.formatDate( anatomy.billing_schedule.start_date ) + '</td></tr>';
            html += '<tr><td>Next Payment</td><td>' + this.formatDate( anatomy.billing_schedule.next_payment ) + '</td></tr>';
            html += '<tr><td>End Date</td><td>' + this.formatDate( anatomy.billing_schedule.end_date ) + '</td></tr>';
            html += '<tr><td>Editable</td><td>' + ( anatomy.billing_schedule.is_editable ? 'Yes' : 'No' ) + '</td></tr>';
            html += '</table>';
            
            return html;
        },

        renderExpectedBehaviorContent: function( expected ) {
            let html = '';
            
            // Payment Gateway Behavior
            html += '<h3>Payment Gateway Capabilities</h3>';
            if ( expected.payment_gateway_behavior && ! expected.payment_gateway_behavior.error ) {
                html += '<table class="wcst-data-table">';
                html += '<tr><th>Feature</th><th>Supported</th></tr>';
                html += '<tr><td>Subscriptions</td><td>' + this.renderSupportIcon( expected.payment_gateway_behavior.supports_subscriptions ) + '</td></tr>';
                html += '<tr><td>Cancellation</td><td>' + this.renderSupportIcon( expected.payment_gateway_behavior.supports_subscription_cancellation ) + '</td></tr>';
                html += '<tr><td>Suspension</td><td>' + this.renderSupportIcon( expected.payment_gateway_behavior.supports_subscription_suspension ) + '</td></tr>';
                html += '<tr><td>Amount Changes</td><td>' + this.renderSupportIcon( expected.payment_gateway_behavior.supports_subscription_amount_changes ) + '</td></tr>';
                html += '<tr><td>Date Changes</td><td>' + this.renderSupportIcon( expected.payment_gateway_behavior.supports_subscription_date_changes ) + '</td></tr>';
                html += '</table>';
            } else {
                html += '<div class="wcst-error">Payment gateway information not available.</div>';
            }
            
            // Renewal Expectations
            html += '<h3>Renewal Process</h3>';
            if ( expected.renewal_expectations ) {
                html += '<div class="wcst-info">';
                html += '<strong>Type:</strong> ' + expected.renewal_expectations.type + '<br>';
                html += '<strong>Description:</strong> ' + expected.renewal_expectations.description;
                html += '</div>';
                
                if ( expected.renewal_expectations.next_action ) {
                    html += '<p><strong>Next Action:</strong> ' + expected.renewal_expectations.next_action + '</p>';
                }
            }
            
            return html;
        },

        renderTimelineContent: function( timeline ) {
            let html = '';
            
            if ( timeline.events && timeline.events.length > 0 ) {
                html += '<div class="wcst-timeline">';
                timeline.events.forEach( ( event ) => {
                    html += '<div class="wcst-timeline-event ' + event.status + '">';
                    html += '<div class="wcst-timeline-header">';
                    html += '<span class="wcst-timeline-date">' + this.formatDate( event.timestamp ) + '</span>';
                    html += '<span class="wcst-timeline-type">' + event.type + '</span>';
                    html += '</div>';
                    html += '<div class="wcst-timeline-description">' + event.title + '</div>';
                    if ( event.description !== event.title ) {
                        html += '<div style="font-size: 12px; color: #666; margin-top: 5px;">' + event.description + '</div>';
                    }
                    html += '</div>';
                } );
                html += '</div>';
            } else {
                html += '<p>No timeline events found.</p>';
            }
            
            return html;
        },

        renderSummaryContent: function( summary ) {
            let html = '';
            
            // Issues Summary
            if ( summary.issues && summary.issues.length > 0 ) {
                html += '<h3>Issues Detected</h3>';
                summary.issues.forEach( ( issue ) => {
                    const severityClass = 'critical' === issue.severity ? 'error' : 'warning';
                    html += '<div class="wcst-' + severityClass + '">';
                    html += '<strong>' + issue.title + ':</strong> ' + issue.description;
                    html += '</div>';
                } );
            } else {
                html += '<div class="wcst-success">';
                html += '<strong>No issues detected!</strong> The subscription appears to be functioning normally.';
                html += '</div>';
            }
            
            // Statistics
            if ( summary.statistics ) {
                html += '<h3>Summary Statistics</h3>';
                html += '<div class="wcst-summary-panel">';
                html += '<div class="wcst-summary-card">';
                html += '<h3>Total Issues</h3>';
                html += '<div class="wcst-summary-value">' + summary.statistics.total_issues + '</div>';
                html += '</div>';
                html += '<div class="wcst-summary-card">';
                html += '<h3>Critical</h3>';
                html += '<div class="wcst-summary-value">' + summary.statistics.critical + '</div>';
                html += '</div>';
                html += '<div class="wcst-summary-card">';
                html += '<h3>Warnings</h3>';
                html += '<div class="wcst-summary-value">' + summary.statistics.warnings + '</div>';
                html += '</div>';
                html += '</div>';
            }
            
            // Next Steps
            if ( summary.next_steps && summary.next_steps.length > 0 ) {
                html += '<h3>Recommended Next Steps</h3>';
                html += '<ul>';
                summary.next_steps.forEach( ( step ) => {
                    html += '<li>' + step + '</li>';
                } );
                html += '</ul>';
            }
            
            
            return html;
        },

        

        showProgress: function() {
            $( '#wcst-progress' ).show();
            $( '#wcst-results' ).hide();
            this.updateProgress( 1 );
        },

        hideProgress: function() {
            // Keep progress visible but mark as complete
        },

        updateProgress: function( step ) {
            $( '.wcst-step' ).removeClass( 'active completed' );
            
            for ( let i = 1; i <= step; i++ ) {
                const $step = $( '.wcst-step[data-step="' + i + '"]' );
                if ( i === step ) {
                    $step.addClass( 'active' );
                } else {
                    $step.addClass( 'completed' );
                }
            }
        },

        updateProgressComplete: function() {
            $( '.wcst-step' ).removeClass( 'active' ).addClass( 'completed' );
        },

        showError: function( message ) {
            const html = '<div class="wcst-error">' + message + '</div>';
            $( '#wcst-results' ).html( html ).show();
            $( '#wcst-progress' ).hide();
        },

        // Helper functions
        getStatusClass: function( status ) {
            const statusMap = {
                'active': 'success',
                'on-hold': 'warning',
                'cancelled': 'error',
                'expired': 'error',
                'pending': 'warning'
            };
            return statusMap[ status ] || 'info';
        },

        renderSupportIcon: function( supported ) {
            return supported ? '✅ Yes' : '❌ No';
        },

        formatDate: function( dateString ) {
            if ( ! dateString || 'N/A' === dateString ) {
                return 'N/A';
            }
            
            try {
                const date = new Date( dateString );
                return date.toLocaleString();
            } catch ( e ) {
                return dateString;
            }
        }
    };

    // Initialize when document is ready
    $( document ).ready( function() {
        WCST.init();
    } );

} )( jQuery );
