/*
 @Author: Dave Timmerman
 */

Events = function()
{
	this.init();
};

$.extend(Events.prototype,
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
		this.dispatch = function() { };
	},

	setup : function()
	{
		// do setup here
	},

	///////////////
	// Event Handler
	///////////////

	/**
	 * The events dispatcher method
	 * Updates can be done through the following call:
	 * app.events.dispatch("EVENT_STRING", {...any other data...});
	 *
	 * @param type String A String containing the type Event
	 * @param data An Object containing optional data
	 */
	dispatch : function(type, data)
	{
		if (type == "NAVIGATE") { console.log("app event :: " + type + " " + data.uri); }
		else if (type == "LOAD_VIEW") { console.log("app event :: " + type + " " + data.view + "_view.php"); }
		else if (type == "ADD_VIEW") { console.log("view event :: " + type + " " + data.view); }
		else if (type == "UPDATE_HTML") { console.log("view event :: " + type + " " + data.update); }
		else { console.log("app event :: " + type); }

		switch(type)
		{

			// app.js

			case 'ADD_DEEPLINKING':
				app.add_deeplinking(data);
				return false;
				break;

			case 'NAVIGATE':
				app.navigate(data);
				return false;
				break;

			case 'LOAD_VIEW':
				app.load_view(data);
				return false;
				break;

			case 'DO_LOGIN':
				app.do_login(data);
				return false;
				break;

			case 'DO_LOGOUT':
				app.do_logout(data);
				return false;
				break;

			case 'DO_FACEBOOK_LOGIN':
				app.do_facebook_login(data);
				return false;
				break;

			case 'DO_FACEBOOK_LOGOUT':
				app.do_facebook_logout(data);
				return false;
				break;

			// app.view.js

			case 'ADD_VIEW':
				app.view.add(data);
				return false;
				break;

			case 'UPDATE_HTML':
				app.view.update.html(data);
				return false;
				break;

			// default

			default:
				if (app.model.environment != 'production') alert("event dispatcher :: '" + type + "' does not exist in js/app.events.js");
				break;
		}

		return false;
	}
});
