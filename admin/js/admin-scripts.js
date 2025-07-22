/**
 * WooCommerce Subscriptions Troubleshooter Admin Scripts
 */

(function($) {
    'use strict';

    var WCST = {
        currentSubscriptionId: null,
        analysisData: null,

        init: function() {
            this.bindEvents();
            this.initializeSections();
        },

        bindEvents: function() {
            // Analyze button click
            $('#wcst-analyze-btn').on('click', this.handleAnalyzeClick.bind(this));
            
            // Search input events
            $('#wcst-subscription-search').on('input', this.handleSearchInput.bind(this));
            $('#wcst-subscription-search').on('keypress', this.handleSearchKeypress.bind(this));
            
            // Section toggle events
            $('.wcst-toggle-section').on('click', this.handleSectionToggle.bind(this));
            
            // Search result clicks
            $(document).on('click', '.wcst-search-result-item', this.handleSearchResultClick.bind(this));
            
            // Filter events
            $(document).on('change', '.wcst-filter-control', this.handleFilterChange.bind(this));
            
            // Export events
            $(document).on('click', '.wcst-export-btn', this.handleExportClick.bind(this));
        },

        initializeSections: function() {
            // Initialize all sections as expanded
            $('.wcst-section').removeClass('collapsed');
        },

        handleAnalyzeClick: function(e) {
            e.preventDefault();
            
            var subscriptionId = $('#wcst-subscription-search').val().trim();
            
            if (!subscriptionId) {
                this.showError('Please enter a subscription ID or customer email.');
                return;
            }
            
            this.analyzeSubscription(subscriptionId);
        },

        handleSearchInput: function(e) {
            var searchTerm = $(e.target).val().trim();
            
            if (searchTerm.length < 3) {
                $('#wcst-search-results').hide();
                return;
            }
            
            this.searchSubscriptions(searchTerm);
        },

        handleSearchKeypress: function(e) {
            if (e.which === 13) { // Enter key
                $('#wcst-analyze-btn').click();
            }
        },

        handleSearchResultClick: function(e) {
            var subscriptionId = $(e.currentTarget).data('id');
            $('#wcst-subscription-search').val(subscriptionId);
            $('#wcst-search-results').hide();
            this.analyzeSubscription(subscriptionId);
        },

        handleSectionToggle: function(e) {
            e.preventDefault();
            
            var $section = $(e.currentTarget).closest('.wcst-section');
            $section.toggleClass('collapsed');
        },

        handleFilterChange: function(e) {
            if (!this.currentSubscriptionId) return;
            
            var filters = this.collectFilters();
            this.applyFilters(filters);
        },

        handleExportClick: function(e) {
            e.preventDefault();
            
            if (!this.currentSubscriptionId) {
                this.showError('No subscription data to export.');
                return;
            }
            
            var format = $(e.currentTarget).data('format');
            this.exportReport(format);
        },

        analyzeSubscription: function(subscriptionId) {
            this.showProgress();
            this.currentSubscriptionId = subscriptionId;
            
            $.ajax({
                url: wcst_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcst_analyze_subscription',
                    subscription_id: subscriptionId,
                    nonce: wcst_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WCST.analysisData = response.data;
                        WCST.displayResults(response.data);
                    } else {
                        WCST.showError(response.data || wcst_ajax.strings.error);
                    }
                },
                error: function() {
                    WCST.showError(wcst_ajax.strings.error);
                },
                complete: function() {
                    WCST.hideProgress();
                }
            });
        },

        searchSubscriptions: function(searchTerm) {
            $.ajax({
                url: wcst_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcst_get_subscription_data',
                    search_term: searchTerm,
                    nonce: wcst_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WCST.displaySearchResults(response.data);
                    }
                }
            });
        },

        displaySearchResults: function(results) {
            var $container = $('#wcst-search-results');
            
            if (results.length === 0) {
                $container.html('<div class="wcst-search-result-item">No subscriptions found.</div>');
            } else {
                var html = '';
                results.forEach(function(result) {
                    html += '<div class="wcst-search-result-item" data-id="' + result.id + '">';
                    html += '<strong>' + result.title + '</strong><br>';
                    html += '<small>Status: ' + result.status + ' | Customer: ' + result.customer + '</small>';
                    html += '</div>';
                });
                $container.html(html);
            }
            
            $container.show();
        },

        displayResults: function(data) {
            this.displayAnatomy(data.anatomy);
            this.displayExpectedBehavior(data.expected);
            this.displayTimeline(data.timeline);
            this.displaySummary(data);
            
            $('#wcst-results').show();
            this.updateProgress(3);
        },

        displayAnatomy: function(anatomy) {
            var html = this.renderAnatomyContent(anatomy);
            $('#wcst-anatomy-content').html(html);
        },

        displayExpectedBehavior: function(expected) {
            var html = this.renderExpectedBehaviorContent(expected);
            $('#wcst-expected-content').html(html);
        },

        displayTimeline: function(timeline) {
            var html = this.renderTimelineContent(timeline);
            $('#wcst-timeline-content').html(html);
        },

        displaySummary: function(data) {
            var html = this.renderSummaryContent(data);
            $('#wcst-summary-content').html(html);
        },

        renderAnatomyContent: function(anatomy) {
            var html = '';
            
            // Summary Panel
            html += '<div class="wcst-summary-panel">';
            html += '<div class="wcst-summary-card">';
            html += '<h3>Status</h3>';
            html += '<div class="wcst-summary-value">';
            html += '<span class="wcst-status-badge ' + anatomy.summary.status.class + '">' + anatomy.summary.status.label + '</span>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Customer</h3>';
            html += '<div class="wcst-summary-value">' + anatomy.summary.customer.name + '</div>';
            html += '<small>' + anatomy.summary.customer.email + '</small>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Billing Schedule</h3>';
            html += '<div class="wcst-summary-value">' + anatomy.summary.billing_schedule.interval + ' ' + anatomy.summary.billing_schedule.period + '</div>';
            html += '<small>Next: ' + (anatomy.summary.billing_schedule.next_payment || 'N/A') + '</small>';
            html += '</div>';
            
            html += '<div class="wcst-summary-card">';
            html += '<h3>Total</h3>';
            html += '<div class="wcst-summary-value">' + anatomy.summary.billing_schedule.total + ' ' + anatomy.summary.billing_schedule.currency + '</div>';
            html += '</div>';
            html += '</div>';
            
            // Payment Method Details
            html += '<h3>Payment Method</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Gateway</th><th>Token</th><th>Status</th><th>Expiry</th></tr>';
            html += '<tr>';
            html += '<td>' + anatomy.payment_method.gateway.title + '</td>';
            html += '<td>' + (anatomy.payment_method.token.last_4 ? '****' + anatomy.payment_method.token.last_4 : 'N/A') + '</td>';
            html += '<td>' + (anatomy.payment_method.status.is_valid ? '<span class="wcst-status-badge success">Valid</span>' : '<span class="wcst-status-badge error">Invalid</span>') + '</td>';
            html += '<td>' + (anatomy.payment_method.expiry.date || 'N/A') + '</td>';
            html += '</tr>';
            html += '</table>';
            
            // Warnings
            if (anatomy.payment_method.status.warnings.length > 0) {
                html += '<div class="wcst-warning">';
                html += '<strong>Payment Method Warnings:</strong><br>';
                html += anatomy.payment_method.status.warnings.join('<br>');
                html += '</div>';
            }
            
            // Related Orders
            html += '<h3>Related Orders (' + anatomy.related_orders.counts.total + ')</h3>';
            if (anatomy.related_orders.orders.length > 0) {
                html += '<table class="wcst-data-table">';
                html += '<tr><th>Order ID</th><th>Type</th><th>Status</th><th>Total</th><th>Date</th></tr>';
                anatomy.related_orders.orders.forEach(function(order) {
                    html += '<tr>';
                    html += '<td><a href="' + adminurl + 'post.php?post=' + order.id + '&action=edit">#' + order.id + '</a></td>';
                    html += '<td>' + order.type + '</td>';
                    html += '<td><span class="wcst-status-badge ' + this.getOrderStatusClass(order.status) + '">' + order.status + '</span></td>';
                    html += '<td>' + order.total + '</td>';
                    html += '<td>' + order.date + '</td>';
                    html += '</tr>';
                }.bind(this));
                html += '</table>';
            }
            
            // Scheduled Actions
            html += '<h3>Scheduled Actions</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Status</th><th>Count</th></tr>';
            html += '<tr><td>Pending</td><td>' + anatomy.scheduled_actions.summary.pending + '</td></tr>';
            html += '<tr><td>Completed</td><td>' + anatomy.scheduled_actions.summary.completed + '</td></tr>';
            html += '<tr><td>Failed</td><td>' + anatomy.scheduled_actions.summary.failed + '</td></tr>';
            html += '<tr><td>Cancelled</td><td>' + anatomy.scheduled_actions.summary.cancelled + '</td></tr>';
            html += '</table>';
            
            return html;
        },

        renderExpectedBehaviorContent: function(expected) {
            var html = '';
            
            // Product Configuration
            html += '<h3>Product Configuration</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Setting</th><th>Value</th></tr>';
            html += '<tr><td>Billing Period</td><td>' + expected.product_config.billing_period + '</td></tr>';
            html += '<tr><td>Billing Interval</td><td>' + expected.product_config.billing_interval + '</td></tr>';
            html += '<tr><td>Trial Length</td><td>' + (expected.product_config.trial_length || 'None') + '</td></tr>';
            html += '<tr><td>Sign Up Fee</td><td>' + (expected.product_config.sign_up_fee || 'None') + '</td></tr>';
            html += '</table>';
            
            // Gateway Behavior
            html += '<h3>Gateway Behavior</h3>';
            html += '<table class="wcst-data-table">';
            html += '<tr><th>Feature</th><th>Supported</th></tr>';
            html += '<tr><td>Subscriptions</td><td>' + (expected.gateway_behavior.supports_subscriptions ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Cancellation</td><td>' + (expected.gateway_behavior.supports_subscription_cancellation ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Suspension</td><td>' + (expected.gateway_behavior.supports_subscription_suspension ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Reactivation</td><td>' + (expected.gateway_behavior.supports_subscription_reactivation ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Amount Changes</td><td>' + (expected.gateway_behavior.supports_subscription_amount_changes ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Date Changes</td><td>' + (expected.gateway_behavior.supports_subscription_date_changes ? '✅' : '❌') + '</td></tr>';
            html += '<tr><td>Manual Renewal</td><td>' + (expected.gateway_behavior.requires_manual_renewal ? '⚠️' : '✅') + '</td></tr>';
            html += '<tr><td>Webhook Configured</td><td>' + (expected.gateway_behavior.webhook_configured ? '✅' : '❌') + '</td></tr>';
            html += '</table>';
            
            return html;
        },

        renderTimelineContent: function(timeline) {
            var html = '';
            
            // Filters
            html += '<div class="wcst-filters">';
            html += '<h3>Filter Timeline</h3>';
            html += '<div class="wcst-filter-row">';
            html += '<div class="wcst-filter-group">';
            html += '<label>Event Type</label>';
            html += '<select class="wcst-filter-control" data-filter="event_type">';
            html += '<option value="">All Types</option>';
            html += '<option value="payment">Payment</option>';
            html += '<option value="status_change">Status Change</option>';
            html += '<option value="action">Action</option>';
            html += '<option value="notification">Notification</option>';
            html += '</select>';
            html += '</div>';
            html += '<div class="wcst-filter-group">';
            html += '<label>Status</label>';
            html += '<select class="wcst-filter-control" data-filter="status">';
            html += '<option value="">All Statuses</option>';
            html += '<option value="success">Success</option>';
            html += '<option value="failed">Failed</option>';
            html += '<option value="pending">Pending</option>';
            html += '</select>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Timeline
            html += '<div class="wcst-timeline">';
            if (timeline.events && timeline.events.length > 0) {
                timeline.events.forEach(function(event) {
                    html += '<div class="wcst-timeline-event ' + event.status + '">';
                    html += '<div class="wcst-timeline-header">';
                    html += '<span class="wcst-timeline-date">' + event.timestamp + '</span>';
                    html += '<span class="wcst-timeline-type">' + event.event_type + '</span>';
                    html += '</div>';
                    html += '<div class="wcst-timeline-description">' + event.description + '</div>';
                    html += '</div>';
                });
            } else {
                html += '<p>No timeline events found.</p>';
            }
            html += '</div>';
            
            return html;
        },

        renderSummaryContent: function(data) {
            var html = '';
            
            // Issues Summary
            if (data.discrepancies && data.discrepancies.length > 0) {
                html += '<h3>Detected Issues</h3>';
                data.discrepancies.forEach(function(discrepancy) {
                    var severityClass = discrepancy.severity === 'critical' ? 'error' : 'warning';
                    html += '<div class="wcst-' + severityClass + '">';
                    html += '<strong>' + discrepancy.type + ':</strong> ' + discrepancy.description;
                    html += '</div>';
                });
            } else {
                html += '<div class="wcst-success">';
                html += '<strong>No issues detected!</strong> The subscription appears to be functioning normally.';
                html += '</div>';
            }
            
            // Export Options
            html += '<h3>Export Report</h3>';
            html += '<div class="wcst-filter-row">';
            html += '<button class="button wcst-export-btn" data-format="html">Export as HTML</button>';
            html += '<button class="button wcst-export-btn" data-format="csv">Export as CSV</button>';
            html += '<button class="button wcst-export-btn" data-format="pdf">Export as PDF</button>';
            html += '</div>';
            
            return html;
        },

        showProgress: function() {
            $('#wcst-progress').show();
            this.updateProgress(1);
        },

        hideProgress: function() {
            $('#wcst-progress').hide();
        },

        updateProgress: function(step) {
            $('.wcst-step').removeClass('active completed');
            
            for (var i = 1; i <= step; i++) {
                var $step = $('.wcst-step[data-step="' + i + '"]');
                if (i === step) {
                    $step.addClass('active');
                } else {
                    $step.addClass('completed');
                }
            }
        },

        showError: function(message) {
            var html = '<div class="wcst-error">' + message + '</div>';
            $('#wcst-results').html(html).show();
        },

        collectFilters: function() {
            var filters = {};
            $('.wcst-filter-control').each(function() {
                var filter = $(this).data('filter');
                var value = $(this).val();
                if (value) {
                    filters[filter] = value;
                }
            });
            return filters;
        },

        applyFilters: function(filters) {
            // This would be implemented to filter the timeline
            console.log('Applying filters:', filters);
        },

        exportReport: function(format) {
            $.ajax({
                url: wcst_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcst_export_report',
                    subscription_id: this.currentSubscriptionId,
                    format: format,
                    nonce: wcst_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Handle download
                        var link = document.createElement('a');
                        link.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(response.data);
                        link.download = 'subscription-report-' + WCST.currentSubscriptionId + '.' + format;
                        link.click();
                    } else {
                        WCST.showError(response.data || 'Export failed.');
                    }
                },
                error: function() {
                    WCST.showError('Export failed.');
                }
            });
        },

        getOrderStatusClass: function(status) {
            var statusClasses = {
                'completed': 'success',
                'processing': 'warning',
                'failed': 'error',
                'cancelled': 'error',
                'refunded': 'default'
            };
            return statusClasses[status] || 'default';
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WCST.init();
    });

})(jQuery); 