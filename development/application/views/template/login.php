<div id="login" class='hidden'>	
	<div id="login-form">	
			
		<form method="post" action="" id="login-form-post" onsubmit="app.events.dispatch('DO_LOGIN'); return false;">		
			<label for="username">Username</label><input value=""  type="text" id="username" name="username" autofocus/>
			<label for="password">Password</label><input value="" type="password" id="password" name="password" />				
			<button class="" name="login" type="submit">Login</button>
			<span id="feedback"></span>
		</form> 
		
	</div>
</div>