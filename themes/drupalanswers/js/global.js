/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.drupalanswers = {
    attach: function (context, settings) {

    }
  };
	
	// $Id$

/**
 * This disables the submit, preview & add-more buttons while a file upload is in progress.
 */

// Disable buttons
Drupal.behaviors.waitSave = function (context) {
  $('.form-file', context).each(function () {
    $(this).change(function() {
      if($(this).val() != '') {
        $('#edit-field-datafiles-field-datafiles-add-more').attr('disabled','disabled').fadeTo(200,.20);
        $('#edit-submit').attr('disabled','disabled').fadeTo(200,.20);
        $('#edit-preview').attr('disabled','disabled').fadeTo(200,.20);
      }
    });
  });
}

// Enable buttons
Drupal.behaviors.readySave = function (context) {
   var queued = 0;
   $('#field-datafiles-items').find('.form-file').each(function(){
      if ( jQuery(this).val() !='' ) {
         queued++;
      }
   });
   if (queued === 0) {
      $('#edit-field-datafiles-field-datafiles-add-more').removeAttr('disabled').fadeTo(200,1);
      $('#edit-submit').removeAttr('disabled').fadeTo(200,1);
      $('#edit-preview').removeAttr('disabled').fadeTo(200,1);
   }
}

})(jQuery, Drupal);

