<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Member controller class
 */
class Securedpage extends CI_Controller
{
 	
	function __construct()
    {
        parent::__construct();
		
		$this->load->library('common'); 							//$this->common->some_function(); 		
		$this->load->model('login_model');							//$this->login_model->some_function();
		
		if(!$this->login_model->check_isvalidated()) 
		{
			redirect(''); 											// f off hacker!
			exit;
		}
		
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
	
	public function player()
    {
    	$data = array(	'name' => $this->input->post('name'),
						'type' => $this->input->post('type'),
						'filename' => $this->input->post('filename')
		);
		$this->load->view('player_view', $data, TRUE);
		//$this->common->html_response($html);
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