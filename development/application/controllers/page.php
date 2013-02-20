<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Page controller class
 */
class Page extends CI_Controller
{
 	
	function __construct()
    {
        parent::__construct();
		
		$this->load->library('common'); 							//$this->common->some_function(); 		
		
		if($this->input->get('testing') == 'true') 					// set post to get for testing purposes
			$this->testing = TRUE;
		else
			$this->testing = FALSE;
	}
	
	public function index()
    {		
		redirect('');
	}
	
	
///////////////	
// PAGELOAD GLOBAL METHODS
///////////////	


	public function load_view()
    {
    	if(!$this->testing) {
			$view = $this->input->post('view');
			$injected_module = $this->input->post('module');
		}else{
			$view = $this->input->get('view');
			$injected_module = $this->input->get('module');

		}
		
		// get extra data to be parsed in the view
		if($view == 'some_view')
		{
			$data['some_data'] = $this->get_some_data();
		}

		// add javascript module
		$data['injected_module'] = $injected_module;
		
		$html = $this->load->view($view, $data, TRUE);
		$this->common->html_response($html);
	}
	
	
///////////////	
// PRIVATE METHODS
///////////////	

	
	private function get_some_data()
	{
		return NULL;
	}	
		
}
 
?>