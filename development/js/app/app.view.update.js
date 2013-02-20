/*
 @Author: Dave Timmerman
*/

ViewUpdate = function()
{
	this.init();
};
	
$.extend(ViewUpdate.prototype, 
{	
	

///////////////		
// initialize	
///////////////	


	init: function() 
	{		
		// do initialization here 
	},
	
	clear: function() 
	{		
		// clear class here
		this.html = function(){};

		this.set_login_feedback = function(){};			
		this.set_navigation = function(){};	
		this.default_page_update = function(){};
		
		this.add_flash = function(){};
		this.trace_object = function(){};
	},
	
	setup: function() 
	{		
		// do setup here 
 	},
 	
 	html: function(data)
	{
		/* We use the fact that app.view.update
		   is the implied scope of this variable. */
		var method = app.view.update[data.update]; 		
		if(typeof method === 'function') 
		{
		    method(data);
		}
		else
		{
			if(app.model.environment != 'production')
				alert("'UPDATE_HTML' :: '"+data.update+"' does not exist in js/app.view.js");
		}
	},

///////////////		
// Methods	
///////////////	
			
	set_login_feedback: function (data)
	{
		$('input[name=password]').val('');
		$('input[name=password]').focus();
		$('#feedback').empty().html(data.msg);
	},
							
	set_navigation: function (data)
	{		
		$('#main_nav').empty();
		$('#profile_menu').empty();
		
		if(data.visible) 
		{
			// check role_id
			switch(data.role_id)
			{
				case '1':
					// administrator
					app.model.role_id = data.role_id;
					// all mudules available
					break;
					
				case '2':
					// manager
					app.model.role_id = data.role_id;
					// some modules available
					break;
					
				case '3':
					// contributor
					app.model.role_id = data.role_id;
					// a view modules available
					break;
					
				default:
					// a view modules available
					app.model.role_id = '3';
					break;
			}	
		}
	},
							
	default_page_update: function(data)
	{
		// remove login_view if neccessary
		if(app.model.current_page != 'login') $('#login').removeClass('visible').addClass('hidden');	
			
		// remove all old messages
		$('#message-no-hours').removeClass('visible');
		
		// header menu
		if(app.model.current_page != '')
		{
			$('#main_nav li').removeClass('current');
			$('#main_nav .'+app.model.current_page).addClass('current');
		}
		
		// remove loaders
		$('#loader-small').animate( { opacity: 0 }, 1000, function() 
		{
			//this.remove();
		});
	},
		
	// Add Flash Applet
	add_flash: function(data)
	{
		//id, swf_name, width, height, version				
		var flashvars = {
			base_url : app.model.base_url,
			environment : app.model.environment,
			app_name  : app.model.app_name,
			google_track_id  : app.model.google_track_id,
			language  : app.model.language
		};
		var params = {
			menu: 'false',
			scale: 'noScale',
			allowFullscreen: 'true',
			allowScriptAccess: 'always',
			allowNetworking: 'all',
			bgcolor: '#FFFFFF',				
			wmode: 'transparent' 							// can cause issues with FP settings & webcam
		};
		var attributes = {
			id : app.id, 									//'unique_identifier',
			name : app.id 									//'unique_identifier'
		};
		swfobject.embedSWF(
			app.model.base_url+"swf/"+app.swf_name+".swf", 		//flash_output_file.swf
			"altContent", app.width, app.height, app.version, 	//"11.0.0", 
			app.model.base_url+"swf/expressInstall.swf", 
			flashvars, params, attributes);	
		
		/* add this to a view
		<div id="altContent">
			<!-- <h1>Flash Applet/h1>
			<p><a href="http://www.adobe.com/go/getflashplayer">Get Adobe Flash player</a></p> -->
		</div>
		*/
	},
	
	// Trace an object in a html view
	trace_object: function (oObj, key, tabLvl)
	{
	    key = key || "";
	    tabLvl = tabLvl || 1;
	    var tabs = "";
	    for(var i = 1; i < tabLvl; i++){
	        tabs += "&nbsp;&nbsp;&nbsp;";
	    }
	    var keyTypeStr = ""; //" (" + typeof key + ")";
	    if (tabLvl == 1) {
	        keyTypeStr = "(self)";
	    }
	    if(key != 'paging')
	    {
		    var s = tabs + key + keyTypeStr + " : ";
		    if (typeof oObj == "object" && oObj !== null) 
		    {
		        s += typeof oObj + "<br />";
		        for (var k in oObj) {
		            if (oObj.hasOwnProperty(k)) {
		                s += app.view.trace_object(oObj[k], k, tabLvl + 1);
		            }
		        }
		    } else {
		    	s += "" + oObj + "<br />"; //" (" + typeof oObj + ") <br />";
		    }
		}
	    return s;
	}
	
});	