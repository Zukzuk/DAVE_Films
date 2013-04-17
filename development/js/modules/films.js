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
      var device_changed = app.model.setup_device();
      if (device_changed) player_resize();
      $(window).resize(function(event) {
        device_changed = app.model.setup_device();
        if (device_changed) player_resize();
      });
    }

    // Add menu outside the wrapper
    add_menu();

    // Setup header menu scrolling.
    app.model.scroll_interval = {};
    var wrapper = $('.wrapper');
    wrapper.resize(function(event) {
      app.model.setup_window();
      set_search();
    });
    wrapper.scroll(function(event) {
      app.model.setup_window();
      set_search();
    });

    // Add functionality to search bar
    $('#search-form').submit(function(event) {
      wrapper.animate({ scrollTop : 0 }, 0);
      on_search_films({
        search_term : $('#search-form input[name=search]').val(),
        search_retry : false
      });
      return false;
    });
    $('#search-form').keydown(function(event) {
        if (event.keyCode != 13) app.model.search_id = 0;
        // Reset if not <enter>
    });

    // Parse films
    app.data.get_all_films().success(function(response) {
      app.model.films_json = response;
      set_paging();
      parse_films();
      app.model.setup_device(true);
      synchronize_films();
    });

    // Add event listeners
    $(document).on('click', '.play-button', on_click_play);
    $(document).on('click', '.collection-button', on_click_collection);
    $(document).on('mouseleave', '.collection-menu', on_rollout_collection);
    $(document).on('click', '.suggest-button', on_click_suggest);
    $(document).on('click', '.synced-button', on_click_synced);
    $(document).on('mouseleave', '.synced-menu', on_rollout_synced);
    $(document).on('click', '.synced-film', on_click_synced_film);

    // Add external event deeplink
    app.model.external_uri_change = external_uri_change_films;
  }

  init();

///////////////
// EVENT HANDLING
///////////////

  /**
   * Handle external navigate events
   */
  function external_uri_change_films(data) {
    app.events.dispatch('NAVIGATE', {
      uri : data.uri
    });
  }

  /**
   * Handle page swap events
   */
  function on_click_page(event) {
    if ( typeof event === 'number') {
      app.model.current_offset = event;
    }
    else {
      app.model.current_offset = $(event.currentTarget).data('id');
    }
    $('#menu .page-button').removeClass('active');
    $(event.currentTarget).addClass('active');
    $('.wrapper').animate({ scrollTop : 0 }, 0);
    set_search();
    parse_films();
    app.model.setup_device(true);
  }

  /**
   * Handle click to play events
   */
  function on_click_play(event) {
    var target = $(event.currentTarget).parent();
    app.model.film_id = target.data('id');
    set_player_data(target.data('name'), target.data('directory'), target.data('film'), target.data('poster'));

    app.model.replace_routing_filter('play', app.model.film_name);
    //app.model.toggle_routing_filter('play', app.model.film_name);
    //app.model.toggle_routing_filter('id', app.model.film_id);

    app.events.dispatch('NAVIGATE', {
      uri : app.model.get_routing_uri()
    });
  }

  /**
   * Handle click to show collection contents events
   */
  function on_click_collection(event) {
    var target = $(event.currentTarget).parent();
    var menu = target.find('.collection-menu');
    menu.remove();

    app.data.get_collection(target.data('directory')).success(function(response) {
      //console.log(data);
      var entries = response.payload.data.entries;
      target.append('<div class="collection-menu"><h2>' + response.payload.name + '</h2><div class="inner-menu"><ul></ul></div></div>');
      menu = target.find('.collection-menu');
      menu.animate({ opacity : 0 }, 0).animate({ opacity : 1 }, 400); 
      menu.find('ul').animate({ opacity : 0 }, 0);
      for (var i = 0; i < entries.length; i++) {
        target
        	.find('.collection-menu .inner-menu ul')
        	.append('<li><a href="' + app.model.file_base + target.data('directory') + '/' + entries[i].filename + '" target="_blank" class="small awesome">' + entries[i].name + '</a></li>');
      };
      target.find('.collection-menu ul').delay(400).animate({
        opacity : 1
      }, 400);
    });
  }

  /**
   * Handle collection menu rollout events
   */
  function on_rollout_collection(event) {
    var target = $(event.currentTarget).parent();
    target.find('.collection-menu ul').animate({ opacity : 0 }, 300, function() {
      target.find('.collection-menu').animate({ opacity : 0 }, 300, function() {
        this.remove();
      });
    });
  }

  /**
   * Handle click to show synced contents events
   */
  function on_click_synced(event) {
    var target = $(event.currentTarget).parent();
    target.append('<div class="synced-tab">');
    target.append('<div class="synced-menu"><h2>Synced Content</h2><div class="inner-menu"><ul></ul></div></div>');
    var menu = target.find('synced-menu');
    menu.remove();
    menu = target.find('.synced-menu');
    menu.animate({ opacity : 0 }, 0).animate({ opacity : 1 }, 400);
    var list = menu.find('ul');
    list.animate({ opacity : 0 }, 0);
    for (var i = 0; i < app.model.synced_films.length; i++) {
    	if (app.model.synced_films[i].active) {
      	list.append(''+
      		'<li>'+
      			'<a href="javascript:void(0);" class="small awesome synced-film" data-film="' + app.model.synced_films[i].name + '">'+
      				'<i class="icon-exchange sync_activate"></i>'+
      				app.model.synced_films[i].name + 
      			'</a>'+
      		'</li>'
      	);
      }
      else {
      	list.append(''+
      		'<li>'+
      			'<a href="javascript:void(0);" title="Resource not synced : ' + app.model.synced_films[i].msg + '." class="small awesome synced-film" data-film="' + app.model.synced_films[i].name + '">'+
      				'<i class="icon-remove sync_deactivate"></i>'+
      				app.model.synced_films[i].name + 
      			'</a>'+
      		'</li>'
      	);
      }
    };
    list.delay(400).animate({ opacity : 1 }, 400);
  }

  /**
   * Handle synced menu rollout events
   */
  function on_rollout_synced(event) {
    $('.synced-tab').remove();
    var target = $(event.currentTarget).parent();
    target.find('.synced-menu ul').animate({ opacity : 0 }, 300, function() {
      target.find('.synced-menu').animate({ opacity : 0 }, 300, function() {
        this.remove();
      });
    });
  }

  /**
   * Handle click to synced film events
   */
  function on_click_synced_film(event) {
    var name = $(event.currentTarget).data('film').toLowerCase();
    app.model.search_id = 0;
    $('.wrapper').animate({ scrollTop : 0 }, 0);
    on_search_films({ search_term : name, search_retry : false });
  }

  /**
   * Handle random suggestion
   */
  function on_click_suggest(event) {
    var random_id = Math.floor(Math.random() * app.model.films_json.payload.length);
    var name = app.model.films_json.payload[random_id].name.toLowerCase();
    app.model.search_id = 0;
    $('.wrapper').animate({ scrollTop : 0 }, 0);
    on_search_films({ search_term : name, search_retry : false });
  }

  /**
   * Handle search input / output
   */
  function on_search_films(data) {
    var images = $('img');
    images.removeClass('grayscale').removeClass('disabled');
    for (var i = 0; i < app.model.films_json.payload.length; i++) {
      var name = app.model.films_json.payload[i].name.toLowerCase();
      if (name.search(data.search_term.toLowerCase()) > -1 && i > app.model.search_id) {
        app.model.search_id = i;
        var search_offset = Math.floor(i / app.model.films_per_page);
        if (app.model.search_offset != search_offset) {
          app.model.search_offset = search_offset;
          on_click_page(search_offset);
          $("#menu .page-button").removeClass('active');
          var page_button = $("#menu .menu-inner").find('[data-id="' + search_offset + '"]');
          $(page_button).addClass('active');
        }

        var target = $('.id-' + i);
        var images = $('img');
        images.addClass('grayscale');
        target.find('img').addClass('disabled');
        target.find('img').removeClass('grayscale');

        var pos = target.offset();
        $('.wrapper').animate({ scrollTop : pos.top - 150 }, 0);
        target.find('img')
        	.stop().delay(1000)
        	.animate({ opacity : 0.2 }, 200)
          .animate({ opacity : 1 }, 200)
          .animate({ opacity : 0.2 }, 200)
          .animate({ opacity : 1 }, 200)
          .animate({ opacity : 0.2 }, 200)
          .animate({ opacity : 1 }, 3000, function() {
            images.addClass('disabled');
        	});
        break;
      }
      if (i == app.model.films_json.payload.length - 1 && !data.search_retry) {
        app.model.search_id = 0;
        // Reset when end is reached
        data.search_retry = true;
        on_search_films(data);
      }
    }
  }

  /**
   * @deprecated
   * Use this if films are progressively streamed from http protocol
   */  
  function on_uri_change() {            
    if (app.model.check_routing_filter('id')) {
      // Deeplink found, hide gallery, show player
      if (app.model.film_id == '') {
        // Deeplink triggered through page load, get film data
        console.log('The current deeplink wants to open "' + app.model.routing.filters.play[0] + '". Get all relevant data from the stored app.model.films_json');

        app.model.film_id = app.model.routing.filters.id[0];
        var film = app.model.films_json.all_films[app.model.film_id];
        var deeplink_name = get_deeplink_name(film.name);

        set_player_data(deeplink_name, film.data.directory, film.data.filename, film.data.poster);

        // Retrigger film
        app.events.dispatch('NAVIGATE', {
            uri : app.model.get_routing_uri()
        });
        on_uri_change();
      }
      else {
        // Deeplink triggered through ajax load, film data available in model. HTTP protocol logic:
        $('#films').hide();
        $('#player').show();
        $('#player video').attr('poster', app.model.poster_uri).html('<source src="' + app.model.film_uri + '" type="video/mp4"></source>');
      }
    }
    else {
      // No deeplink, hide player, show gallery. HTTP protocol logic:
      delete ($('#player video'));
      $('#player video').remove();
      $('#player').hide().html('<video controls autoplay poster autobuffer preload="auto" name="media"></video>');
      player_resize();
    }

  }
  
  /**
   * Remove filters
   */
  function on_clear_filters(event) {
    // Not in use
  }

///////////////
// PAGE PARSING
///////////////

  /**
   * Parse loaded films
   */
  function parse_films() {
    var start_item = app.model.current_offset * app.model.films_per_page;
    var end_item = app.model.films_per_page + (app.model.current_offset * app.model.films_per_page);
    app.model.alphabet_count = 0;

    var target = $('#films ul');
    target.empty();
    for (var i = start_item; i < end_item; i++) {
      app.model.film_id = i;
      var film = app.model.films_json.payload[i];
      html = '';

      if (film) {
        // Add alphabet tiles
        if (!i) {
        	html = '<li class="film-alphabet"><h1 class="depth" title="#">#</li>';
        }
        alphabet_entry = get_alphabet_tile(film.name);
        html += alphabet_entry;

        var deeplink_name = get_deeplink_name(film.name);
        var search_name = deeplink_name.replace(/-/g, "+");
        app.model.film_uri = app.model.file_base + film.data.directory + '/' + film.data.filename;
        app.model.poster_uri = app.model.server_base + film.data.directory + '/' + film.data.poster;
				
				/**
				 * 3D flip
				 *
css
				.flip {
					-webkit-perspective: 800;
				  width: 400px;
				  height: 200px;
			    position: relative;
			    margin: 50px auto;
				}
				.flip .card.flipped {
				  -webkit-transform: rotatey(-180deg);
				}
				.flip .card {
				  width: 100%;
				  height: 100%;
				  -webkit-transform-style: preserve-3d;
				  -webkit-transition: 0.5s;
				}
				.flip .card .face {
				  width: 100%;
				  height: 100%;
				  position: absolute;
				  -webkit-backface-visibility: hidden ;
				  z-index: 2;
			    font-family: Georgia;
			    font-size: 3em;
			    text-align: center;
			    line-height: 200px;
				}
				.flip .card .front {
				  position: absolute;
				  z-index: 1;
			    background: black;
			    color: white;
			    cursor: pointer;
				}
				.flip .card .back {
				  -webkit-transform: rotatey(-180deg);
			    background: blue;
			    background: white;
			    color: black;
			    cursor: pointer;
				}
		    
script					
		    $('.flip').click(function(){
	        $(this).find('.card').addClass('flipped').mouseleave(function(){
	          $(this).removeClass('flipped');
	        });
	        return false;
		    });
				
html
				<div class="flip"> 
	        <div class="card"> 
            <div class="face front"> 
              Front
            </div> 
            <div class="face back"> 
              Back
            </div> 
	        </div> 
		    </div> 
				 *
				 */
				
        html += '<li class="film-large shadowed-background id-' + app.model.film_id + '" data-id="' + app.model.film_id + '" data-name="' + deeplink_name + '" data-directory="' + film.data.directory + '" data-film="' + film.data.filename + '" data-poster="' + film.data.poster + '">';

        if (!film.data.poster) {
            // Add empty frame
            html += '' + '<img src="" alt="" border="0" width="140" height="200" class="" />' + '<a href="javascript:void(0);" target="_self" class="empty-button"></a>';
        }
        else {
            if (!check_for_collection(film)) {
                // Film
                html += '' + '<img src="' + app.model.poster_uri + '" alt="' + film.name + '" border="0" width="140" height="200" class="" />' + '<a href="' + app.model.film_uri + '" target="_blank" class="play-button"></a>';
            }
            else {
                // Collection
                html += '' + '<img src="' + app.model.poster_uri + '" alt="' + film.name + '" border="0" width="140" height="200" class="" />' + '<a href="javascript:void(0);" target="_self" class="collection-button"></a>';
            }
        }

        if (!film.data.poster) {
            // Add search suggection
            html += '<div class="google-search">' + film.name + " " + film.year + "<br />" + '<a href="http://www.google.nl/search?q=' + search_name + '+' + film.year + '&hl=nl&tbo=d&source=lnms&tbm=isch&sa=X" target="_blank" >Filmposter...</a>' + '</div>';
        }

        // CRITICAL: filetype!=mp4 [nodub] [nosub] | NOTICE: [lowres] [screener] | INFO: [collection] [remake]
        if (film.data.filetype != "mp4") {
            html += '<div class="bookmark"><i class="icon-bookmark warning"></i><div class="text-bookmark light">' + film.data.filetype + '</div></div>';
        }
        if (film.data.count > 1 && !check_for_collection(film)) {
            html += '<div class="bookmark"><i class="icon-bookmark warning"></i><div class="text-bookmark light">' + film.data.count + 'X</div></div>';
        }
        if (!film.data.types && film.data.filetype == "mp4") {
            html += '<div class="bookmark"><i class="icon-bookmark hd"></i><div class="text-bookmark near-light">HD</div></div>';
        }
        if (film.data.types) {
          $.each(film.data.types, function(key, type) {
            switch(type) {
              //Info
              case "collection":
                html += '<div class="bookmark"><i class="icon-bookmark info"></i><div class="text-bookmark dark">CL</div></div>';
                break;
              case "remake":
                html += '<div class="bookmark"><i class="icon-bookmark info"></i><div class="text-bookmark dark">RM</div></div>';
                break;
              //Notice
              case "lowres":
                html += '<div class="bookmark"><i class="icon-bookmark notice"></i><div class="text-bookmark dark">LR</div></div>';
                break;
              case "screener":
                html += '<div class="bookmark"><i class="icon-bookmark notice"></i><div class="text-bookmark dark">SC</div></div>';
                break;
              //Warning
              case "nodub":
                html += '<div class="bookmark"><i class="icon-bookmark warning"></i><div class="text-bookmark light">DU</div></div>';
                break;
              case "nosub":
                html += '<div class="bookmark"><i class="icon-bookmark warning"></i><div class="text-bookmark light">SU</div></div>';
                break;
            }
          });
        }

        html += '</li>';
        target.append(html);
      }
    }
    app.model.film_id = '';
    var images = $('img');
    images.addClass('grayscale').animate({ opacity : 1 }, 1000, function() {
      images.addClass('disabled');
    });
  }

  /**
   * Add menu outside wrapper
   */
  function add_menu() {
    $('body').prepend('' + '<div id="menu">' + '<div class="menu-inner">' + '<a href="javascript:void(0);" class="small blue awesome synced-button"><i class="icon-cloud-upload"></i></a>' + '<a href="javascript:void(0);" class="small awesome logout-button" onclick="app.events.dispatch(\'DO_LOGOUT\'); return false;">Logout</a>' + '<a href="javascript:void(0);" class="small yellow awesome suggest-button"><i class="icon-random"></a>' + '<div class="paging"></div>' + '</div>' + '</div>');
    $('#menu .synced-button').hide();
  }

  /**
   * Set film paging
   */
  function set_paging() {
    app.model.num_of_pages = Math.ceil(app.model.films_json.payload.length / app.model.films_per_page);
    var html = '';
    for (var i = 0; i < app.model.num_of_pages; i++) {
      if (i == 0)
        html += '<a href="javascript:void(0);" class="small awesome page-button active" data-id="' + i + '">' + (i + 1) + '</a>';
      else
        html += '<a href="javascript:void(0);" class="small awesome page-button" data-id="' + i + '">' + (i + 1) + '</a>';
    };
    $('#menu .menu-inner .paging').append(html);
    $(document).on('click', '.page-button', on_click_page);
  }

  /**
   * Add alphabetic tiles
   */
  function get_alphabet_tile(name) {
    var html = '';
    var alphabet = '#abcdefghijklmnopqrstuvwxyz';
    var first_letter = name.substr(0, 1).toLowerCase();
    var alphabet_letter = alphabet.substr(app.model.alphabet_count, 1);

    if (first_letter != alphabet_letter && isNaN(first_letter)) {
      for (var i = 0; i < alphabet.length; i++) {
        if (first_letter == alphabet[i]) {
          app.model.alphabet_count = i;
          break;
        }
      };
      alphabet_letter = alphabet.substr(app.model.alphabet_count, 1);

      html += '' + '<li class="film-alphabet"><h1 class="depth" title="' + alphabet_letter.toUpperCase() + '">' + alphabet_letter.toUpperCase() + '</h1></li>';
    }

    return html;
  }
	
	/**
   * Synchronize new and reactivated films to the cloud
   */
  function synchronize_films() {
    var batch_length = 50;
    var sync_count = 0;
    var film_batch = [];
    var film_batches = [];
    var synced_films = [];
    
    // create film batches
    for (var i = 0; i < app.model.films_json.payload.length; i++) {
        if (!(i % batch_length) && i) {
            film_batches.push(film_batch);
            film_batch = [];
        }
        film_batch.push(app.model.films_json.payload[i]);
    };
    film_batches.push(film_batch);
		
		// prepare db for sync
		app.data.prepare_synchronization().success(function(response) {
			if(!response.error) {
				// loop through all film batches
				$.each(film_batches, function(key, film_batch) {
					// sync batch
		      app.data.synchronize_films(film_batch).success(function(response) {
		      	// log each batch
		        console.log(response);
		        sync_count++;
		        if (response.synced) {
		        	// add film to menu if added to db
		          $.each(response.films, function(key, synced_film) {
		              if(synced_film.msg != 'Film unchanged') synced_films.push(synced_film);
		          });
		        }
		        // check if all batches are synced
		        if (sync_count == film_batches.length) {
		        	// show synced button in menu if synced films exist
		          if (synced_films.length > 0) {
		            $('#menu .synced-button').show().animate({ opacity : 0 }, 0).animate({ opacity : 1 }, 300);
		          }
		          // add synced films to model for later use
		          app.model.synced_films = synced_films;
		          // log synced films
		          console.log(app.model.synced_films);
		        }
		        // finish sync
		        app.data.finish_synchronization().success(function(response) {
		        	console.log(response);
		        });
		      });
		    });
		 	}
	  });
  }

  /**
   * Fill the last row on a page with maximum amount of films
   */
  function fill_last_row() {
      // Not in use
  }

  /**
   * Check if an item is a collection
   */
  function check_for_collection(film) {
    var is_collection = false;
    if (film.data.types) {
      for (var i = 0; i < film.data.types.length; i++) {
        if (film.data.types[i] == 'collection') {
	        is_collection = true;
	        break;
        }
      };
    }
    return is_collection;
  }

  /**
   * Get sanitized deeplink name
   */
  function get_deeplink_name(name) {
    var deeplink_name = name.toLowerCase();
    deeplink_name = deeplink_name.replace(" - ", "-");
    deeplink_name = escape(deeplink_name);
    deeplink_name = deeplink_name.replace(/%20/g, "-");
    return deeplink_name;
  }

  /**
   * Set data to play a film
   */
  function set_player_data(name, directory, file, poster) {
    app.model.film_name = name;
    app.model.film_file = file;
    app.model.film_poster = poster;
    app.model.film_uri = app.model.file_base + directory + '/' + file;
    app.model.poster_uri = app.model.server_base + directory + '/' + poster;
  }

  /**
   * Animate header search
   */
  function set_search() {
    var search = $('#search');
    if (app.model.scroll_top > 50) {
      search.css({
        'top' : (app.model.scroll_top - 80) + 'px',
        '-webkit-transition' : 'none',
        '-moz-transition' : 'none',
        '-o-transition' : 'none',
        'transition' : 'none'
      });
      clearInterval(app.model.scroll_interval);
      app.model.scroll_interval = setInterval(function() {
        search.css({
          'top' : (app.model.scroll_top - 5) + 'px',
          '-webkit-transition' : 'all 0.3s ease-out',
          '-moz-transition' : 'all 0.3s ease-out',
          '-o-transition' : 'all 0.3s ease-out',
          'transition' : 'all 0.3s ease-out'
        });
      }, 900);
    }
    else {
      search.css({
        'top' : (app.model.scroll_top - 5) + 'px',
        '-webkit-transition' : 'none',
        '-moz-transition' : 'none',
        '-o-transition' : 'none',
        'transition' : 'none'
      });
    }
  }

  /**
   * @deprecated
   * Used for http progressive streaming.
   * Stops streaming after 10 minutes or so.
   */
  function player_resize() {
    switch(app.model.device.type) {
      case 'mobile_small':
      case 'mobile':
      case 'tablet_small':
        $('#player video').attr('width', '320').attr('height', '240');
        // QVGA
        break;

      case 'tablet':
        $('#player video').attr('width', '480').attr('height', '320');
        // HVGA
        break;

      case 'desktop':
        $('#player video').attr('width', '800').attr('height', '600');
        // SVGA
        break;

      case 'wide':
        $('#player video').attr('width', '1280').attr('height', '720');
        // HD 720
        break;

      case 'full':
        $('#player video').attr('width', '1920').attr('height', '1080');
        // HD 1080
        break;
    }
  }

})();
