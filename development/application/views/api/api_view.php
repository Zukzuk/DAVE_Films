<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>  
	  
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes" /> 
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=IE8"> -->
    
    <meta property="og:image" content=""/> 
    <meta property="og:site_name" content=""/>
    <meta property="og:title" content=""/>
    <meta property="og:description" content=""/>
    <meta property="og:url" content="" />
    
    <title><?php if(! is_null($page_title)) echo $page_title; ?></title>
    
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    
    <link rel="stylesheet" href="style/css/normalize.css">
  	<link rel="stylesheet" href="style/css/style.css">
  	<link rel="stylesheet" href="style/css/font-awesome.css">
  	<link rel="stylesheet" href="style/css/print.css" media="print">

</head>

<body class="api">
	
<header id="navigation" class="no-print">

<a id="logo" href="#/home"></a>	

<nav id="main_nav">
	<ul style="display:none;">
		<li class="home"></li>
	</ul>
</nav>	

<nav id="profile_menu"></nav>	
	
</header>

<div id="messages">
	<h1 class="message" id="message-saving">Saving changes<p class="right remove-message"><i class="icon-remove"></i></p></h1>
</div>

<div id="container">
	<div id="main" class="clearfix">
		<?php if(! is_null($methods)) echo $methods; ?>
	</div>
</div>

<?php $year = date('Y');?>
<footer><p>Fitzroy &copy; <?php echo $year; ?> | All Rights Reserved</p></footer>

<script type="text/javascript">
	
</script> 

</body>
</html>
