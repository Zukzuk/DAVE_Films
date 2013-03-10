/**
 * @Author: Dave Timmerman
 */

(function()
{
	function init()
	{
		// reset event listeners
		$(document).off();
		$(window).off();
	
		// responsive javascript / css behaviour
		if (app.model.responsive)
		{
			var device_changed = app.model.setup_device();
			//if(device_changed) player_resize();
			$(window).resize(function()
			{
				device_changed = app.model.setup_device();
				//if(device_changed) player_resize();
			});
		}
		
		// setup header menu scrolling.
		app.model.scroll_interval = {};
		$('.wrapper').resize(function()
		{
			app.model.setup_window();
			set_header_menu();
		});
		$('.wrapper').scroll(function()
		{
			app.model.setup_window();
			set_header_menu();
		});
		
		// remove some deprecated stuff
		$('#player').hide();
		
		// add functionality to search bar
		$('#search').submit(function()
		{
			$('.wrapper').animate( { scrollTop : 0 }, 0);
			on_search_films( { search_term : $('input[name=search]').val() });
			return false;
		});
		
		// parse films
		app.data.get_all_films().success(function(response)
		{
			app.model.films_json = response;
			set_paging();
			parse_films();
			on_uri_change();
		});
		
				// add event listeners
		$(document).on('click', '.play-button', on_click_play);
		$(document).on('click', '.collection-button', on_click_collection);
		$(document).on('mouseleave', '.collection-menu', on_rollout_collection);
		
		// add external event deeplink
		app.model.external_uri_change = external_uri_change_films;
	}
	init();
	
	
////////////////////////////////////////////////////////////////////////////	
// EVENT HANDLING 
////////////////////////////////////////////////////////////////////////////	

	
	// handle external navigate events
	function external_uri_change_films(data)
	{
		app.events.dispatch('NAVIGATE', { uri : data.uri }); 
		on_uri_change();
	}
	
	// handle page swap events
	function on_click_page(event)
	{
		app.model.current_offset = $(event.currentTarget).data('id');
		$('.wrapper').animate( { scrollTop : 0 }, 0);
		set_header_menu();
		parse_films();
		on_uri_change();
	}
	
	// handle click to play events
	function on_click_play(event)
	{
		var target = $(event.currentTarget).parent();

		app.model.film_id = target.data('id');
		set_player_data(target.data('name'), target.data('directory'), target.data('film'), target.data('poster'));

		app.model.replace_routing_filter('play', app.model.film_name);
		//app.model.toggle_routing_filter('play', app.model.film_name);
		//app.model.toggle_routing_filter('id', app.model.film_id);

		app.events.dispatch('NAVIGATE', { uri : app.model.get_routing_uri() });
		on_uri_change();
	}

	// handle click to show collection contents events
	function on_click_collection(event)
	{
		var target = $(event.currentTarget).parent();
		var menu = target.find('.collection-menu');
		menu.remove();

		app.data.get_collection(target.data('directory')).success(function(response)
		{
			//console.log(data);
			var entries = response.payload.data.entries;
			target.append('<div class="collection-menu"><div class="inner-menu"><ul></ul></div></div>');
			menu = target.find('.collection-menu');
			menu.animate({opacity:0},0).animate({opacity:1},400);
			menu.find('ul').animate({opacity:0},0);
			for (var i = 0; i < entries.length; i++)
			{
				target.find('.collection-menu .inner-menu ul').append('<li><a href="' + app.model.file_base + target.data('directory') +'/'+ entries[i].filename + '" target="_blank" class="small awesome">' + entries[i].name + '</a></li>');
			};
			target.find('.collection-menu ul').delay(400).animate({opacity:1},400);
		});
	}
	
	// handle collection menu rollout events
	function on_rollout_collection(event)
	{
		var target = $(event.currentTarget).parent();
		target.find('.collection-menu').animate({opacity:0},300, function(){
			this.remove();
		});
	}
	
	// handle filter changes
	function on_uri_change()
	{
		/**
		 * Use this if films are progressively streamed from http protocol
		 *
		 if(app.model.check_routing_filter('id'))
		 {
			 // deeplink found, hide gallery, show player
			 if(app.model.film_id == '')
			 {
				 // deeplink triggered through page load, get film data
				 console.log('The current deeplink wants to open "' + app.model.routing.filters.play[0] + '". Get all relevant data from the stored app.model.films_json');
		
				 app.model.film_id = app.model.routing.filters.id[0];
				 var film = app.model.films_json.all_films[app.model.film_id];
				 var deeplink_name = get_deeplink_name(film.name);
		
				 set_player_data(deeplink_name, film.data.directory, film.data.filename, film.data.poster);
		
				 // retrigger film
				 app.events.dispatch('NAVIGATE', { uri:app.model.get_routing_uri() });
				 on_uri_change();
			 }
			 else
			 {
				 // deeplink triggered through ajax load, film data available in model
				 // HTTP protocol logic:
				 $('#films').hide();
				 $('#player').show();
				 $('#player video').attr('poster', app.model.poster_uri).html('<source src="' + app.model.film_uri + '" type="video/mp4"></source>');
			 }
		 }
		 else
		 {
			 // no deeplink, hide player, show gallery
			 // HTTP protocol logic:
			 delete($('#player video'));
			 $('#player video').remove();
			 $('#player').hide().html('<video controls autoplay poster autobuffer preload="auto" name="media"></video>');
			 player_resize();
		 }
		 */
	}
	
	// remove filters
	function on_clear_filters(event)
	{
		/**
		 * Not in use
		 */
	}
	
	// handle search input / output
	function on_search_films(data)
	{
		var images = $('img');
		images.removeClass('grayscale').removeClass('disabled');
		for (var i = 0; i < app.model.films_json.payload.length; i++)
		{
			var name = app.model.films_json.payload[i].name.toLowerCase();
			// TODO: search further than first -> name != app.model.last_search_term
			if (name.search(data.search_term.toLowerCase()) > -1)
			{
				var target = $('.id-' + i);
				var images = $('img');
				images.addClass('grayscale');
				target.find('img').addClass('disabled');
				target.find('img').removeClass('grayscale');
				
				var pos = target.offset();
				$('.wrapper').animate( { scrollTop : pos.top - 150 }, 0);
				target
					.stop()
					.delay(1000)
					.animate({opacity:0.2}, 200)
					.animate({opacity:1}, 200)
					.animate({opacity:0.2}, 200)
					.animate({opacity:1}, 200)
					.animate({opacity:0.2}, 200)
					.animate({opacity:1}, 3000, function()
					{
						images.addClass('disabled');	
					}
				);							
				break;
			}
		}
	}


////////////////////////////////////////////////////////////////////////////	
// PAGE PARSING
////////////////////////////////////////////////////////////////////////////	

	
	// parse loaded films
	function parse_films()
	{
		var start_item = app.model.current_offset*app.model.films_per_page;
		var end_item = app.model.films_per_page+(app.model.current_offset*app.model.films_per_page);
		//end_item += fill_last_row();
		
		var target = $('#films ul');
		target.empty();
		
		for(var i = start_item; i < end_item; i++)
		{
			app.model.film_id = i;
			var film = app.model.films_json.payload[i];
			html = '';

			// Add alphabet tiles
			if (!i)	html = '<li class="film-alphabet"><h1 class="depth" title="#">#</li>';
			alphabet_entry = get_alphabet_tile(film.name);
			html += alphabet_entry;

			var deeplink_name = get_deeplink_name(film.name);
			var search_name = deeplink_name.replace(/-/g, "+");
			app.model.film_uri = app.model.file_base + film.data.directory + '/' + film.data.filename;
			app.model.poster_uri = app.model.server_base + film.data.directory + '/' + film.data.poster;

			html += '<li class="film-large shadowed-background id-' + app.model.film_id + '" data-id="' + app.model.film_id + '" data-name="' + deeplink_name + '" data-directory="' + film.data.directory + '" data-film="' + film.data.filename + '" data-poster="' + film.data.poster + '">';

			if (!film.data.poster) {
				// add empty frame
				html += '' + 
					'<img src="" alt="" border="0" width="140" height="200" class="" />' + 
					'<a href="javascript:void(0);" target="_self" class="empty-button"></a>';
			}
			else
			{
				if (!check_for_collection(film)) {
					// film
					html += '' + 
						'<img src="' + app.model.poster_uri + '" alt="' + film.name + '" border="0" width="140" height="200" class="" />' + 
						'<a href="' + app.model.film_uri + '" target="_blank" class="play-button"></a>';
				}
				else {
					// collection
					html += '' + 
						'<img src="' + app.model.poster_uri + '" alt="' + film.name + '" border="0" width="140" height="200" class="" />' + 
						'<a href="javascript:void(0);" target="_self" class="collection-button"></a>';
				}
			}

			if (!film.data.poster) {
				// add search suggection
				html += '<div class="google-search">' + 
						film.name + " " + film.year + "<br />" + 
						'<a href="http://www.google.nl/search?q=' + search_name + '+' + film.year + '&hl=nl&tbo=d&source=lnms&tbm=isch&sa=X" target="_blank" >Google filmposter >></a>' + 
					'</div>';
			}

			/**
			 * ICONS: icon-certificate, icon-bookmark, icon-info-sign, icon-warning-sign
			 * CRITICAL: filetype != mp4, [nodub], [nosub]
			 * NOTICE: [lowres], [screener]
			 * INFO: [collection], [remake]
			 */
			if (film.data.filetype != "mp4") {
				html += '<div class="bookmark"><i class="icon-bookmark warning"></i><div class="text-bookmark light">' + film.data.filetype + '</div></div>';
			}
			if (!film.data.types && film.data.filetype == "mp4") {
				html += '<div class="bookmark"><i class="icon-bookmark hd"></i><div class="text-bookmark near-light">HD</div></div>';
			}
			if (film.data.types) {
				$.each(film.data.types, function(key, type) {
					switch(type)
					{
						//info
						case "collection":
							html += '<div class="bookmark"><i class="icon-bookmark info"></i><div class="text-bookmark dark">CL</div></div>';
							break;
						case "remake":
							html += '<div class="bookmark"><i class="icon-bookmark info"></i><div class="text-bookmark dark">RM</div></div>';
							break;
						//notice
						case "lowres":
							html += '<div class="bookmark"><i class="icon-bookmark notice"></i><div class="text-bookmark dark">LR</div></div>';
							break;
						case "screener":
							html += '<div class="bookmark"><i class="icon-bookmark notice"></i><div class="text-bookmark dark">SC</div></div>';
							break;
						//warning
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
		app.model.film_id = '';
		var images = $('img');
		images.addClass('grayscale').animate({opacity:1}, 1000, function()
		{
			images.addClass('disabled');	
		});
	}
	
	// set film paging
	function set_paging()
	{
		app.model.num_of_pages = Math.floor(app.model.films_json.payload.length/app.model.films_per_page);
		var html = '';
		for (var i = app.model.num_of_pages; i >= 0; i--) 
		{
			html += '<a href="javascript:void(0);" class="small awesome page-button" data-id="'+i+'">'+(i+1)+'</a>';
		};
		$('#head .menu').append(html);
		$(document).on('click', '.page-button', on_click_page);
	}
	
	// add alphabetic tiles
	function get_alphabet_tile(name)
	{
		var html = '';
		var alphabet = '#abcdefghijklmnopqrstuvwxyz';
		var first_letter = name.substr(0, 1).toLowerCase();
		var alphabet_letter = alphabet.substr(app.model.alphabet_count, 1);

		if (first_letter != alphabet_letter && isNaN(first_letter))
		{
			for (var i = 0; i < alphabet.length; i++)
			{
				if (first_letter == alphabet[i])
				{
					app.model.alphabet_count = i;
					break;
				}
			};
			alphabet_letter = alphabet.substr(app.model.alphabet_count, 1);

			html += '' + '<li class="film-alphabet"><h1 class="depth" title="' + alphabet_letter.toUpperCase() + '">' + alphabet_letter.toUpperCase() + '</h1></li>';
		}

		return html;
	}
	
	// fill the last row on a page with maximum amount of films
	function fill_last_row()
	{
		
	}
	
	// check if an item is a collection
	function check_for_collection(film)
	{
		var is_collection = false;
		if (film.data.types)
		{
			for (var i = 0; i < film.data.types.length; i++)
			{
				if (film.data.types[i] == 'collection')
				{
					is_collection = true;
					break;
				}
			};
		}
		return is_collection;
	}
	
	// get sanitized deeplink name
	function get_deeplink_name(name)
	{
		var deeplink_name = name.toLowerCase();
		deeplink_name = deeplink_name.replace(" - ", "-");
		deeplink_name = escape(deeplink_name);
		deeplink_name = deeplink_name.replace(/%20/g, "-");
		return deeplink_name;
	}
	
	// set data to play a film
	function set_player_data(name, directory, file, poster)
	{
		app.model.film_name = name;
		app.model.film_file = file;
		app.model.film_poster = poster;
		app.model.film_uri = app.model.file_base + directory + '/' + file;
		app.model.poster_uri = app.model.server_base + directory + '/' + poster;
	}
	
	// animate header menu
	function set_header_menu()
	{
		if(app.model.scroll_top > 50)
		{
			$('#head').css( 
			{
				'top': (app.model.scroll_top-80)+'px', '-webkit-transition': 'none', '-moz-transition': 'none', '-o-transition': 'none', 'transition': 'none'
			});
			clearInterval(app.model.scroll_interval);
			app.model.scroll_interval = setInterval(function() 
			{ 
				$('#head').css( 
				{
					'top': app.model.scroll_top+'px', '-webkit-transition': 'all 0.3s ease-out', '-moz-transition': 'all 0.3s ease-out', '-o-transition': 'all 0.3s ease-out', 'transition': 'all 0.3s ease-out'
				});
			}, 900);
		}
		else
		{
			$('#head').css( 
			{
				'top': app.model.scroll_top+'px', '-webkit-transition': 'all 0.3s', '-moz-transition': 'all 0.3s', '-o-transition': 'all 0.3s', 'transition': 'all 0.3s'
			});
		}
	}

	// Deprecated
	// Used for http progressive streaming. 
	// Stops streaming after 10 minutes or so.
	function player_resize()
	{
		switch(app.model.device.type)
		{
			case 'mobile_small':
			case 'mobile':
			case 'tablet_small':
				$('#player video').attr('width', '320').attr('height', '240'); // QVGA
				break;
		
			case 'tablet':
				$('#player video').attr('width', '480').attr('height', '320'); // HVGA
				break;
		
			case 'desktop':
				$('#player video').attr('width', '800').attr('height', '600'); // SVGA
				break;
		
			case 'wide':
				$('#player video').attr('width', '1280').attr('height', '720'); // HD 720
				 reak;
		
			case 'full':
				$('#player video').attr('width', '1920').attr('height', '1080'); // HD 1080
				break;
		}
	}

})(); 