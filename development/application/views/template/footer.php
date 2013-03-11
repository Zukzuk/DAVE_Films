</div> <!-- end of wrapper -->

<script type="text/javascript">

// Application Object
var app = new CustomApplication({
	// application data
	base_url:'<?php echo base_url(); ?>',				// string
	version:'<?php echo APP_VERSION; ?>',				// string
	environment:'<?php echo ENVIRONMENT; ?>',			// string
	app_name:'<?php echo $app_name; ?>',				// string
	google_track_id:'<?php echo $google_track_id; ?>',	// string
	language:'<?php echo $language; ?>',				// string
	js_deeplink:<?php echo $js_deeplink; ?>, 			// boolean
	responsive:<?php echo $responsive; ?>, 				// boolean
	login_target:'<?php echo $login_target; ?>', 		// string
	login_at_startup:<?php echo $login_at_startup; ?>, 	// boolean
	developer:'<?php echo $developer; ?>'				// string
});

// Load Google Javascript Asynchronously
// trackEvent :: _gaq.push(['_trackEvent', 'cat', 'name']);	
// pageView :: pageTracker._trackPageview('/folder/file');
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '<?php echo $google_track_id; ?>']);
_gaq.push(['_trackPageview']);
(function() 
{
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true; ga.id = 'lib';
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
  
// Waaaaait for it.....
$(document).ready(function() 
{
	app.setup();
});
	
</script> 
</body>
</html>