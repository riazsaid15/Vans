(function($) {
  Drupal.behaviors.login_user = {
     attach : function (context, settings){

      $('.login-user-picture').once('open').on('click',function(e) {

        if($(this).hasClass('open')) {
          $('.login-user-dropdown').css('display', 'none');
          $(this).removeClass('open');
        } else {
          $('.login-user-dropdown').css('display', 'block');
          $(this).addClass('open');
        }
        
      });

      $(document).once('clk').on('click',function(e) 
      {
          var container = $(".login-user-picture");
          // if the target of the click isn't the container nor a descendant of the container
          if (!$(e.target).hasClass("login-user-picture") && $(e.target).parents(".login-user-dropdown").length === 0) 
          {
            $('.login-user-dropdown').css('display', 'none');
            // container.removeClass('open');
          } 
          // else if (container.hasClass('open')){
          //   $('.login-user-dropdown').css('display', 'none');
          //   container.removeClass('open');
          // } else {
          //   $('.login-user-dropdown').css('display', 'block');
          //   container.addClass('open');
          // }
      });

      
    
     }
  }

  

})(jQuery);
