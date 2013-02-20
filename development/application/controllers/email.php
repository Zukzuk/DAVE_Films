<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Wouter Felius
 * Description: Email controller class
 */
class Email extends CI_Controller
{
	
	function __construct()
	{
	    parent::__construct();
				
		$this->load->library('common'); 							//$this->common->some_function(); 		
		$this->load->model('login_model');							//$this->login_model->some_function();
		$this->load->model('data_model');							//$this->data_model->some_function();	
		
		if(!$this->login_model->check_isvalidated()) redirect(''); 	// f off hacker!	
		
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
// API MODULE METHODS
///////////////	

	
	public function send_email()
	{
		require_once 'application/libraries/email/class.phpmailer.php';
		
		if(!$this->testing){
			$id = base64_decode($this->input->post('id'));
			$name = base64_decode($this->input->post('name'));
			$email = base64_decode($this->input->post('email'));
			$image = base64_decode($this->input->post('image'));
			$type= base64_decode($this->input->post('type'));
		}else{
			$id = base64_decode($this->input->get('id'));
			$name = base64_decode($this->input->get('name'));
			$email = base64_decode($this->input->get('email'));
			$image = base64_decode($this->input->get('image'));
			$type= base64_decode($this->input->get('type'));
		}
		
		# create instance
		$mail = new PHPMailer(true); //defaults to using php "mail()"; the true param means it will throw exceptions on errors, which we need to catch
		
		# set type
		$data['name'] = $name;
		$data['link'] = "https://www.facebook.com/testAway/app_270914029690867?app_data=".$image;
		switch($type)		
		{
			case 'approved' :
				$html = $this->load->view('email_approved', $data, TRUE);
				$mail->Subject = 'Peace, love and ice cream ';
				break;
			 
			case 'denied' :
				$html = $this->load->view('email_denied', $data, TRUE);
				$mail->Subject = 'Peace, love and ice cream ';
				break;
		}
		
		// Send email!
		try 
		{
			$mail->AddReplyTo('noreply@benjerry.com', 'Ben en Jerry\'s - Peace, Love and Icecream');
			$mail->AddAddress($email, $name);
			$mail->SetFrom('noreply@benjerry.com', 'Ben en Jerry\'s - Peace, Love and Icecream');
			$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
			$mail->MsgHTML($html);
			//$mail->AddAttachment('../../resources/'.$language.'/images/mail/mail_header.jpg');
			//$mail->AddAttachment('../../resources/'.$language.'/images/mail/mail_footer.jpg');
			$mail->Send();
			
			$response['error'] = FALSE;
			$response['msg'] = 'Email was succesfully send to '.$name.'.';			
		} 
		catch (phpmailerException $e) 
		{
			$response['error'] = TRUE;
			$response['msg'] = $e->errorMessage(); //Pretty error messages from PHPMailer
		}
		catch (Exception $e) 
		{
			$response['error'] = TRUE;
			$response['msg'] = $e->getMessage(); //Boring error messages from anything else!
		}
		
		// Update database
		if(!$response['error'])
		{
			$update = $this->data_model->update_status($id, $type);
			
			if($update && !$update['error']) 
			{
				$response['msg'] .= '<br />The database was also successfully updated!';	
			}
			else if($update['error']) 
			{
				$response['error'] = TRUE;
				$response['msg'] = $update['msg'];	
			}
			else
			{
				$response['error'] = TRUE;
				$response['msg'] = 'There is no response.';			
			}
		}
		
		$this->common->json_response($response);
		
	}


///////////////	
// API GLOBAL METHODS
///////////////	
	
	
///////////////	
// PRIVATE METHODS
///////////////	

}

?>