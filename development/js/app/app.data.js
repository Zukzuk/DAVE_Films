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
						//window.location.reload(app.model.base_url);
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
	// OTHER MODULES
	///////////////

	
}); 