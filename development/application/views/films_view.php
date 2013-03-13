<section id="menu">
	<form method="get" action="" id="search">
		<div id="loader-small"><img src="images/ui/ajax-loader.gif" width="20" height="20" /></div>
		<input name="search" type="text" size="40" placeholder="search..." />
	</form>
	<div class="paging">
		<a href="javascript:void(0);" class="small awesome logout-button" onclick="app.events.dispatch('DO_LOGOUT'); return false;">Logout</a>
		<a href="javascript:void(0);" class="small yellow awesome suggest-button">...</a>
	</div>
</section>
<section id="films">
	<ul></ul>
</section>
<section id="player" class="hidden">
	<video controls autoplay poster autobuffer preload="auto" name="media"></video>
</section>