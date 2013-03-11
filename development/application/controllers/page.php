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
		}else{
			$view = $this->input->get('view');
		}
		$data = array();
		
		// get extra data to be parsed in the view
		if($view == 'some_view')
		{
			$data['some_data'] = $this->get_some_data();
		}
		
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