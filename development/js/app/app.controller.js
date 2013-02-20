/*
 @Author: Dave Timmerman
 */

Controller = function()
{
	this.init();
};

$.extend(Controller.prototype,
{

	///////////////
	// initialize
	///////////////

	init : function()
	{
		// do initialization here
	},

	clear : function()
	{
		// clear class here
		this.execute = function()
		{
		};

		this.xhr_abort = function()
		{
		};

		this.load_view = function()
		{
		};

		this.check_login = function()
		{
		};
		this.process_login = function()
		{
		};
		this.process_logout = function()
		{
		};

		this.send_email = function()
		{
		};
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
				if (method == "load_view")
					console.log(method + " msg :: fetched html successfully.");
				else
					console.log(method + " msg :: " + response.msg);
			},
			error : function(xhr, ajaxOptions, thrownError)
			{
				console.log(method + " error :: " + xhr.status + ", thrownError=" + thrownError + ', fired from ' + method);
			},
			beforeSend : function(xhr, settings)
			{
				//
			},
			complete : function(xhr, textStatus)
			{
				//
			}
		});
	},

	/**
	 * abort all pending ajax calls
	 *
	 */
	xhr_abort : function()
	{
		$.each(app.model.xhr_pool, function(idx, xhr)
		{
			console.log('ABORT AJAX CALL\napp.controller.xhr_abort :: ', xhr);
			xhr.abort();
			app.model.xhr_pool = $.grep(app.model.xhr_pool, function(value)
			{
				return value != xhr
			});
		});
	},

	///////////////
	// Methods
	///////////////

	/**
	 * load a view and return html
	 *
	 */
	load_view : function(view)
	{
		app.controller.xhr_abort();
		php_controller = false;

		switch(view+'_view')
		{
			// login_view handled in template for autocompletion
			case "login_view":
				app.events.dispatch("ADD_VIEW",
				{
					view : 'login'
				});
				return
				{
					success : function()
					{
						console.log("toggle login_view success :: html visible");
					}
				};
				break;

			default:
				for (var i = 0; i < app.model.pages.length; i++)
				{
					if (app.model.pages[i] == view)
					{
						php_controller = 'page';
						break;
					}
				};
				if (!php_controller)
				{
					for (var i = 0; i < app.model.securedpages.length; i++)
					{
						if (app.model.securedpages[i] == view)
						{
							php_controller = 'securedpage';
							break;
						}
					};
				}
				if (!php_controller)
				{
					if (app.model.environment != 'production')
						alert("load_view :: '" + view + "' is not added to a controller yet. Check app.controller.js/load_view");
				}
				break;
		}

		var method = 'load_view';
		var url = app.model.base_url + php_controller + "/load_view";

		var type = 'POST';
		var dataType = "html";
		var cache = false;
		var data =
		{
			view : view + '_view',
			module : view
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * check login status and return json
	 *
	 */
	check_login : function()
	{
		var method = 'check_login';
		var url = app.model.base_url + "login/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * process login and return json
	 *
	 */
	process_login : function(username, password)
	{
		var method = 'process_login';
		var url = app.model.base_url + "login/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
			username : username,
			password : password
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * process logout and return json
	 *
	 */
	process_logout : function()
	{
		var method = 'process_logout';
		var url = app.model.base_url + "login/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
		};

		return this.execute(url, type, dataType, cache, data, method);
	},

	/**
	 * send an email and return json
	 *
	 */
	send_email : function(id, uid, name, email, image, type)
	{
		var method = 'send_email';
		var url = app.model.base_url + "email/" + method;

		var type = 'POST';
		var dataType = "json";
		var cache = false;
		var data =
		{
			id : id,
			name : name,
			email : email,
			image : image,
			type : type
		}

		return this.execute(url, type, dataType, cache, data, method);
	}
}); 