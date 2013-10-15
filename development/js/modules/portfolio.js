/**
 * @author: Dave Timmerman
 */
(function() {
        
      function init() {
  
      // Reset event listeners
      $(document).off();
      $(window).off();
      
      // responsive javascript / css behaviour
      if (app.model.responsive) {
        handle_resize();
        $(window).resize(function(event) {
          handle_resize();
        });
      }
      app.model.setup_device(true);
      
      // Start highlights
      var int = self.setInterval(function(){
        highlight_projects();
      }, 4000);
      highlight_projects();
      
      // Add event listeners
      $(document).on('mouseover', '#portfolio .highlight li', on_mouseover_highlight);
      $(document).on('mouseleave', '#portfolio .highlight li', on_mouseleave_highlight);
  
      // Add external event deeplink
      app.model.external_uri_change = external_uri_change_films;
    }
  
    init();
  
  ///////////////
  // EVENT HANDLING
  ///////////////
  
    /**
     * Handle external navigation events
     */
    function external_uri_change_films(data) {
      app.events.dispatch('NAVIGATE', {
        uri : data.uri
      });
      on_uri_change();
    }
  
    /**
     * Handle an event
     */
    function on_mouseover_highlight(event) {
      //console.log(event.target);
      if($(event.target).hasClass('html5')) $('.header .html5 .type').addClass('show');
      if($(event.target).hasClass('flash')) $('.header .flash .type').addClass('show');
      if($(event.target).hasClass('webapp')) $('.header .webapp .type').addClass('show');
    }
    
    /**
     * Handle an event
     */
    function on_mouseleave_highlight(event) {
      //console.log(event.target);
      if($(event.target).hasClass('html5')) $('.header .html5 .type').removeClass('show');
      if($(event.target).hasClass('flash')) $('.header .flash .type').removeClass('show');
      if($(event.target).hasClass('webapp')) $('.header .webapp .type').removeClass('show');
    }
    
  ///////////////
  // HELPER METHODS
  ///////////////
    
    /**
     * 
     */
    function handle_resize() {
      device_changed = app.model.setup_device();
      var img = $("#portfolio .highlight li .grayscale");
      var middle = (img.width()-img.parent().width()) / 2;
      img.css("margin", "0 0 0 -" + middle + "px");
    }
    
    /**
     * 
     */
    function highlight_projects() {
      var items = $('.projects a');
      var count = 0;
      //console.log(items);
      $.each(items, function(index) {
        $(this).stop().delay(count*60)
          .queue(function() { 
            $(this).addClass('active');
            remove_highlight($(this), count*8);
          }
        );        
        count++;
      });
    }
    
    /**
     * 
     */
    function remove_highlight(item, delay) {
      setTimeout(function() {
        item.removeClass('active');
      }, delay);
    }
  
  }
)();
