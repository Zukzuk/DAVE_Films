/*
 @Author: Mettin Parzinski, Dave Timmerman
*/


(function()
{
	var top_limit = 100,
        supports_svg = false,
        filters = {};
        countries = [],
        industries = [],
        
	init();
	
	function init()
	{
		//console.mp('^^ init()');
		$('.clear-filters, .all-filters').hide();
		
		$(document).off();	
		$(document).on('click', '.get-all-filters', toggle_filters);
		$(document).on('click', '.industry', on_click_industry);
		$(document).on('click', '.country', on_click_country);
		$(document).on('click', '.clear-filters', clear_filters);
		
		// check if SVG is supported
        if(window.SVGAngle || document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1"))
        {
            supports_svg = true;
        }
        
        // load all logos
		app.data.get_top100('').success(function(data)
		{
			parse_top100(data);
		});
		
		// add external event deeplink
		app.model.external_uri_change = external_uri_change_hpb_top100;
	}
	
	function external_uri_change_hpb_top100(data)
	{
		app.events.dispatch('NAVIGATE', { uri:data.uri });
		on_uri_change();
	}
	
	function parse_top100(data)
	{
		//console.mp('^^ parse_top100 data:', data);

        var $target = $('.list').find('ul');
        var html = '';

        $target.empty();

		for(var i = 0; i < data.top_list.length; i++)
		{
			var in_scope = i < top_limit ? 'in-scope' : 'out-of-scope';// in_scope = 'in-scope';
            var friendly_company_name = make_friendly(data.top_list[i].username);
            var filetype = supports_svg ? 'svg': 'png';
            
            if(!countries.contains(data.top_list[i].country_iso))
            {
                countries.push(data.top_list[i].country_iso);
            }
            if(!industries.contains(data.top_list[i].industry))
            {
                industries.push(data.top_list[i].industry);
            }

			html += '\
				<li class="block-large block-' + i + ' industry-'+ make_friendly(data.top_list[i].industry) + ' country-' + data.top_list[i].country_iso + ' ' + in_scope +' ">\
					<div class="met_stip_gestegen hidden"></div>\
					<a href="javascript:void(0)">\
						<span class="ranking-number">' + (i + 1) + '<span class="company-title hidden">'+data.top_list[i].username+'</span></span>\
						<span class="hpb-score">HPB-SCORE <span class="value">'+ data.top_list[i].target_ttm +'</span></span>\
						<img src="img/logos/'+ filetype +'/'+ friendly_company_name +'.'+ filetype +'" title="'+data.top_list[i].username+'" alt="'+data.top_list[i].username+' logo" class="company-logo"/>\
						<span class="industry-title">' + data.top_list[i].industry + '</span>\
					</a>\
				</li>';
		}		
		/*
		 * <ul class="social share">\
		    	<li class="facebook block-tiny"><p href="javascript:void(0);">F</p></li>\
		        <li class="twitter block-tiny"><p href="javascript:void(0);">T</p></li>\
		        <li class="linkedin block-tiny"><p href="javascript:void(0);">Li</p></li>\
	        </ul>\
		 */
		$target.append(html);
		
		/*	
		$.each(countries, function(i, value)
		{
			$('.countries-list').append('<li class="filter country country-'+ value +'" data-country-id="'+ value +'"><a href="javascript:void(0)">' + value + '</a></li>');
		});	
		*/   
		   
        on_uri_change();
        $('.industries-list .icon-ok-sign').delay(700).fadeOut(200).fadeIn(300).fadeOut(200).fadeIn(300);
	}
	
	function on_click_industry(event)
	{
		//console.mp('^^ on_click_industry()');
		var $target = $(event.currentTarget),
			clicked_industry = $target.data('industry');
		
		app.model.toggle_routing_filter('industry', clicked_industry);
		app.events.dispatch('NAVIGATE', { uri:app.model.get_routing_uri() });
		on_uri_change();
		// toggle status of menu item
	}
	
	function on_click_country(event)
	{
		//console.mp('^^ on_click_country()');
		var $target = $(event.currentTarget),
			clicked_country = $target.data('country-id');
		
		app.model.toggle_routing_filter('country', clicked_country);		
		app.events.dispatch('NAVIGATE', { uri:app.model.get_routing_uri() });
		on_uri_change();
		// toggle status of menu item
	}
	
	function on_uri_change(data)
	{
		console.mp('^^ on_uri_change()');
		
		// hide all items
		var li = $('.list ul').find('li');
		li.addClass('hidden');	
		li.find('.met_stip_gestegen').addClass('hidden')
		
		$('.all-filters .icon-ok-circle').removeClass('hidden');
		$('.all-filters .icon-ok-sign').addClass('hidden');
		
		// remove all filter options
		li.removeClass('filtered-industry filtered-country');
		
		if(	typeof app.model.routing.filters.industry != 'undefined' ||
			typeof app.model.routing.filters.country != 'undefined')
		{
			// if there is a filter active in the routing object
			open_filters();
			if(typeof app.model.routing.filters.industry != 'undefined')
			{
				for(i = 0; i < app.model.routing.filters.industry.length; i++)
				{
					// add industry filter to item			
			        item = '.industry-' + app.model.routing.filters.industry[i];
			    	$('.list ul').find(item).addClass('filtered-industry');
			    	$('.industries-list').find(item+' .icon-ok-circle').addClass('hidden');
					$('.industries-list').find(item+' .icon-ok-sign').removeClass('hidden');
				}
			}
			else
			{
				// add filter to all items when industry filter is inactive
				li.addClass('filtered-industry');
			}
			
			if(typeof app.model.routing.filters.country != 'undefined')
			{
				for(i = 0; i < app.model.routing.filters.country.length; i++)
				{
					// add country filter to item	
					item = '.country-' + app.model.routing.filters.country[i];			
			    	$('.list ul').find(item).addClass('filtered-country');
			    	$('.countries-list').find(item+' .icon-ok-circle').addClass('hidden');
					$('.countries-list').find(item+' .icon-ok-sign').removeClass('hidden');
				}
			}
			else
			{
				// add filter to all items when country filter is inactive
				li.addClass('filtered-country');
			}
			
			var items = $('.list ul').find('li.filtered-industry.filtered-country');
			items.stop();
			items.removeClass('hidden');
			
			var delay = 100;
		}
		else
		{
			// no filters active, show all
			li.removeClass('hidden');
			
			if(app.model.device.type == 'desktop' || app.model.device.type == 'wide')
			{
				var delay = 50;
				for (var i=0; i < li.length; i++) 
		    	{
					if(delay >= 1000 ) 
					{
						$(li[i]).hide().delay(delay).slideDown(0);
					}
					else
					{
						$(li[i]).hide().delay(delay).slideDown(300);
						delay += 50;
					}
				};	
			}		
		}
		
		// show number in title
		var filtered_items = $('.list li.block-large:not(.hidden)');	
		var num_of_filtered_items = filtered_items.length;
		$('.number-of-results').fadeOut(300, function()
		{
			if(num_of_filtered_items > 100) num_of_filtered_items = 100;
	    	$(this).empty().append(num_of_filtered_items).fadeIn(400);
	    });
	    // reset results to 100 items
		if(num_of_filtered_items > 100)
	    {
	    	for (var i=100; i < filtered_items.length; i++) 
	    	{
				$(filtered_items[i]).addClass('hidden');
			};
	    }
	    
	    setTimeout(function()
		{			
			// add met_stip_gestegen
			$('.list li:nth-child(6)').find('.met_stip_gestegen').hide().removeClass('hidden').slideDown(300);
			$('.list li:nth-child(8)').find('.met_stip_gestegen').hide().removeClass('hidden').slideDown(300);
			$('.list li:nth-child(15)').find('.met_stip_gestegen').hide().removeClass('hidden').slideDown(300);	
			
		}, delay);
	    
	}
	
	function toggle_filters(event)
	{
		//console.mp('^^ toggle_filters()');
		$('.all-filters').slideToggle(700, 'easeInOutExpo');
	}
	function open_filters(event)
	{
		//console.mp('^^ toggle_filters()');
		$('.all-filters').slideDown(700, 'easeInOutExpo');
	}
	
	function clear_filters(event)
	{
		//console.mp('^^ clear_filters()');
		filters = {};
		
		$('.list')
			.find('li')
				.stop(false,true) // stop all ongoing animations
			.end()
			.find('li.in-scope')
				.delay(200)
				.show()
				.animate(
				{
					opacity: 1
				},
				{
					easing: "easeInExpo",
					duration: 500
				})
            .end()
            .find('.out-of-scope')
                .hide(200);
				
		$('.get-all-filters')
			.html('<i class="icon-cog"></i>');
		$('.clear-filters')
			.slideUp(400, 'easeOutExpo');
		$('.number-of-results')
			.text(100);
	}
	
	function scroll_to_y(_y)
	{
		//console.mp('^^ scroll_to_y()');
		var speed = Math.abs(_y - $('body,html').scrollTop());
		
		console.log('animated scroll to:'+ _y, 'speed:',speed);
		$('body,html').animate({scrollTop: _y}, {duration: speed});
	}
	
	function make_friendly(input)
	{
        var toReturn = '';
		if(input)
		{
            toReturn 	= input.replace('industry-', ''),
            toReturn	= toReturn.toLowerCase(),
            toReturn 	= toReturn.replace('industry', ''),
            toReturn 	= toReturn.replace('&', ''),
            toReturn 	= toReturn.replace('-', ''),
            toReturn 	= toReturn.replace(' ', ''),
            toReturn 	= toReturn.replace(' ', ''),
            toReturn 	= toReturn.replace(' ', ''),
            toReturn 	= toReturn.replace('\'', ''),
            toReturn 	= toReturn.replace(' ', ''),
            toReturn 	= toReturn.replace('`', ''),
            toReturn 	= toReturn.replace('.', ''),
            toReturn 	= toReturn.replace('.', ''),
            toReturn 	= toReturn.replace('.', ''),
            toReturn 	= toReturn.replace(' ', '');
		}
		
		return toReturn;
	}
	
    Array.prototype.contains = function(obj)
    {
        //http://stackoverflow.com/a/237176
        var i = this.length;
        while (i--) {
            if (this[i] === obj) {
                return true;
            }
        }
        return false;
    }
})();