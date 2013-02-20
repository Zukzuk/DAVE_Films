<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Common 
{	
	function check_mandatory_parameters($mandatory, $testing)
    {
    	// set error
    	$response['error'] = FALSE;
		
		// parse message
		$response['msg'] = '*** Missing mandatory parameter(s) : ';
		foreach ($mandatory as $key => $value) 
		{
			// set post or get key to a string
			if(isset($_POST[$value])) $val = $_POST[$value]; 	
			if(isset($_GET[$value])) $val = $_GET[$value];	
			// check if string is mandatory
			if(!isset($val) || $val == '')
			{
				$response['msg'] .= $value.', ';
				$response['error'] = TRUE;
			}
			$val = NULL;
		}
		// remove trailing comma and space
		$response['msg'] = substr($response['msg'], 0, -2);
		
		return $response;
	}
	
	function prepare_payload($method, $response, $mandatory, $optional, $base_uri, $controller_uri, $testing)
	{
		if(!$testing) {
			$this->json_response($response);
		} else {
			$this->api_echo($method, $response, $mandatory, $optional, $base_uri, $controller_uri, $testing);
		}
	}
	
	function json_response($response)
	{
		echo(json_encode($response));
	}
	
	function api_echo($method, $response, $mandatory, $optional, $base_uri, $controller_uri, $testing)
    {
    	// echo <pre>
		echo('<pre>');
		
		echo('</br>'.
		'<strong>===========</br>'.
		'JSON Object</br>'.
		'===========</strong></br></br>'
		);
		print_r($response);
		echo('</br></br>');
		
		//setup feedback
		$method_str = $method;
		echo('method : '.$method_str.'</br>');
		
    	// parse mandatory parameters
    	$mandatory_str = '';
		foreach ($mandatory as $key => $value) { $mandatory_str .= $value.', '; }	
		$mandatory_str = substr($mandatory_str, 0, -2);		
		echo('mandatory : '.$mandatory_str.'</br>');
		
		// parse optional parameters
		$optional_str = '';
		foreach ($optional as $key => $value) { $optional_str .= $value.', '; }
		$optional_str = substr($optional_str, 0, -2);
		echo('optional : '.$optional_str.'</br>');
		
		// parse api uri for testing
		$uri_str = $base_uri.$controller_uri.$method_str.'?testing=true';
		$parameters = '';
		foreach ($mandatory as $key => $value) { $parameters .= '&'.$value.'=MANDATORY'; }
		foreach ($optional as $key => $value)  { $parameters .= '&'.$value.'=OPTIONAL'; }
		$uri_str .= $parameters;
		echo('uri : <a href="'.$uri_str.'" target="_self">'.$uri_str.'</a>'.'</br>');
		
		// setup code hinting
		
		// create parameters
		$parameters = '';			
		if($mandatory_str != '') {
			$parameters .= $mandatory_str;	
		}
		if($optional_str != '') {
			if($mandatory_str != '') $parameters .= ', ';
			$parameters .= $optional_str;
		}
		
		// echo module.js code hinting
		echo('</br></br></br>'.
		'<strong>==================================</br>'.
		'Use this snippet in your module.js</br>'.
		'==================================</strong></br></br>'.
		'app.data.'.$method_str.'('.$parameters.')</br>'.
		'.success(function(data)</br>'.
		'{</br>'.
		'&nbsp;&nbsp;&nbsp;console.log(data);</br>'.
		'});'
		);
		
		//create variables_str
		$variables = explode(', ', $parameters);
		$variables_str = '';
		foreach ($variables as $key => $value) 
		{
			if($value != '') $variables_str .= '_'.$value.', ';
		}
		$variables_str = substr($variables_str, 0, -2);
		
		//create data_str
		$data = explode(', ', $parameters);
		$data_str = '';
		foreach ($data as $key => $value) 
		{
			if($value != '') $data_str .= $value.':_'.$value.', ';
		}
		$data_str = substr($data_str, 0, -2);
		
		// echo app.data.js code hinting
		echo('</br></br></br>'.
		'<strong>===============================</br>'.
		'Use this snippet in app.data.js </br>'.
		'===============================</strong></br></br>'.
		$method_str.': function('.$variables_str.')</br>'.
		'{</br>'.
		'&nbsp;&nbsp;&nbsp;method = "'.$method_str.'";</br>'.
		'&nbsp;&nbsp;&nbsp;url = app.model.base_url+"'.$controller_uri.'"+method;</br>'.
		'&nbsp;&nbsp;&nbsp;type = "POST";</br>'.
		'&nbsp;&nbsp;&nbsp;dataType = "json";</br>'.
		'&nbsp;&nbsp;&nbsp;cache = false;</br>'.
		'&nbsp;&nbsp;&nbsp;data = { '.$data_str.' };</br>'.
		'&nbsp;&nbsp;&nbsp;return this.execute(url, type, dataType, cache, data, method);</br>'.
		'}'
		);
		
		echo('</pre>');
	}
	
	// DEPRECATED //
	function json_pre_echo($response)
    {
		echo('<pre>');
		
		echo('</br>'.
		'<strong>===========</br>'.
		'JSON Object</br>'.
		'===========</strong></br></br>'
		);
		echo(print_r($response));
		
		echo('</pre>');
	}
	
	function html_response($html)
	{
		echo($html);
	}
	
	function random_string($type = 'alnum', $len = 8)
	{					
		switch($type)
		{
			case 'alnum'	:
			case 'numeric'	:
			case 'nozero'	:
			
					switch ($type)
					{
						case 'alnum'	:	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
							break;
						case 'numeric'	:	$pool = '0123456789';
							break;
						case 'nozero'	:	$pool = '123456789';
							break;
					}
	
					$str = '';
					for ($i=0; $i < $len; $i++)
					{
						$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
					}
					return $str;
					
			  break;
				
			case 'unique' : return md5(uniqid(mt_rand()));
			  break;
		}
	}
	
}

/* End of file Common.php */