<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Data controller class
 */
class Data extends CI_Controller
{     
	
	function __construct()
	{
	    parent::__construct();		
		
		$this->controller_uri = 'data/';
		$this->api_view = 'api_data_view';
		
		$this->load->library('common'); 							//$this->common->some_function();
		$this->load->model('data_model');							//$this->data_model->some_function();
		
		if($this->input->get('testing') == 'true') 					// set post to get for testing purposes
			$this->testing = TRUE;
		else
			$this->testing = FALSE;
	}
	
	public function index()
    {
    	if(ENVIRONMENT != 'production')
		{
			$data['page_title'] = 'API - Data';
			$data['links'] = $this->get_api_methods();			
			$html = $this->load->view('api/'.$this->api_view, $data, TRUE);
			$this->common->html_response($html);
		}
		else
		{
			redirect('');
		}
	}
	
	
///////////////	
// API MODULE METHODS
///////////////	


///////////////	
// API GLOBAL METHODS
///////////////	
	
	
///////////////	
// PRIVATE METHODS
///////////////	

	
	private function execute($method, $mandatory, $optional)
	{
	 	// check submitted data
		$response = $this->common->check_mandatory_parameters($mandatory, $this->testing);		
		// if no error, retrieve model query 
		if(!$response['error'])
		{
			if(!$this->testing)
				$parameters = $_POST;
			else				
				$parameters = $_GET;
			$response = array_merge($response, $this->data_model->$method($parameters));
		}		
		// echo appropriate payload
		$this->common->prepare_payload($method, $response, $mandatory, $optional, base_url(), $this->controller_uri, $this->testing);
	}
	
	private function get_api_methods()
	{
		$counter = 0;
		$links = '';
		$class_methods = get_class_methods($this);
		$links .= "<h1>API - Data</h1>";
		foreach ($class_methods as $method_name) 
		{
			if($method_name != '__construct' && $method_name != 'index' && $method_name != 'execute' && $method_name != 'get_instance' && $method_name != 'get_api_methods')
			{
				$counter++;
				$method = base_url().$this->controller_uri.$method_name;
			    $links .= '<p><a href="'.$method.'?testing=true" target="_self">'.$this->controller_uri.$method_name.'()</a></br></br>';
			}
		}
		if(!$counter) 
			$links .= '<p>No methods found!</br></br></p>';
		else
			$links .= '</p>';
		return $links;
	}

}
?>