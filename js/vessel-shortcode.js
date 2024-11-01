/**
 * Created by Aaron Allen on 10/15/2018.
 */

(function ($, _) {
    'use strict';

    var insertButton;
    var cachedCampaigns = null;

    $(function () {
        insertButton = $('.vessel-insert-campaign');
        insertButton.click(chooseCampaign)
    });

    function chooseCampaign() {
        //var frame = new CampaignsFrame();
//
        //frame.open();

        var modal = new VesselModal();

        modal.open();
    }

    function insertCampaign(campaignId) {
        var shortCode = new wp.shortcode({
            tag: 'vessel-campaign',
            attrs: { id: campaignId },
            type: 'single'
        });

        wp.media.editor.insert(shortCode.string());
    }

    /**
     * Get the campaigns for this account from vapi
     * returns a cache if exists
     * @param apiKey
     * @param apiUrl
     * @param callback
     * @returns {*}
     */
    function getCampaigns(apiKey, apiUrl, callback) {
        if (cachedCampaigns) {
            return callback && callback(cachedCampaigns);
        }

        var url = apiUrl + "deliver/" + apiKey + "/campaigns";

        $.get(url, {}, function (data) {
            cachedCampaigns = data;

            callback && callback(data);
        });
    }

    var VesselModal = wp.Backbone.View.extend({
        tagName: 'div',
        template: wp.template('vessel-campaign-modal'),

        events: {
            'click .media-modal-backdrop, .media-modal-close': 'escapeHandler',
            'keydown': 'keydown'
        },

        initialize: function () {
            _.defaults(this.options, {
                container: document.body,
                title: 'Choose a Campaign'
            });
        },

        loadCampaigns: function() {
            var selectionWrapper = this.selectionWrapper = $('#vessel-selection-wrapper');

            var apiUrl = selectionWrapper.data('url');
            var apiKey = selectionWrapper.data('key');
            var self = this;

            // get the campaigns
            getCampaigns(apiKey, apiUrl, function (campaigns) {
                if (!campaigns || campaigns.length === 0) {
                    selectionWrapper.html("<span>You don't have any campaigns yet.</span> <a href='https://wzgd-central.com/create-account' target='_blank'>Let's make some!</a>")
                } else {
                    self.buildCampaignSelector(campaigns);
                }
            })
        },

        buildCampaignSelector: function(campaigns) {
            this.selectionWrapper.empty();
            // build the selector
            var selector = $('<select>');

            selector.append($('<option value="null">Select one...</option>'));

            _.each(campaigns, function (campaign) {
                var option = $('<option value="' + campaign.id + '">' + campaign.title + '</option>');
                selector.append(option);
            });

            this.selectionWrapper.append(selector);

            var self = this;
            // insert the short code when a selection is made
            selector.change(function () {
                var campaignId = selector.val();

                if (campaignId === 'null') return;

                insertCampaign(campaignId);
                // close the modal
                self.escape();
            });
        },

        /**
         * @returns {wp.media.view.Modal} Returns itself to allow chaining
         */
        attach: function() {
            if ( this.views.attached ) {
                return this;
            }

            if ( ! this.views.rendered ) {
                this.render();
            }

            this.$el.appendTo( this.options.container );

            // Manually mark the view as attached and trigger ready.
            this.views.attached = true;
            this.views.ready();

            this.loadCampaigns();

            return this;
        },

        /**
         * @returns {wp.media.view.Modal} Returns itself to allow chaining
         */
        detach: function() {
            if ( this.$el.is(':visible') ) {
                this.close();
            }

            this.$el.detach();
            this.views.attached = false;
            return this;
        },

        /**
         * @returns {wp.media.view.Modal} Returns itself to allow chaining
         */
        open: function() {
            var $el = this.$el,
                mceEditor;

            if ( $el.is(':visible') ) {
                return this;
            }

            this.clickedOpenerEl = document.activeElement;

            if ( ! this.views.attached ) {
                this.attach();
            }

            // Disable page scrolling.
            $( 'body' ).addClass( 'modal-open' );

            $el.show();

            // Try to close the onscreen keyboard
            if ( 'ontouchend' in document ) {
                if ( ( mceEditor = window.tinymce && window.tinymce.activeEditor ) && ! mceEditor.isHidden() && mceEditor.iframeElement ) {
                    mceEditor.iframeElement.focus();
                    mceEditor.iframeElement.blur();

                    setTimeout( function() {
                        mceEditor.iframeElement.blur();
                    }, 100 );
                }
            }

            // Set initial focus on the content instead of this view element, to avoid page scrolling.
            this.$( '.media-modal' ).focus();
        },

        /**
         * @param {Object} options
         * @returns {wp.media.view.Modal} Returns itself to allow chaining
         */
        close: function( options ) {
            if ( ! this.views.attached || ! this.$el.is(':visible') ) {
                return this;
            }

            // Enable page scrolling.
            $( 'body' ).removeClass( 'modal-open' );

            // Hide modal and remove restricted media modal tab focus once it's closed
            this.$el.remove();//.hide().undelegate( 'keydown' );

            // Put focus back in useful location once modal is closed.
            if ( null !== this.clickedOpenerEl ) {
                this.clickedOpenerEl.focus();
            } else {
                $( '#wpbody-content' ).focus();
            }

            //this.selectionWrapper.remove();

            return this;
        },

        /**
         * @returns {wp.media.view.Modal} Returns itself to allow chaining
         */
        escape: function() {
            return this.close({ escape: true });
        },

        /**
         * @param {Object} event
         */
        escapeHandler: function( event ) {
            event.preventDefault();
            this.escape();
        },
    });
}(jQuery, _));