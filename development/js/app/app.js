/*
 @Author: Dave Timmerman
 */

CustomApplication = function(data)
{
	this.init(data);
};

$.extend(CustomApplication.prototype,
{
	model : { },
	events : { },
	view : { },
	controller : { },
	data : { },

	///////////////
	// initialize
	///////////////

	init : function(data)
	{
		// do initialization here
		this.model = new Model(data);
		this.events = new Events();
		this.view = new View();
		this.controller = new Controller();
		this.data = new Data();
	},

	clear : function()
	{
		// clear class here
		this.add_deeplinking = function() { };
		this.navigate = function() { };
		this.load_view = function() { };
		this.do_login = function() { };
		this.do_logout = function() { };

	},

	setup : function()
	{
		if (app.model.js_deeplink)
			app.events.dispatch("ADD_DEEPLINKING", { });
		else
			app.events.dispatch("NAVIGATE", { uri : '/' });
	},

	///////////////
	// Methods
	///////////////

	add_deeplinking : function(data)
	{
		//$.address.strict(false);

		// Catches deeplink from internal url changes (hardcoded or via links)
		$.address.internalChange(function(deeplink)
		{
			//console.app('Address internalChange :: '+deeplink.path);
		});

		// Catches deeplinks on browser update (browser back button etc)
		// Add this to your module!!
		$.address.externalChange(function(deeplink)
		{
			//console.app('Address externalChange :: '+deeplink.path);
			app.model.external_uri_change( { uri : deeplink.path });
		});
		
		$.address.change(function(deeplink)
		{
			//console.app('app.js $.address.change:', deeplink);
			app.model.setup_routing(deeplink);

			// user wants to load the base url
			if (app.model.routing.type == '')
			{
				// if root url, redirect to landing "page"
				app.events.dispatch('NAVIGATE', { uri : app.model.landing_page });
			}

			// user wants to load a "page"
			else
			if (app.model.routing.type == 'page')
			{
				if (!app.model.startup_complete)
				{
					// starting up, check if login is required
					if (app.model.login_at_startup)
					{
						// login required, check login status
						app.controller.check_login().success(function(response)
						{
							if (!response.login_status)
							{
								// user is not logged in, redirect to login "page"
								console.app('routing :: user is not logged in');
								console.app('routing :: login_at_startup is true, navigate to the login "page"');
								if (app.model.routing.view == "login")
									app.events.dispatch("LOAD_VIEW", { view : "login" });
								else
									app.events.dispatch("NAVIGATE", { uri : "login" });
							}
							else
							{
								// user is logged in, save some stuff
								console.app('routing :: user is logged in, save publicly available data to the model');
								console.app('routing :: navigate to the landing "securedpage"');
								app.model.firstname = response.firstname;
								app.model.lastname = response.lastname;

								// redirect to landing "securedpage"
								app.events.dispatch("NAVIGATE", { uri : app.model.landing_securedpage });
							}
						});

						// set navigation
						app.events.dispatch('UPDATE_HTML', { update : 'set_navigation', visible : false });
						app.model.startup_complete = true;
					}
					else
					{
						// login not required, normal routing applies
						console.app('routing :: no login required');
						console.app('routing :: simply navigate to the requested "page"');
						app.events.dispatch('LOAD_VIEW', { view : app.model.routing.view });
					}
				}
				else
				{
					// startup complete, normal routing applies
					console.app('routing :: startup complete');
					console.app('routing :: simply navigate to the requested "page"');
					app.events.dispatch('LOAD_VIEW', { view : app.model.routing.view });
				}
			}

			// user wants to load a "securedpage"
			else
			if (app.model.routing.type == 'securedpage')
			{
				// check login
				app.controller.check_login().success(function(response)
				{
					if (!response.login_status)
					{
						// user is not logged in, redirect to login page
						console.app('routing :: user is not logged in');
						console.app('routing :: navigate to the login "page"');
						app.events.dispatch('NAVIGATE', { uri : 'login' });
					}
					else
					{
						// user is logged in, normal routing applies
						console.log('routing :: user is logged in, save publically available data to the model.');
						app.model.firstname = response.firstname;
						app.model.lastname = response.lastname;

						console.app('routing :: simply navigate to the requested "securedpage"');
						app.events.dispatch("LOAD_VIEW", { view : app.model.routing.view });

						// set navigation when role_id is new
						app.data.get_user_privileges().success(function(response)
						{
							if (app.model.role_id != response.role_id)
							{
								app.model.role_id = response.role_id;
								app.events.dispatch('UPDATE_HTML', { update : 'set_navigation', visible : true, role_id : response.role_id });
								app.events.dispatch('UPDATE_HTML', { update : 'default_page_update' });
							}
						});
					}
				});
			}

		});
	},

	navigate : function(data)
	{
		if (app.model.js_deeplink)
			$.address.path(data.uri);
		else
			app.events.dispatch('LOAD_VIEW', { view : data.view });
	},

	load_view : function(data)
	{
		if (app.model.routing.view != app.model.current_page)
		{
			$('#loader-small').animate( { opacity : 0 }, 0).animate( { opacity : 1 }, 400);

			// load view
			app.controller.load_view(data.view).success(function(response)
			{
				app.events.dispatch("ADD_VIEW", { view : data.view, html : response });
			});
		}
	},

	do_login : function(data)
	{
		// get form input data
		var username = $.base64Encode($('input[name=username]').val());
		var password = $.base64Encode($('input[name=password]').val());
		var login_type = 'ajax';

		// check credentials
		app.controller.process_login(username, password, login_type).success(function(response)
		{
			if (!response.login_status)
			{
				// user is not logged in.
				app.events.dispatch("UPDATE_HTML", { update : 'set_login_feedback', msg : response.msg });
			}
			else
			{
				// user has logged in.
				window.location = app.model.base_url;
			}
		});
	},

	do_logout : function(data)
	{
		app.controller.process_logout().success(function(response)
		{
			window.location = app.model.base_url;
		});
	}
});

/*
 In JavaScript null is an object. There's another value for things that don't exist, undefined.
 The DOM returns null for almost all cases where it fails to find some structure in the document,
 but in JavaScript itself undefined is the value used. Second, no, they are not directly equivalent.

 If you really want to check for null:
 if (null == yourvar) // with casting
 if (null === yourvar) // without casting

 If you want to check if a variable exist:
 if (typeof yourvar != 'undefined') // Any scope
 if (window['varname'] != undefined) // Global scope
 if (window['varname'] != void 0) // Old browsers

 If you know the variable exists but don't know if there's any value stored in it:
 if (undefined != yourvar)
 if (void 0 != yourvar) // for older browsers

 If you want to know if a member exists independent of whether it has been assigned a value or not:
 if ('membername' in object) // With inheritance
 if (object.hasOwnProperty('membername')) // Without inheritance
 If you want to to know whether a variable autocasts to true:

 if(variablename)
 */

