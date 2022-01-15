// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define(['jquery', 'core/custom_interaction_events', 'core/log', 'core/pubsub'],
    function($, CustomEvents, Log, PubSub) {

        var SELECTORS = {
            TOGGLE_REGION: '[data-region="sidebar-toggle"]',
            TOGGLE_ACTION: '.sidebar-toggle',
            BODY: 'body',
            DRAWER: 'nav-sidebar',
            OVERLAY: 'overlay-sidebar'
        };

        var small = $(document).width() < 768;

        /**
         * Constructor for the Sidebar.
         *
         */
        var Sidebar = function() {

            if (!$(SELECTORS.TOGGLE_REGION).length) {
                Log.debug('Page is missing a drawer region');
            }

            if (!$(SELECTORS.TOGGLE_ACTION).length) {
                Log.debug('Page is missing a drawer toggle link');
            }

            var drawer = $(document.getElementById(SELECTORS.DRAWER));
            drawer.on('mousewheel DOMMouseScroll', this.preventPageScroll);

            this.registerEventListeners();

            if (small) {
                this.closeAll();
            }
        };

        Sidebar.prototype.closeAll = function() {
            $(SELECTORS.TOGGLE_REGION).each(function(index, ele) {
                var trigger = $(ele).find(SELECTORS.TOGGLE_ACTION);
                var drawer = $(document.getElementById(SELECTORS.DRAWER));
                var overlay = $(document.getElementById(SELECTORS.OVERLAY));

                trigger.attr('aria-expanded', 'false');
                drawer.attr('aria-hidden', 'true');
                drawer.removeClass('active');
                overlay.removeClass('active');
            });
        };

        /**
         * Open / close the blocks drawer.
         *
         * @method toggleSidebar
         * @param {Event} e
         */
        Sidebar.prototype.toggleSidebar = function(e) {
            var target = $(e.target);
            var drawer = $(document.getElementById(SELECTORS.DRAWER));
            var body = $(SELECTORS.BODY);
            var overlay = $(document.getElementById(SELECTORS.OVERLAY));

            body.addClass('drawer-ease');
            var open = target.attr('aria-expanded') == 'true';
            if (!open) {
                // Open.
                drawer.attr('aria-expanded', 'true');
                drawer.attr('aria-hidden', 'false');
                drawer.addClass('active');
                drawer.focus();

                overlay.attr('aria-expanded', 'true');
                overlay.attr('aria-hidden', 'false');
                overlay.addClass('active');

                $(SELECTORS.TOGGLE_ACTION).each(function(index, element) {
                    $(element).attr('aria-expanded', 'true');
                });
            } else {
                // Close.
                drawer.attr('aria-expanded', 'false');
                drawer.attr('aria-hidden', 'true');
                drawer.removeClass('active');

                overlay.attr('aria-expanded', 'false');
                overlay.attr('aria-hidden', 'true');
                overlay.removeClass('active');

                $(SELECTORS.TOGGLE_ACTION).each(function(index, element) {
                    $(element).attr('aria-expanded', 'false');
                });
            }

            // Publish an event to tell everything that the drawer has been toggled.
            // The drawer transitions closed so another event will fire once teh transition
            // has completed.
            PubSub.publish('nav-drawer-toggle-start', open);
        };

        /**
         * Prevent the page from scrolling when the drawer is at max scroll.
         *
         * @method preventPageScroll
         * @param  {Event} e
         */
        Sidebar.prototype.preventPageScroll = function(e) {
            var delta = e.wheelDelta || (e.originalEvent && e.originalEvent.wheelDelta) || -e.originalEvent.detail,
                bottomOverflow = (this.scrollTop + $(this).outerHeight() - this.scrollHeight) >= 0,
                topOverflow = this.scrollTop <= 0;

            if ((delta < 0 && bottomOverflow) || (delta > 0 && topOverflow)) {
                e.preventDefault();
            }
        };

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        Sidebar.prototype.registerEventListeners = function() {
            $(SELECTORS.TOGGLE_ACTION).each(function(index, element) {
                CustomEvents.define($(element), [CustomEvents.events.activate]);
                $(element).on(CustomEvents.events.activate, function(e, data) {
                    this.toggleSidebar(data.originalEvent);
                    data.originalEvent.preventDefault();
                }.bind(this));
            }.bind(this));

            $(SELECTORS.SECTION).click(function() {
                if (small) {
                    this.closeAll();
                }
            }.bind(this));

            // Publish an event to tell everything that the drawer completed the transition
            // to either an open or closed state.
            $(SELECTORS.DRAWER).on('webkitTransitionEnd msTransitionEnd transitionend', function(e) {
                var drawer = $(e.target).closest(SELECTORS.DRAWER);
                var open = drawer.attr('aria-hidden') == 'false';
                PubSub.publish('nav-drawer-toggle-end', open);
            });
        };

        return {
            'init': function() {
                return new Sidebar();
            }
        };
    });