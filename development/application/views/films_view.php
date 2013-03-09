<section id="head">
	<form method="get" action="" id="search">
		<div id="loader-small"><img src="images/ui/ajax-loader.gif" width="20" height="20" /></div>
		<input name="search" type="text" size="40" placeholder="search..." />
	</form>
	<a href="javascript:void(0);" class="small awesome" onclick="app.events.dispatch('DO_LOGOUT'); return false;">Logout</a>
</section>
<section id="films">
	<ul></ul>
</section>
<section id="player">
	<video controls autoplay poster autobuffer preload="auto" name="media"></video>
</section>
<script type="text/javascript" src="js/modules/<?php echo $injected_module; ?>.js"></script>