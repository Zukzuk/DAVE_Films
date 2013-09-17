/*
 @Author: Dave Timmerman
 */

Data = function(data)
{
	this.init(data);
};

$.extend(Data.prototype,
{

	///////////////
	// initialize
	///////////////

	init : function(data)
	{
		// do initialization here
	},

	clear : function()
	{
		// clear class here
		this.execute = function() { };

		this.get_user_privileges = function() { };
	},

	setup : function()
	{
		// do setup here
	},

	execute : function(url, type, dataType, cache, data, method)
	{
		return $.ajax(
		{
			url : url,
			type : type,
			dataType : dataType,
			cache : cache,
			data : data,
			success : function(response)
			{
				if (!response.session)
				{
					console.log(method + " msg :: " + response.msg);
					if (app.model.environment != 'production') alert('Session is no longer valid, you will now be logged out.');
					app.controller.process_logout().success(function()
					{						
						window.location.reload(app.model.base_url);
					});					
				}
			},
			error : function(xhr, ajaxOptions, thrownError)
			{
				console.log(method + " error :: " + xhr.status + ", thrownError=" + thrownError + ', fired from ' + method);
			},
			beforeSend : function(xhr, settings)
			{
				app.model.xhr_pool.push(xhr);
			},
			complete : function(xhr, textStatus)
			{
				app.model.xhr_pool = $.grep(app.model.xhr_pool, function(value)
				{
					return value != xhr;
				});
			}
		});
	},

	///////////////
	// GLOBAL
	///////////////

	/**
	 * Get the user privileges and return json
	 *
	 */
	get_user_privileges : function()
	{
		var method = 'get_user_privileges';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data = {};

		return this.execute(url, type, dataType, cache, data, method);
	},

	///////////////
	// FILMS MODULE
	///////////////

	/**
	 * Get all available film directories
	 *
	 */
	get_all_films : function()
	{
		var method = 'get_all_films';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data = {};

		return this.execute(url, type, dataType, cache, data, method);
	},
	
	/**
	 * Prepare synchronization with database
	 *
	 */
	prepare_synchronization : function(_films)
	{
		var method = 'prepare_synchronization';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data = {};
		
		return this.execute(url, type, dataType, cache, data, method);
	},
	
	/**
	 * Synchronize films with database
	 *
	 */
	synchronize_films : function(_films)
	{
		var method = 'synchronize_films';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
			films:_films
		};
		
		return this.execute(url, type, dataType, cache, data, method);
	},
	
	/**
	 * Finish synchronization with database
	 *
	 */
	finish_synchronization : function(_films)
	{
		var method = 'finish_synchronization';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data = {};
		
		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * CRUD tags to search
	 *
	 */
	crud_tags : function(_crud_type, _film_id, _tag)
	{
		var method = 'crud_tags';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
		  crud_type : _crud_type,
		  film_id : _film_id,
		  tag : _tag
		};

		return this.execute(url, type, dataType, cache, data, method);
	},
	
	/**
	 * Get all available items in a collection
	 *
	 */
	get_collection : function(_directory)
	{
		var method = 'get_collection';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
			directory : _directory
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * Get film player iframe
	 * DEPRECATED!
	 */
	get_player_iframe : function(_film, _poster)
	{
		var method = 'get_player_iframe';
		var url = app.model.base_url + "secureddata/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
			film : _film,
			poster : _poster
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * Get imdb data for a specific movie
	 * DEPRECATED!
	 */
	get_imdb_data : function(_imdb_name, _year)
	{
		var method = 'get_imdb_data';
		var url = 'http://imdbapi.org/?q=' + _imdb_name + '&year=' + _year + '&type=json&lang=en-US';
		// + "&type=json&limit=1";

		var type = 'GET';
		var dataType = "json";
		var cache = false;
		var data = {};

		return this.execute(url, type, dataType, cache, data, method);
	}
}); 