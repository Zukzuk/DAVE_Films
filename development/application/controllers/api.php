<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: API controller class
 */
class Api extends CI_Controller
{     
	
	function __construct()
	{ 
	    parent::__construct();	
		
		$this->controller_uri = 'api/';
		$this->view = 	$view = 'api_view';
		
		$this->load->library('common'); 							//$this->common->some_function(); 
	}
	
	public function index()
    {	
		if(ENVIRONMENT != 'production')
		{
			$data['page_title'] = 'API';
			$data['methods'] = $this->get_api_methods();		
			$html = $this->load->view('api/'.$this->view, $data, TRUE);
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


	private function get_api_methods()
	{
		$methods = 	'<h1>API</h1>'.
					'<p><a href="'.base_url().'login" target="_self">API login methods</a></br></br>'.
					'<a href="'.base_url().'data" target="_self">API data methods</a></br></br>'.
					'<a href="'.base_url().'secureddata" target="_self">API secureddata methods</a></br></br></p>';
		return $methods;
	}	

}
?>