
			
jQuery('#node-ask-question-form').ajaxStart(function(){  
	    
				$('#edit-submit').attr('disabled', 'disabled');
        $('#edit-preview').attr('disabled', 'disabled');
        $('#edit-delete').attr('disabled', 'disabled');
	 } );
		
jQuery('#node-ask-question-form').ajaxSuccess(function(){  
	
	  $('#edit-submit').removeAttr('disabled');
        $('#edit-preview').removeAttr('disabled');
        $('#edit-delete').removeAttr('disabled');
} );