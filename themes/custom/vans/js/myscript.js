/**
     * @file
     * Global utilities.
     *
     */
(function ($, Drupal) {
   'use strict';
    Drupal.behaviors.drupalanswers = {
       attach: function (context, settings) {
         MicroModal.init({
           onShow: modal => console.info(`${modal.id} is shown`), // [1]
           onClose: modal => console.info(`${modal.id} is hidden`), // [2]
           openTrigger: 'data-custom-open', // [3]
           closeTrigger: 'data-custom-close', // [4]
           disableScroll: true, // [5]
           disableFocus: false, // [6]
           awaitOpenAnimation: false, // [7]
           awaitCloseAnimation: false, // [8]
           debugMode: true // [9]
        });


        //APpend Vote to main Question Section
        jQuery(".middle-quetion").each(function (id, doEle) {
          var t = jQuery(this).find(".vote-result p").text();
          jQuery(this).find(".append-vote  p").text(t);
        });
       $('.vote-result p').hide();

       }
    };
})(jQuery, Drupal);
