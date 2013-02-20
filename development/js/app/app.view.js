/*
 @Author: Dave Timmerman
*/

View = function()
{
	this.init();
};
	
$.extend(View.prototype, 
{	
	update : {},
	main_nav : '',
	main_nav_html : '',
	container : '',
	

///////////////		
// initialize	
///////////////	


	init: function() 
	{		
		// do initialization here 
		this.update = new ViewUpdate();
		this.setup();
	},
	
	clear: function() 
	{		
		// clear class here
		this.add = function(){};
		
		this.login_view = function(){};
		this.films_view = function(){};
	},
	
	setup: function() 
	{		
		// do setup here
 	},
 	
	add: function(data)
	{
		/* We use the fact that app.view
		   is the implied scope of this variable. */
		var method = app.view[data.view+'_view']; 		
		if(typeof method === 'function') 
		{
		    method(data);
		}
		else
		{
			if(app.model.environment != 'production')
				alert("'ADD_VIEW' :: '"+data.view+"' does not exist in js/app.view.js");
		}
	},
	
///////////////		
// Methods	
///////////////	

		
// login page (non ajax)
	login_view: function(data)
	{
		$("#container").hide().empty().show();
		$('#login').removeClass('hidden');
		app.model.current_page = 'login';
		app.events.dispatch('UPDATE_HTML', { update:'default_page_update' });
	},

	films_view : function(data)
	{
		$("#container").hide().empty().html(data.html).show();
		app.data.get_user_privileges().success(function(response)
		{
			app.model.current_page = 'films';
			app.events.dispatch('UPDATE_HTML', { update:'default_page_update' });
		});
	}
	
});	