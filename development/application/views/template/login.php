<div id="login" class='hidden'>
	<div id="login-form" class="shadowed-background">

		<form method="post" action="<?php echo base_url(); ?>login/process_login" id="login-form-post" nonsubmit="app.events.dispatch('DO_LOGIN'); return false;">
			<label for="username">Username</label>
			<input value=""  type="text" id="username" name="username" autofocus/>
			<label for="password">Password</label>
			<input value="" type="password" id="password" name="password" />
			<button class="awesome large login-button" name="login" type="submit"> Login </button>
			<span id="feedback"></span>
		</form>

	</div>
</div>