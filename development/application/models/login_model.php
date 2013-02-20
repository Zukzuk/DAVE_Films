<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Login model class
 */
class Login_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
		
		$this->pdo = $this->db;
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
	
	public function check_login($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = FALSE;	
		if(!$this->check_isvalidated())
        {
        	// Not logged in or session elapsed
        	$this->login_model->logout();
			$response['msg'] = 'Not logged in.';
			$response['login_status'] = FALSE;
        }
		else 
		{
			// Already validated and session checks out
            $response['msg'] = 'Logged in.';
			$response['login_status'] = TRUE;
			$response['session'] = TRUE;	
			$response['firstname'] = $this->session->userdata('firstname');
			$response['lastname'] = $this->session->userdata('lastname');
		}
		
		return $response;		
	}

	public function process_login($_parameters)
	{
		$base64_name = $_parameters['username'];
		$base64_pw = $_parameters['password'];
		
		$response['error'] = FALSE;
		$response['session'] = FALSE;
		$response['login_status'] = FALSE;
		$response['validated'] = FALSE;	
		
		// grab user input
        //$str = do_hash($str); // SHA1
		//$str = do_hash($str, 'md5'); // MD5
        $username = base64_decode($base64_name);
        $password = do_hash(base64_decode($base64_pw), 'md5'); 
		//die($username." | ".$password);
			
		# PREPARE QUERY STATEMENTS
		$getUser = $this->pdo->prepare('SELECT * FROM users WHERE username = :username AND password = :password');
				
		# GET USER
		try
		{
			$getUser->execute(array(':username' => $username, ':password' => $password));
			$user = $getUser->fetchAll();
			$getUser->closeCursor();
		}
		catch(PDOException $e)
		{
			$response['error'] = TRUE;
			$response['msg'] = 'Error executing getUser: ' . $e->getMessage();
			return $response;
			die();
		}		
		
		# IF USER, SET STUFF
		if(count($user) == 1)
		{
			$row = $user[0];
		
			if($row['active'] == 1)
			{
				$data = array(
					'session' => TRUE,
					'login_status' => TRUE,					
					'validated' => TRUE,
					'uid' => $row['uid'],
					'firstname' => $row['firstname'],
					'lastname' => $row['lastname'],
					'role_id' => $row['role_id']
				);
           		$this->session->set_userdata($data);
				
				$response['msg'] = 'Login validated.';
				$response['login_status'] = TRUE;
				$response['session'] = TRUE;	
				$response['firstname'] = $this->session->userdata('firstname');
				$response['lastname'] = $this->session->userdata('lastname');
			}
			else 
			{
				$response['msg'] = 'This account is not active.';
			}
		}
		else
		{
			$response['msg'] = 'Wrong username and/or password.';
		}
		return $response;
	}

	public function process_logout()
    {        
		$this->logout();
		$response['msg'] = 'Logged out.';
		$response['error'] = FALSE;	
		$response['session'] = FALSE;	
		$response['login_status'] = FALSE;
		$response['validated'] = FALSE;	
		return $response;
    }
	
	public function logout()
    {        
		$this->session->sess_destroy(); 
    }
	
	public function check_isvalidated()
    {
    	if($this->session->userdata('validated') )
        {
	        return TRUE;
	    }
	    return FALSE;
    }	
	
}

?>