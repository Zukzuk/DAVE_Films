<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Init controller class
 */
class Init extends CI_Controller
{
     
    function __construct()
    {
        parent::__construct();
			
		$this->load->library('common'); 			//$this->common->some_function(); 
		
		if($this->input->get('testing') == 'true') 	// set post to get for testing purposes
			$this->testing = TRUE;
		else
			$this->testing = FALSE;
    }
     
    public function index()
    {
    	$this->load_template();
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
  
	
	private function load_template()
    {
    	// SET COMMON VARS
		$data['page_title'] = 'Portfolio';
		$data['app_name'] = 'Portfolio';
		$data['google_track_id'] = 'UA-xxxxxxxx-1';
		$data['language'] = 'english';		
		$data['js_deeplink'] = 'true';
		$data['responsive'] = 'true';
		$data['login_target'] = 'self'; // self, facebook, linkedin, etc
		$data['login_at_startup'] = 'false';
		$data['developer'] = $this->input->get('developer');
		
    	$this->load->vars($data);
   		$this->load->view('template/template');
    }

}

?>