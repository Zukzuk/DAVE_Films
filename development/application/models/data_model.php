<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Data model class
 */
class Data_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
		
		$this->pdo = $this->db;
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
	
	
///////////////	
// GLOBAL
///////////////	


	public function get_user_privileges($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		$response['msg'] = 'Setting user privileges to '.$this->session->userdata('firstname').' '.$this->session->userdata('lastname');
		$response['login_status'] = $this->session->userdata('login_status');
		$response['firstname'] = $this->session->userdata('firstname');
		$response['lastname'] = $this->session->userdata('lastname');
		$response['role_id'] = $this->session->userdata('role_id');
		$response['uid'] = $this->session->userdata('uid');  
		return $response;
	}
	
	
///////////////	
// PROJECT_REPORTS MODULE
///////////////

	
	public function get_all_films($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		
		$directories = glob('F:/*' , GLOB_ONLYDIR);
		$count = 0;
		
		foreach ($directories as $key => $value) 
		{
			$directory = $directories[$key];
			$dir_name = str_replace("F:/", "", $directory);
			
			if($dir_name != "System Volume Information" && $dir_name != "FFOutput" && substr($dir_name, 1) != "RECYCLE.BIN")
			{
				$payload[$count]['name'] = "";
				$payload[$count]['year'] = "";
				$payload[$count]['data']['series'] = FALSE;				
				$payload[$count]['data']['types'] = FALSE;
				$payload[$count]['data']['directory'] = rawurlencode($dir_name); // space to %20
				$payload[$count]['data']['filename'] = "";
				$payload[$count]['data']['filetype'] = "";
				$payload[$count]['data']['subtitle'] = FALSE;
				$payload[$count]['data']['poster'] = FALSE;
				
				// get name
				$payload[$count]['name'] = $this->helper_get_name_from_dirname($dir_name);
					
				// get year
				$payload[$count]['year'] = $this->helper_get_year_from_dirname($dir_name);	
				
				// get series (Terminator -1- The Terminator)
				$result = $this->helper_get_series_and_name_from_currentname($payload[$count]['name']);	
				$payload[$count]['name'] = $result['name'];
				$payload[$count]['data']['series'] = $result['series'];
				
				// get type(s)
				// [lowres], [screener], [collection], [remake], [nosub], [nodub]
				$payload[$count]['data']['types'] = $this->helper_get_types_from_dirname($dir_name);
				
				// get contents from directory
				$contents = scandir($directory);
				foreach ($contents as $_key => $content) 
				{
					if($content === '.' or $content === '..') continue;
					
					$result = $this->helper_get_film_by_filetype($content);
					if($result)
					{
						$payload[$count]['data']['filename'] = $result['filename'];
						$payload[$count]['data']['filetype'] = $result['filetype'];
					}					
					
					$result = $this->helper_get_subtitle_by_filetype($content);	
					if($result)
					{
						$payload[$count]['data']['subtitle'] = $result['subtitle'];	
					}
					
					$result = $this->helper_get_poster_by_filetype($content);	
					if($result)
					{
						$payload[$count]['data']['poster'] = $result['poster'];	
					}					
				}
				$count++;	
			}	
		}
		
		// normalize films array
		$payload = array_values($payload);	
		
		$response['msg'] = 'Successfully fetched all films.';
		$response['payload'] = $payload;
		
		return $response;
	}

	public function get_collection($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		
		//http://localhost/DAVE_Films/trunk/development/secureddata/get_collection?testing=true&directory=Batman%20-3-%20Batman%20Complete%20Animated%20Series%201992%20%5bcollection%5d
		//http://localhost/DAVE_Films/trunk/development/secureddata/get_collection?testing=true&directory=Cowboy%20Bebop%20-1-%20Cowboy%20Bebop%20Complete%20Sessions%201998%20%5Bcollection%5D
		$directory = 'F:/'.rawurldecode($_parameters['directory']);
		$dir_name = rawurldecode($_parameters['directory']);
		
		$payload['name'] = "";
		$payload['year'] = "";
		$payload['data']['series'] = FALSE;	
		
		if(is_dir($directory)) 
		{
			$contents = scandir($directory);
		
			// get name
			$payload['name'] = $this->helper_get_name_from_dirname($dir_name);
				
			// get year
			$payload['year'] = $this->helper_get_year_from_dirname($dir_name);	
			
			// get series (Cowboy Bebop -1- Cowboy Bebop Complete Sessions)
			$result = $this->helper_get_series_and_name_from_currentname($payload['name']);	
			$payload['name'] = $result['name'];
			$payload['data']['series'] = $result['series'];
			
			$entries = array();		
			$count = 0;
			
			if(!empty($contents))
			{
				// entries in root, without subfolders
				$entries = $this->helper_get_films_from_directory($contents);
				
				// normalize collection array
				$entries = array_values($entries);
				$payload['data']['entries'] = $entries;
				
				// DEPRECATED! NO MORE SUBDIRS!
				/**
				 * 
				 *
				$sub_dirs = $this->helper_check_for_subdirectories($contents);
				if(!$sub_dirs)
				{
					// entries in root, without subfolders
					$entries = $this->helper_get_films_from_directory($contents);
					
					// normalize collection array
					$entries = array_values($entries);
					$payload['data']['entries'] = $entries;
				}
				else
				{
					// entries in subfolder(s)
					foreach ($sub_dirs as $key => $sub_dir) 
					{
						if($sub_dir === '.' or $sub_dir === '..') continue;
						
						$contents = scandir($directory.'/'.$sub_dir);
						$entries = $this->helper_get_films_from_directory($contents);
						//die(print_r($entries));
						
						// normalize collection array
						$entries = array_values($entries);
						$payload['data'][strtolower(str_replace(' ', '', $sub_dir))] = $entries;
					}
				}
				 *
				 */			
				
				$response['msg'] = 'Successfully fetched all collection entries.';
				$response['payload'] = $payload;
			}
			else
			{
				$response['msg'] = 'Directory is empty.';
				$response['payload'] = 'There is no payload here, dummy!'; 
			}				
		}
		else
		{
			$response['msg'] = 'Directory not found.';
			$response['payload'] = 'There is no payload here, dummy!'; 
		}
		
		return $response;
	}
	
	public function get_player_iframe($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		
		$file = 'player_iframe.php';
		$content = ''.
			'<html><head></head><body>'.
				'<video controls autoplay poster="' . $_parameters['poster'] . '" name="media">'.
					'<source src="' . $_parameters['film'] . '" type="video/mp4">'.
				'</video>'.
			'</body></html>';
		// Write the contents to the file, 
		// LOCK_EX flag to prevent anyone else writing to the file at the same time
		file_put_contents($file, $content, LOCK_EX);
		
		$response['msg'] = 'Successfully written php for iframe insertion.';
		$response['player_uri'] = $file;
		
		return $response;
	}
	
	private function helper_get_name_from_dirname($dir_name)
	{
		if(strpos($dir_name, '[') === FALSE)
			$name = substr($dir_name, 0, -5);				
		else 
			$name = substr( substr($dir_name, 0, strpos($dir_name, '[')-1), 0, -5);	
		return $name;
	}
	
	private function helper_get_year_from_dirname($dir_name)
	{
		if(strpos($dir_name, '[') === FALSE)
			$year = substr($dir_name, -4);			
		else 					
			$year = substr( substr($dir_name, 0, strpos($dir_name, '[')-1), -4);	
		return $year;
	}
	
	private function helper_get_series_and_name_from_currentname($currentname)
	{
		$result = FALSE;
		if (strpos($currentname, '-') !== FALSE) 
		{
			$names = explode('-', $currentname);
			if( substr($names[2], 1) == substr($names[0], 0, -1))
				$result['name'] = substr($names[2], 1);
			else
				$result['name'] = substr($names[0], 0, -1) ." - ". substr($names[2], 1);
		    $result['series'] = substr($names[0], 0, -1);
		}
		else
		{
			$result['name'] = $currentname;
			$result['series'] = FALSE;
		}
		return $result;
	}
	
	private function helper_get_types_from_dirname($dir_name)
	{
		$result = FALSE;
		if(strpos($dir_name, '[') !== FALSE)
		{
			$types = explode('[', substr($dir_name, strpos($dir_name, '[')+1) );
			if(count($types)) 
			{
				$result = array();
				foreach ($types as $key => $type) 
				{
					array_push($result, substr($type, 0, -1) );
				}
			}
		}
		return $result;
	}
	
	private function helper_get_film_by_filetype($file)
	{
		$result = FALSE;		
		$filetype = strtolower(substr($file, -3));
		if(	$filetype == 'mp4' ||
			$filetype == 'mkv' ||
			$filetype == 'm4v' ||
			$filetype == 'mov' ||
			$filetype == 'avi' ||
			$filetype == 'mpg' ||
			$filetype == 'wmv')
		{
			$result['filename'] = $file;
			$result['filetype'] = $filetype;
		}
		$filetype = strtolower(substr($file, -4));					
		if(	$filetype == 'xvid' ||
			$filetype == 'm2ts')
		{
			$result = array();	
			$result['filename'] = $file;
			$result['filetype'] = $filetype;
		}
		return $result;
	}
	
	private function helper_get_subtitle_by_filetype($file)
	{
		$result = FALSE;	
		$filetype = strtolower(substr($file, -3));	
		if(	$filetype == 'srt' ||
			$filetype == 'sub')
		{			
			$result['subtitle'] = $file;
		}	
		return $result;
	}
	
	private function helper_get_poster_by_filetype($file)
	{
		$result = FALSE;	
		$filetype = strtolower(substr($file, -3));
		if(	$filetype == 'jpg')
		{			
			$result['poster'] = $file;
		}	
		return $result;
	}
	
	private function helper_check_for_subdirectories($contents)
	{
		$result = FALSE;
		foreach ($contents as $key => $content) 
		{
			if($content === '.' or $content === '..') continue;
			
			// TODO: check for real dirs! glob () and is_dir somehow don't detect subdirs...
			$check1 = strtolower(substr($content, -3, 1));
			$check2 = strtolower(substr($content, -4, 1));
			if($check1 != '.' && $check2 != '.')
			{
				if(!$result) $result = array();
				array_push($result, $content);
			}
		}
		return $result;
	}
	
	private function helper_get_films_from_directory($contents)
	{
		$result = FALSE;
		foreach ($contents as $key => $content) 
		{
			if($content === '.' or $content === '..') continue;
			
			$_result = $this->helper_get_film_by_filetype($content);
			if($_result)
			{
				if(!$result) $result = array();
				array_push($result, $_result);
			}				
		}
		return $result;
	}
	
	
/*
///////////////	
// PROJECT_REPORTS MODULE
///////////////

	
	public function get_local_projects($_parameters)
	{		
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$projects = array();
	
		# PREPARE QUERY STATEMENTS
		$getLocalProjects = $this->pdo->prepare('SELECT * FROM _projects_view WHERE bc_status = :bc_status ORDER BY name ASC');
					
		# GET ALL PROJECTS FROM LOCAL DB
		try
		{	
			$getLocalProjects->execute(array(':bc_status' => 'active'));
			$localProjects = $getLocalProjects->fetchAll();
			$getLocalProjects->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing getLocalProjects: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN RESULT
		if(!$json['error'])
		{	
			foreach($localProjects as $row)
			{
				$clientName 		= $row['client_name'];
				$clientID	 		= $row['client_id'];
				$projectName 		= $row['name'];
				$projectID 			= $row['id'];
				$projectStatus 		= $row['project_status_name'];
				
				$project = array(
					'client_name' => $clientName,
					'client_id' => $clientID,
					'project_name' => $projectName,
					'project_id' => $projectID,
					'project_status' => $projectStatus,
					);
				$projects[$projectName] = $project;
			}
		}

		if(!$json['error'])
		{
			$json['msg'] = 'Local projects successfully retrieved!';
			$json['projects'] = array_reverse($projects);
		}
		
		return $json;
	}
	
	public function get_billing_sets($_parameters)
	{			
		# DECLARE
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$billing_sets = array();
			
		# PREPARE QUERY STATEMENTS
		$getBillingSets = $this->pdo->prepare('SELECT * FROM billing_sets ORDER BY name ASC');
				
		# GET RESOURCE SETS
		try
		{	
			$getBillingSets->execute();
			$billingSets = $getBillingSets->fetchAll();
			$getBillingSets->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getBillingSets: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# PARSE
		foreach($billingSets as $row)
		{
			$billing_set = array('billing_set_id' => $row['id'], 'billing_set_name' => $row['name']);
			$billing_sets[] = $billing_set;
		}
		
		# RETURN SETS ARRAY
		if(!$json['error'])
		{
			$json['msg'] = 'Billing sets successfully retrieved!';
			$json['billing_sets'] = $billing_sets;
		}
		
		return $json;
	}	
	
	public function get_project_statusses($_parameters)
	{
		# DECLARE
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$projectStatusses = array();
			
		# PREPARE QUERY STATEMENTS
		$getProjectStatusses = $this->pdo->prepare('SELECT * FROM project_statusses');
				
		# GET PROJECT
		try
		{	
			$getProjectStatusses->execute();
			$projectStatusses = $getProjectStatusses->fetchAll();
			$getProjectStatusses->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getProjectStatusses: ' . $e->getMessage();
			return $json;
			exit;
		}		
	
		// RETURN ARRAY
		if(!$json['error'])
		{
			$json['msg'] = 'Project statusses successfully retrieved!';
			$json['statusses'] = $projectStatusses;
		}
		
		return $json;
	}

	public function get_person_log($_parameters)
	{
		$_project_id = $_parameters['project_id'];
		$_user_id = $_parameters['user_id'];
		
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		
		# PREPARE QUERY STATEMENTS
		$getAllocations = $this->pdo->prepare('SELECT * FROM _allocations_view WHERE uid = :user_id AND project_id = :project_id ORDER BY date DESC');
						
		# GET ALL PROOJECTS FROM LOCAL DB
		try
		{	
			$getAllocations->execute(array(':user_id' => $_user_id, ':project_id' => $_project_id));
			$result = $getAllocations->fetchAll();
			$getAllocations->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing getLocalProjects: ' . $e->getMessage();
			return $json;
		}
		
		# RETURN RESULT
		if(!$json['error'])
		{			
			$json['msg'] = 'Projects per user successfully retrieved!';
			$json['allocations'] = $result;
		}
		
		return $json;
	}

	
	public function get_billing_set_data($_parameters)
	{			
		# DECLARE
		$_billing_set_id = $_parameters['billing_set_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$billing_set_data = array();
			
		# PREPARE QUERY STATEMENTS
		$getUsers = $this->pdo->prepare('SELECT * FROM _users_view');
		$getUserRate = $this->pdo->prepare('SELECT rate FROM billing_sets_data WHERE billing_set_id = :billing_set_id AND uid = :uid');
		
		# GET USERS
		try
		{	
			$getUsers->execute(array(':billing_set_id' => $_billing_set_id));
			$users = $getUsers->fetchAll();
			$getUsers->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getUsers: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		foreach($users as $row)
		{
			
			# GET USER RATE
			try
			{	
				$getUserRate->execute(array(':uid' => $row['uid'], ':billing_set_id' => $_billing_set_id));
				$user_rate = $getUserRate->fetchAll();
				$getUserRate->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing getUserRate: ' . $e->getMessage();
				return $json;
				exit;
			}
			
			// SET RATE
			(count($user_rate) == 1) ? $rate = $user_rate[0]['rate'] : $rate = 0;
			
			// CREATE SET ITEM
			$name = $row['firstname'] . ' ' . $row['lastname'];
			$item = array(	'uid' => $row['uid'],
							'active' => $row['active'],
							'name' => $name,
							'activity' => $row['activity'],
							'rate' => $rate,
							'billing_set_id' => $_billing_set_id);
							
			$billing_set_data[] = $item;
		}
		
		# RETURN SETS ARRAY
		if(!$json['error'])
		{
			$json['msg'] = 'Billing set data successfully retrieved!';
			$json['billing_set_data'] = $billing_set_data;
		}
		
		return $json;
	}
	
	
	public function save_billing_set_rate($_parameters)
	{
		# DECLARE
		$_billing_set_id = $_parameters['billing_set_id'];
		$_uid = $_parameters['uid'];
		$_rate = $_parameters['rate'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$action 			= '';
		$unique				= 0;
			
		# PREPARE QUERY STATEMENTS
		$checkExistence = $this->pdo->prepare('SELECT * FROM billing_sets_data WHERE billing_set_id = :billing_set_id AND uid = :uid');
		$insertBillingSetRate = $this->pdo->prepare('INSERT INTO billing_sets_data (billing_set_id,uid,rate) VALUES(:billing_set_id, :uid, :rate)');
		$updateBillingSetRate = $this->pdo->prepare('UPDATE billing_sets_data SET rate = :rate WHERE billing_set_id = :billing_set_id AND uid = :uid');

		
		# CHECK RATE EXISTENCE
		try
		{	
			$checkExistence->execute(array(':billing_set_id' => $_billing_set_id, ':uid' => $_uid));
			$unique = $checkExistence->fetchAll();
			$checkExistence->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
		}
		
		# SET ACTION MODE
		(count($unique) >= 1) ? $action = 'update' : $action = 'insert';
		
		# NEW RATE
		if($action == 'insert')
		{
			try
			{	
				$insertBillingSetRate->execute(array(':billing_set_id' => $_billing_set_id, ':uid' => $_uid, ':rate' => $_rate ));
				$insertBillingSetRate->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertBillingSetRate: ' . $e->getMessage();
			}		
		}
		
		# UPDATE RATE
		if($action == 'update')
		{
			try
			{	
				$updateBillingSetRate->execute(array(':billing_set_id' => $_billing_set_id, ':uid' => $_uid, ':rate' => $_rate ));
				$updateBillingSetRate->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateBillingSetRate: ' . $e->getMessage();
			}		
		}
		
		# RETURN
		if(!$json['error'])
		{
			$json['msg'] = 'Rate saved succesfully.';
		}
		
		return $json;
	}
	
	
	public function save_new_billing_set_name($_parameters)
	{
		# DECLARE
		$_name = $_parameters['name'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$unique				= 0;
			
		# PREPARE QUERY STATEMENTS
		$checkExistence = $this->pdo->prepare('SELECT * FROM billing_sets WHERE name = :name');
		$insertNewBillingSetName = $this->pdo->prepare('INSERT INTO billing_sets (name) VALUES(:name)');
		
		# CHECK RATE EXISTENCE
		try
		{	
			$checkExistence->execute(array(':name' => $_name));
			$unique = $checkExistence->fetchAll();
			$checkExistence->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
		}
		
		
		if(count($unique) >= 1)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Billing set name already exists';
		}
		
		else
		{
			# CHECK RATE EXISTENCE
			try
			{	
				$insertNewBillingSetName->execute(array(':name' => $_name));
				$new_billing_set_id = $this->pdo->lastInsertId();
				$insertNewBillingSetName->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
			}
		}
		
		// RETURN ARRAY
		if(!$json['error'])
		{
			$json['msg'] = 'New billing set name saved successfully';
			$json['billing_set_id'] = $new_billing_set_id;
		}
		
		return $json;
	}
	
	public function get_project_data($_parameters)
	{
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		//$json['msg'] = $_project_id;
		//return $json;
			
		# PREPARE QUERY STATEMENTS
		$getProjectData = $this->pdo->prepare('SELECT * FROM projects WHERE id = :project_id');
				
		# GET PROJECT META
		try
		{	
			$getProjectData->execute(array(':project_id' => $_project_id));
			$projectData = $getProjectData->fetchAll();
			$getProjectData->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getProjectData: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if($projectData)
		{
			$row = $projectData[0];
			$project_data = array('billing_set_id'=>$row['billing_set_id'], 'project_status_id' => $row['project_status_id']);
			
			$json['error'] = FALSE;
			$json['msg'] = 'Project data successfully retrieved!';
			$json['project_data'] = $project_data;
		}
		else
		{
			$json['error'] = TRUE;
			$json['msg'] = 'No results were found';
		}
		
		return $json;
	}
	
	public function get_activities($_parameters)
	{			
		# DECLARE
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$activities = array();
			
		# PREPARE QUERY STATEMENTS
		$getActivities = $this->pdo->prepare('SELECT * FROM activities ORDER BY `order` ASC');
				
		# GET ACTIVITIES
		try
		{	
			$getActivities->execute();
			$result = $getActivities->fetchAll();
			$getActivities->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getActivities: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# PARSE
		foreach($result as $row)
		{
			$activity = array( 'activity_name' => $row['name'], 'activity_id' => $row['id'] );
			$activities[] = $activity;
		}
	
		// RETURN ARRAY
		if(!$json['error'])
		{
			$json['msg'] = 'Activities successfully retrieved!';
			$json['activities'] = $activities;
		}
		
		return $json;
	}
	
	public function save_activity($_parameters)
	{
		# SET VALUES	
		$_activity_name = $_parameters['activity_name'];
		$_activity_id = $_parameters['activity_id'];	
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		
		if($_activity_id == 'new')
		{
			// Create
			$insertActivity = $this->pdo->prepare('INSERT INTO activities (name) VALUES (:name)');
			
			try
			{
				$insertActivity->execute(array(':name' => $_activity_name));
				$new_activity_id = $this->pdo->lastInsertId();
				$insertActivity->closeCursor();
				$json['activity_id'] = $new_activity_id;
				$json['new'] = TRUE;
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertActivity: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
		else
		{	
			// update
			$updateActivity = $this->pdo->prepare('UPDATE activities SET name = :name WHERE id = :id');
			
			try
			{
				$updateActivity->execute(array(':name' => $_activity_name, ':id' => $_activity_id));
				$updateActivity->closeCursor();
				$json['activity_id'] = $_activity_id;
				$json['new'] = FALSE;
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateActivity: ' . $e->getMessage();
				return $json;
				exit;
			}
		}

		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Activity saved successfuly.';
			$json['activity_name'] = $_activity_name;
		}
		
		return $json;
	}


	public function delete_activity($_parameters)
	{
		# SET VALUES
		$_activity_id = $_parameters['activity_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		
		$deleteActivity = $this->pdo->prepare('DELETE FROM activities WHERE id = :id');
		
		try
		{
			$deleteActivity->execute(array(':id' => $_activity_id));
			$affRows = $deleteActivity->rowCount();
			$deleteActivity->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing deleteActivity: ' . $e->getMessage();
			return $json;
			exit;
		}

		if(!$affRows)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Could not delete the activity: the activity_id propably doesn\'t excist';
		}
		

		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Activity deleted successfuly.';
		}
		
		return $json;
	}
	
	public function get_budgets($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$all_budgets = array();
		
		# PREPARE QUERY STATEMENTS
		$getBudgets = $this->pdo->prepare('SELECT * FROM _budgets_view WHERE project_id = '.$_project_id.' ORDER BY project_id ASC');
				
		# GET ACTIVITIES
		try
		{	
			$getBudgets->execute(array(':project_id' => $_project_id));
			$budgets = $getBudgets->fetchAll();
			$getBudgets->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getBudgets: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if(!$json['error'])
		{
			foreach($budgets as $row)
			{
				$budget = array(
					'project_ID' => $row['project_id'], 
					'activity_id' => $row['activity_id'],
					'activity_name' => $row['activity_name'],
					'hours' => $row['hours'], 
					'budget' => $row['budget']);
				$all_budgets[] = $budget;
			}
			
			$json['msg'] = 'Budgets successfully retrieved!';
			$json['budgets'] = $all_budgets;
		}
		
		return $json;
	}
	
	public function get_payment_statusses($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		$getPaymentStatusses = $this->pdo->prepare('SELECT * FROM payment_statusses');
				
		# GET PROJECT META
		try
		{	
			$getPaymentStatusses->execute();
			$paymentStatusses = $getPaymentStatusses->fetchAll();
			$getPaymentStatusses->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getPaymentStatusses: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if($paymentStatusses)
		{			
			$json['msg'] = 'Payment statusses successfully retrieved!';
			$json['payment_statusses'] = $paymentStatusses;
		}
		else
		{
			$json['error'] = TRUE;
			$json['msg'] = 'No results were found';
		}
		
		return $json;
	}
	
	public function get_contractors($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		$getContractors = $this->pdo->prepare('SELECT * FROM contractors WHERE project_id = :project_id ORDER BY id ASC');
				
		# GET PROJECT META
		try
		{	
			$getContractors->execute(array(':project_id' => $_project_id));
			$contractors = $getContractors->fetchAll();
			$getContractors->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getContractors: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if($contractors)
		{							
			foreach($contractors as $row)
			{
				$contractor = array(
					'id' => $row['id'],
					'contractor' => $row['contractor'], 
					'description' => $row['description'], 
					'activity_id' => $row['activity_id'], 
					'hours' => $row['hours'], 
					'rate' => $row['rate'], 
					'payment_status_id' => $row['payment_status_id']
				);
				foreach($contractor as $key => $value)
				{
					if($value === 'undefined')
					{
						$contractor[$key] = 'No '.$key.' found.';
					}
				}				
				$all_contractors[] = $contractor;
			}
			
			$json['msg'] = 'Contractors successfully retrieved!';
			$json['contractors'] = $all_contractors;
		}
		else
		{
			$json['msg'] = 'No results were found';
		}
		
		return $json;
	}

	public function get_project_comments($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$comments = array();
			
		# PREPARE QUERY STATEMENTS
		$getComments = $this->pdo->prepare('SELECT comments.*, users.firstname, users.lastname FROM comments LEFT JOIN users ON users.uid = comments.uid WHERE project_id = :project_id ORDER BY id ASC');
				
		# GET PROJECT META
		try
		{	
			$getComments->execute(array(':project_id' => $_project_id));
			$commentsData = $getComments->fetchAll();
			$getComments->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getComments: ' . $e->getMessage();
			return $json;
			exit;
		}

		if($commentsData)
		{
			foreach($commentsData as $row)
			{
				
				$firstname = $row['firstname'];
				$lastname = $row['lastname'];
				$comment = $row['comment'];
				$time_stamp = $row['time_stamp'];

				if($firstname == NULL || $firstname == 'NULL')
				{
					$firstname = 'Imported';
					$lastname = '';
				}

				$comment = array('firstname' => $firstname, 'lastname' => $lastname, 'comment' => $comment, 'time_stamp' => $time_stamp);
				$comments[] = $comment;
			}

			if(!$json['error'])
			{				
				$json['msg'] = 'Retrieved the comments succesfully';
				$json['comments'] = $comments;
			}
		}
		elseif(!$json['error'])
		{
			$json['msg'] = 'No results were found';
			$json['comments'] = 0;
		}
		
		# RETURN RESULT
		return $json;
	}

	public function get_project_report($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		
		// Get all activities
		$getActivities = $this->pdo->prepare('SELECT `id`, `name`, `order` FROM activities ORDER BY `order` ASC');
		
		try
		{	
			$getActivities->execute(array(':uid' => $this->session->userdata('uid')));
			$activities = $getActivities->fetchAll();
			$getActivities->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getActivities: ' . $e->getMessage();
			return $json;
			exit;
		}
		foreach($activities as $row)
		{
			$title = $row['name'];
			$dataset[$title]['info']['activity_id'] 	= $row['id'];
			$dataset[$title]['info']['name'] 			= $row['name'];
			
			// Create empty objects so we always have a result 
			$dataset[$title]['data']['total']['hours_confirmed'] = 0;
			$dataset[$title]['data']['total']['money_confirmed'] = 0;
			$dataset[$title]['data']['total']['hours_planned'] = 0;
			$dataset[$title]['data']['total']['money_planned'] = 0;
			$dataset[$title]['data']['total']['hours_result'] = 0;
			$dataset[$title]['data']['total']['money_result'] = 0;
		}	
		
		// Create the total rows
		$json['total'] = array();
		$json['total']['hours_confirmed'] 	= 0;
		$json['total']['money_confirmed'] 	= 0;
		$json['total']['hours_planned'] 	= 0;
		$json['total']['money_planned'] 	= 0;
		$json['total']['hours_result'] 		= 0;
		$json['total']['money_result'] 		= 0;
			
		# Get and process internal hours
		$getInternalHours = $this->pdo->prepare('
		SELECT
		uid,
		username_first,
		username_last,
		activity_id,
		activity_name,
		SUM(hours) AS activity_total_hours,
		projects.billing_set_id
		
		FROM _allocations_view
		
		JOIN projects
		ON projects.id = _allocations_view.project_id
		
		WHERE _allocations_view.project_id = :project_id
		
		GROUP BY uid');
				
		# GET RESOURCES OF THE SET
		try
		{	
			$getInternalHours->execute(array(':project_id' => $_project_id));
			$internalHours = $getInternalHours->fetchAll();
			$getInternalHours->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getInternalHours: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN RESULT
		// TODO DEBUG foreach loop!!
		if($internalHours)
		{
			$count = 0;
			
			foreach($internalHours as $row)
			{
				if($row['username_first'])
				{
					$getPersonRateInBillingSet = $this->pdo->prepare('SELECT rate FROM billing_sets_data WHERE uid = :uid AND billing_set_id = :billing_set_id');
					$getPersonRateInBillingSet->execute(array(':uid' => $row['uid'], ':billing_set_id' => $row['billing_set_id']));
					$personRateInBillingSet = $getPersonRateInBillingSet->fetchAll();
					$getPersonRateInBillingSet->closeCursor();				
					
					if(isset($personRateInBillingSet[0])) 
						$rate = $personRateInBillingSet[0]['rate'];
					else
						$rate = 0;
					
					//$dataset['queries'][] = $getPersonRateInBillingSet;
					
					$dataset[$row['activity_name']]['data']['user'][$count] = $row;
					$dataset[$row['activity_name']]['data']['user'][$count++]['rate'] = $rate;
					
					// Get the confirmed hours
					$dataset[$row['activity_name']]['data']['total']['hours_confirmed'] += $row['activity_total_hours'];
					$dataset[$row['activity_name']]['data']['total']['money_confirmed'] += ($row['activity_total_hours'] * (int)$rate);
					
					// Start calculating the result
					$dataset[$row['activity_name']]['data']['total']['hours_result'] -= $row['activity_total_hours'];
					$dataset[$row['activity_name']]['data']['total']['money_result'] -= ($row['activity_total_hours'] * (int)$rate);
					
					$json['total']['hours_confirmed'] += $row['activity_total_hours'];
					$json['total']['money_confirmed'] += ($row['activity_total_hours'] * (int)$rate);
				}
			}
		}		
		
		# Get and process External hours
		$getExternalHours = $this->pdo->prepare('
			SELECT
				*,
				activities.name AS activity_name
			FROM contractors
			JOIN activities
			ON activities.id = contractors.activity_id
			WHERE contractors.project_id = :project_id');
				
		# GET RESOURCES OF THE SET
		try
		{	
			$getExternalHours->execute(array(':project_id' => $_project_id));
			$externalHours = $getExternalHours->fetchAll();
			$getExternalHours->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getExternalHours: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if($externalHours)
		{
			foreach($externalHours as $row)
			{
				$dataset[$row['activity_name']]['data']['contractor'][] = $row;
				
				// Save the activities total values
				$dataset[$row['activity_name']]['data']['total']['hours_confirmed'] += $row['hours'];
				$dataset[$row['activity_name']]['data']['total']['money_confirmed'] += ($row['hours'] * $row['rate']);
				
				$dataset[$row['activity_name']]['data']['total']['hours_result'] -= $row['hours'];
				$dataset[$row['activity_name']]['data']['total']['money_result'] -= ($row['hours'] * $row['rate']);
				
				// save the total values
				$json['total']['hours_confirmed'] += $row['hours'];
				$json['total']['money_confirmed'] += ($row['hours'] * $row['rate']);
			}
		}	
		
		# Get planned values 
		$getPlannedValues = $this->pdo->prepare('
			SELECT
				project_budget_per_activity.*,
				projects.name,
				activities.name AS activity_name
				
			FROM project_budget_per_activity
			
			JOIN projects
			ON projects.id = project_budget_per_activity.project_id
			
			JOIN activities
			ON activities.id = project_budget_per_activity.activity_id
			
			WHERE project_budget_per_activity.project_id = :project_id');
				
		# GET RESOURCES OF THE SET
		try
		{	
			$getPlannedValues->execute(array(':project_id' => $_project_id));
			$plannedValues = $getPlannedValues->fetchAll();
			$getPlannedValues->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getExternalHours: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# RETURN RESULT
		if($plannedValues)
		{
			foreach($plannedValues as $row)
			{
				// Save the activities total
				$dataset[$row['activity_name']]['data']['total']['hours_planned'] = 0+$row['hours'];
				$dataset[$row['activity_name']]['data']['total']['money_planned'] = 0+$row['budget'];
				
				// Calculate the activities result
				$dataset[$row['activity_name']]['data']['total']['hours_result'] += $row['hours'];
				$dataset[$row['activity_name']]['data']['total']['money_result'] += $row['budget'];				

				// Calculate the total ()
				$json['total']['hours_planned'] += (int)$row['hours'];
				$json['total']['money_planned'] += (int)$row['budget'];
				
				// working
			}
			
		}
		$json['total']['hours_result'] = $json['total']['hours_planned'] - $json['total']['hours_confirmed'] ;
		$json['total']['money_result'] = $json['total']['money_planned'] - $json['total']['money_confirmed'];

		$json['msg'] = 'Reporting dataset succesfully retrieved!';
		$json['report'] = $dataset;
		
		
					
		return $json;
	}

	public function save_budget($_parameters)
	{			
		# DECLARE
		$_activity = $_parameters['activity'];
		$_type = $_parameters['type'];
		$_value = $_parameters['value'];
		$_project_id = $_parameters['project_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$action 			= '';
		$unique				= 0;
			
		# PREPARE QUERY STATEMENTS
		$checkExistence = $this->pdo->prepare('SELECT * FROM project_budget_per_activity WHERE project_id = :project_id AND activity_id = :activity');
	
		$insertBudget = $_type == 'money'
		? $this->pdo->prepare('INSERT INTO project_budget_per_activity (project_id,activity_id,budget) VALUES(:project_id, :activity, :value)')
		: $this->pdo->prepare('INSERT INTO project_budget_per_activity (project_id,activity_id,hours) VALUES(:project_id, :activity, :value)');
		
		$updateBudget = $_type == 'money'
		? $this->pdo->prepare('UPDATE project_budget_per_activity SET budget = :value WHERE project_id = :project_id AND activity_id = :activity')
		: $this->pdo->prepare('UPDATE project_budget_per_activity SET hours =  :value WHERE project_id = :project_id AND activity_id = :activity');
					
	
		$json['input'] 	= $_activity .'/'. $_type .'/'. $_value .'/'. $_project_id;			
		$json['updateQ'] 	= $updateBudget;			
		$json['insertQ'] 	= $insertBudget;			
		
		# CHECK COMMENT EXISTENCE
		try
		{	
			$checkExistence->execute(array(':project_id' => $_project_id, ':activity' => $_activity));
			$unique = $checkExistence->fetchAll();
			$checkExistence->closeCursor();
			$json['unique'] = $checkExistence;
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# SET ACTION MODE
		(count($unique) == 1) ? $action = 'update' : $action = 'insert';
		
		# NEW BUDGET
		if($action == 'insert')
		{
			try
			{	
				$insertBudget->execute(array(':project_id' => $_project_id, ':activity' => $_activity, ':value' => $_value ));
				$affRows = $insertBudget->rowCount();
				$insertBudget->closeCursor();
				$json['insert'] = $affRows;
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertBudget: ' . $e->getMessage();
				return $json;
				exit;
			}		
		}
		
		# UPDATE BUDGET
		if($action == 'update')
		{
			try
			{	
				$updateBudget->execute(array(':project_id' => $_project_id, ':activity' => $_activity, ':value' => $_value));
				$affRows = $updateBudget->rowCount();
				$updateBudget->closeCursor();
				$json['update'] = $updateBudget;
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateBudget: ' . $e->getMessage();
				return $json;
				exit;
			}		
		}
		
		# RETURN
		if(!$json['error'])
		{
			$json['msg'] = 'Budget saved succesfully.';
		}
		
		return $json;
	}
	
	public function save_comment($_parameters)
	{		
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$_comment = $_parameters['comment'];
		$action = '';
		$json['error'] = FALSE;
		$json['session'] = TRUE;

		# PREPARE QUERY STATEMENTS
		$insertComments = $this->pdo->prepare('
			INSERT INTO comments 
				(project_id,comment, uid) 
				VALUES(:project_id, :comment, :uid)');
		
		try
		{	
			$insertComments->execute(array(':project_id' => $_project_id, ':comment' => $_comment, ':uid' => $this->session->userdata('uid') ));
			$insertComments->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing insertComments: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN
		if(!$json['error'])
		{
			$json['msg'] = 'Comments saved succesfully.';
			
			$json['comment']['id'] = $this->pdo->lastInsertId();
			$json['comment']['uid'] = $this->session->userdata('uid');
			$json['comment']['comment'] = $_comment;
			$json['comment']['time_stamp'] = 'Just now';
			$json['comment']['firstname'] = 'You';
			$json['comment']['lastname'] = '';		
		}
		
		return $json;
	}
	
	public function set_billing_set($_parameters)
	{		
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$_billing_set_id = $_parameters['billing_set_id'];
		$action = '';
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		$checkExistence = $this->pdo->prepare('SELECT id FROM projects WHERE id = :project_id');
		$insertProjectData = $this->pdo->prepare('INSERT INTO projects (id, billing_set_id) VALUES(:project_id, :resource_set_id)');
		$updateProjectData = $this->pdo->prepare('UPDATE projects SET billing_set_id = :billing_set_id WHERE id = :project_id');
		
		# CHECK EXISTENCE
		try
		{	
			$checkExistence->execute(array(':project_id' => $_project_id));
			$unique = $checkExistence->fetchAll();
			$checkExistence->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# SET ACTION TO TAKE
		(count($unique) == 1) ? $action = 'update' : $action = 'insert';		
		
		# INSERT NEW PROJECT (META)
		if($action == 'insert')
		{
			try
			{	
				$insertProjectData->execute(array(':project_id' => $_project_id, ':billing_set_id' => $_billing_set_id));
				$insertProjectData->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertProjectData: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
		
		# UPDATE EXISTING PROJECT (META)
		if($action == 'update')
		{
			try
			{	
				$updateProjectData->execute(array(':project_id' => $_project_id, ':billing_set_id' => $_billing_set_id));
				$updateProjectData->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateProjectData: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
	
		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Billing set saved succesfully.';
		}
		
		return $json;
	}

	public function set_project_status($_parameters)
	{
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$_project_status_id = $_parameters['project_status_id'];
		$action = '';
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		$checkExistence = $this->pdo->prepare('SELECT id FROM projects WHERE id = :project_id');
		$insertProjectData = $this->pdo->prepare('INSERT INTO projects (id, project_status_id) VALUES(:project_id, :project_status_id)');
		$updateProjectData = $this->pdo->prepare('UPDATE projects SET project_status_id = :project_status_id WHERE id = :project_id');
		
		# CHECK EXISTENCE
		try
		{	
			$checkExistence->execute(array(':project_id' => $_project_id));
			$unique = $checkExistence->fetchAll();
			$checkExistence->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing checkExistence: ' . $e->getMessage();
			return $json;
			exit;
		}		
		
		# SET ACTION TO TAKE
		(count($unique) == 1) ? $action = 'update' : $action = 'insert';		
		
		# INSERT NEW PROJECT (META)
		if($action == 'insert')
		{
			try
			{	
				$insertProjectData->execute(array(':project_id' => $_project_id, ':project_status_id' => $_project_status_id));
				$insertProjectData->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertProjectData: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
		
		# UPDATE EXISTING PROJECT (META)
		if($action == 'update')
		{
			try
			{	
				$updateProjectData->execute(array(':project_id' => $_project_id, ':project_status_id' => $_project_status_id));
				$updateProjectData->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateProjectData: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
	
		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Project status saved succesfully.';
		}
		
		return $json;
	}

	public function save_contractor($_parameters)
	{
		# SET VALUES	
		$_project_id = $_parameters['project_id'];
		$_row_id = $_parameters['row_id'];
		$_contractor = $_parameters['contractor'];
		$_activity_id = $_parameters['activity_id'];
		$_hours = $_parameters['hours'];
		$_rate = $_parameters['rate'];
		$_payment_status_id = $_parameters['payment_status_id'];
		$_description = $_parameters['description'];	
		$action = '';
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$_activity_id	= $_activity_id == 'other' ? '0' : $_activity_id;
		$_rate 			= str_replace(",", ".", $_rate);	
			
		# PREPARE QUERY STATEMENTS
		$insertContractor = $this->pdo->prepare('INSERT INTO contractors (project_id,contractor,description,activity_id,hours,rate,payment_status_id) VALUES (:project_id,:contractor,:description,:activity_id,:hours,:rate,:payment_status_id)');
		$updateContractor = $this->pdo->prepare('UPDATE contractors 
			SET 
				project_id = :project_id, 
				contractor = :contractor, 
				description = :description,
				activity_id = :activity_id,
				hours = :hours, 
				rate = :rate,
				payment_status_id = :payment_status_id 
			WHERE id = :id');		

		# SET ACTION BASED ON EXISTENCE
		$_row_id == 'new' ? $action = 'insert' : $action = 'update';					
		
		# INSERT NEW CONTRACTOR
		if($action == 'insert')
		{
			try
			{	
				$insertContractor->execute(array(
					':project_id' => $_project_id,
					':contractor' => $_contractor,
					':description' => $_description,
					':activity_id' => $_activity_id, 
					':hours' => $_hours,
					':rate' => $_rate,
					':payment_status_id' => $_payment_status_id));
				$insertContractor->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing insertContractor: ' . $e->getMessage();
				return $json;
				exit;
			}
			$json['saved_id'] = $this->pdo->lastInsertId();
		}
		
		# UPDATE EXISTING CONTRACTOR
		if($action == 'update')
		{
			try
			{	
				$updateContractor->execute(array(
					':project_id' => $_project_id,
					':contractor' => $_contractor,
					':description' => $_description,
					':activity_id' => $_activity_id, 
					':hours' => $_hours,
					':rate' => $_rate,
					':payment_status_id' => $_payment_status_id, 
					':id' => $_row_id));						
				$updateContractor->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] = TRUE;
				$json['msg'] = 'Error executing updateContractor: ' . $e->getMessage();
				return $json;
				exit;
			}
		}
		
		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Contractor saved successfuly.';
		}
		
		return $json;
	}

	public function delete_contractor($_parameters)
	{
		// SET, DECLARE
		$_id = $_parameters['row_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		//$delContractor = $pdo->prepare('DELETE FROM resource_custom WHERE project_id = :project_id AND id = :id');
		$delContractor = $this->pdo->prepare('DELETE FROM contractors WHERE id = :id');
				
		# DELETE CONTRACTOR
		try
		{	
			$delContractor->execute(array(':id' => $_id));
			$affRows = $delContractor->rowCount();
			$delContractor->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing delContractor: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN RESULT
		if($affRows >= 1)
		{
			$json['error'] = FALSE;
			$json['msg'] = 'Contractor is deleted succesfully.';
			$json['deletedRowID'] = $_id;
		}
		else
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error deleting contractor.';
		}
		
		return $json;
	}
	
///////////////	
// TIME_REGISTRATION MODULE
///////////////

		
	public function get_basecamp_projects($_parameters)
	{
		
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$projects = array();
		
		# INCLUDE BASECAMP API
		require 'application/libraries/basecamp/Basecamp.class.php';
		
		# DECLARE
		$_token = $this->session->userdata('bc_token');
		$bcObject = new Basecamp(BASECAMP_URL, $_token);
		
		# GET ALL PROOJECTS FROM BASECAMP
		$result = $bcObject->getProjects();
		$xml =  new SimpleXMLElement($result['body']);
		
		foreach($xml as $key => $value)
		{
			$projectStatus = strip_tags($value->status->asXML());
			
			if($projectStatus == 'active')
			{	
				$clientName 		= strip_tags($value->company->name->asXML());
				$clientID	 		= strip_tags($value->company->id->asXML());
				$projectName 		= strip_tags($value->name->asXML());
				$projectID 			= strip_tags($value->id->asXML());
 * 
				$project = array(
					'client_name' => $clientName,
					'client_id' => $clientID,
					'project_name' => $projectName,
					'project_id' => $projectID,
					'project_status' => $projectStatus,
					);
				$projects[$clientName][] = $project;
			}
		}
		
		if(!$json['error'])
		{
			$json['msg'] = 'Basecamp projects successfully retrieved!';
			$json['projects'] = $projects;
		}
		
		return $json;
	}

	public function get_allocations($_parameters)
	{ 
	    # DECLARE
	    $_dates = $_parameters['dates'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$all_allocations = array();		
		$_uid = $this->session->userdata('uid');		
		
		# PREPARE QUERY STATEMENTS
		$getAllocations = $this->pdo->prepare('
			SELECT * 
			FROM _allocations_view
			WHERE (uid = :uid AND date IN(:date0, :date1, :date2, :date3, :date4, :date5, :date6))'
		);
					
		# GET TIME ENTRIES
		try
		{	
			$getAllocations->execute(array(':uid' => $_uid, ':date0' => $_dates[0], ':date1' => $_dates[1], ':date2' => $_dates[2], ':date3' => $_dates[3], ':date4' => $_dates[4], ':date5' => $_dates[5], ':date6' => $_dates[6]));
			$allocations = $getAllocations->fetchAll();
			$getAllocations->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing getAllocations: ' . $e->getMessage();
		}
		
		# RETURN RESULT
		if(!$json['error'])
		{
			foreach($allocations as $row)
			{
				$allocation = array(
					'allocation_id'=>$row['allocation_id'], 
					'uid'=>$row['uid'], 
					'project_id'=>$row['project_id'], 
					'project_name'=>$row['project_name'], 
					'client_name'=>$row['client_name'],
					'client_id'=>$row['client_id'],
					'hours'=>$row['hours'], 
					'date'=>$row['date'], 
					'desc'=>$row['description']);
				$all_allocations[] = $allocation;
			}
			
			$json['msg'] = 'Allocations successfully retrieved!';
			$json['allocations'] = $all_allocations;
		}
		
		return $json;
	}

	public function save_allocation($_parameters)
	{			
		# DECLARE
		$_project_id = $_parameters['project_id'];
		$_date = $_parameters['date'];
		$_hours = $_parameters['hours'];
		$_description = $_parameters['description'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$project_name 		= '';
		$_description 		= $_description ? $_description : '';
		$_uid 				= $this->session->userdata('uid');
		
		# PREPARE QUERY STATEMENTS
		$getProjectName = $this->pdo->prepare('SELECT name FROM projects WHERE id = :project_id');
		$insertAllocation = $this->pdo->prepare('INSERT INTO allocations (uid,project_id,date,hours,description) VALUES(:uid,:project_id,:date,:hours,:description)');
		
		# GET PROJECT NAME TO RETURN
		try
		{	
			$getProjectName->execute(array(':project_id' => $_project_id));
			$result = $getProjectName->fetchAll();
			$project_name = $result[0]['name'];
			$getProjectName->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing getProjectName: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# SAVE ALLOCATION
		try
		{	
			$insertAllocation->execute(array(':uid' => $_uid, ':project_id' => $_project_id, ':date' => $_date, ':hours' => $_hours, ':description' => $_description));
			$allocation_id = $this->pdo->lastInsertId();
			$insertAllocation->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error executing insertAllocation: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN
		if(!$json['error'])
		{
			$json['msg'] = 'Time entry is saved succesfully.';
			$json['allocation_id'] = $allocation_id;
			$json['project_name'] = $project_name;
			$json['description'] = $_description; // we need to return a empty string, do not delete!
		}
		
		return $json;
	}

	public function update_allocation($_parameters)
	{			
		# DECLARE	
		$_new_hours = $_parameters['hours'];
		$_allocation_id = $_parameters['allocation_id'];
		$_description = $_parameters['description'];	
		$json['error'] = FALSE;
		$json['session'] = TRUE;
					
		$updateAllocation = $this->pdo->prepare('
		UPDATE allocations
		SET
			hours = :new_hours,
			description = :description
		WHERE
			id = :allocation_id
		');
		
		try
		{
			$updateAllocation->execute(array(':new_hours' => $_new_hours, ':description' => $_description, ':allocation_id' => $_allocation_id));
			$result = $updateAllocation->rowCount();
			$updateAllocation->closeCursor();
		}
		catch (PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 		= 'Error executing updateAllocation: ' . $e->getMessage();
			return $json;
			exit;
		}
		
		# RETURN RESULT
		if(!$json['error'])
		{				
			$json['msg'] = 'Updated time entries succesfully';
			$json['vars']['hours'] = $_new_hours;
			$json['vars']['desc'] = $_description;
			$json['vars']['allocation_id'] = $_allocation_id;
		}
		
		return $json;
	}

	public function delete_allocation($_parameters)
	{			
		// SET, DECLARE
		$_allocation_id = $_parameters['allocation_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
			
		# PREPARE QUERY STATEMENTS
		$delAllocation = $this->pdo->prepare('DELETE FROM allocations WHERE id = :allocation_id'); 
		
		# GET ACTIVITY NAME
		try
		{
			$delAllocation->execute(array(':allocation_id' => $_allocation_id));
			$json['error'] = FALSE;
			$json['msg'] = 'Deleted allocation succesfully';
		}
		catch(PDOException $e)
		{
			$json['error'] = TRUE;
			$json['msg'] = 'Error deleting time entry: ' . $e->getMessage();
		}
		
		return $json;
	}
	
///////////////	
// TIME_REPORT MODULE
///////////////

	public function get_time_report_data($_parameters)
	{ 
	    # DECLARE
	    $_from = $_parameters['from'];
	    $_to = $_parameters['to'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$weekDays = array(1,2,3,4,5); // mon, tue, wed, thu, fri
		$all_allocations = array();
		$day = 86400;
    	$format = 'Y-m-d';
	    $from = strtotime($_from);
    	$to = strtotime($_to);
	    $numDays = round(($to - $from) / $day) + 1;
		
    	$days = array();
		$weekdaysOnly = array();
		$workingdays = 0;
		$time_report_data = array();
        
		# GET ALL DATES FROM GIVEN TIME RANGE
    	for ($i = 0; $i < $numDays; $i++) {
        	$days[] = date($format, ($from + ($i * $day)));
    	}
		
		# GET WEEK DAYS ONLY
		for($j = 0; $j < count($days); $j++)
		{
			if (in_array(date("w", strtotime($days[$j])), $weekDays))
			{
				$weekdaysOnly[] = $days[$j];
			}
		}
		
		# SET WOKING DAYS
		$workingdays = count($weekdaysOnly);
		// echo 'workingdays = ' . $workingdays;
		
		
		# PREPARE QUERY STATEMENTS
		$days_to_string = implode("\", \"", $days);
		$getAllocationsFromTimeRange = $this->pdo->prepare('SELECT * FROM _allocations_view WHERE date IN ("'.$days_to_string.'") ORDER BY uid ASC');
					
		# GET TIME ENTRIES
		try
		{	
			$getAllocationsFromTimeRange->execute();
			$allocations = $getAllocationsFromTimeRange->fetchAll();
			$getAllocationsFromTimeRange->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing getAllocationsFromTimeRange: ' . $e->getMessage();
		}
		
		
		# RETURN RESULT
		if(!$json['error'])
		{
			foreach($allocations as $row)
			{
				// set array key for parsing
				$key = $row['uid'];

				// set post_type
				if($row['client_name'] == "Fitzroy")
				{
					if (stripos($row['project_name'],'Ziek') !== false)	$post_type = 'sick';
					else if (stripos($row['project_name'],'Vrij') !== false) $post_type = 'holiday';
					else $post_type = 'internal';
				}
				else $post_type = 'projects';

				
				// start parsing
				if(!array_key_exists($key , $time_report_data)) // new employee, create object
				{
					$time_report_data[$key]['uid'] = $row['uid'];
					$time_report_data[$key]['employee'] = $row['username_first'] . ' ' .  $row['username_last'];
					$time_report_data[$key]['hours_contract'] = $row['hours_contract'];
					$time_report_data[$key]['to_book'] = ($row['hours_contract'] / 5) * $workingdays;
					$time_report_data[$key]['booked'] = $row['hours'];
					
					switch($post_type)
					{
						case 'sick' :
							$time_report_data[$key]['sick'] = $row['hours'];
							$time_report_data[$key]['holiday'] = 0;
							$time_report_data[$key]['internal'] = 0;
							$time_report_data[$key]['projects'] = 0;
							break;
							
						case 'holiday' :
							$time_report_data[$key]['sick'] = 0;
							$time_report_data[$key]['holiday'] = $row['hours'];
							$time_report_data[$key]['internal'] = 0;
							$time_report_data[$key]['projects'] = 0;
							break;
							
						case 'internal' :
							$time_report_data[$key]['sick'] = 0;
							$time_report_data[$key]['holiday'] = 0;
							$time_report_data[$key]['internal'] = $row['hours'];
							$time_report_data[$key]['projects'] = 0;
							break;
							
						case 'projects' :
							$time_report_data[$key]['sick'] = 0;
							$time_report_data[$key]['holiday'] = 0;
							$time_report_data[$key]['internal'] = 0;
							$time_report_data[$key]['projects'] = $row['hours'];
							break;
					}
				}
				else // eemployee exists, add stuff to it
				{
					$time_report_data[$key]['booked'] += $row['hours'];
					
					switch($post_type)
					{
						case 'sick' :
							$time_report_data[$key]['sick'] += $row['hours'];
							break;
							
						case 'holiday' :
							$time_report_data[$key]['holiday'] += $row['hours'];
							break;
							
						case 'internal' :
							$time_report_data[$key]['internal'] += $row['hours'];
							break;
							
						case 'projects' :
							$time_report_data[$key]['projects'] += $row['hours'];
							break;
					}
				}
			}
			
			$json['msg'] = 'Time Report Data successfully retrieved!';
			$json['time_report_data'] = $time_report_data;
		}
		
		return $json;
	}
	
	
///////////////	
// USERS MODULE
///////////////

	public function get_all_users($_parameters)
	{ 
	    # DECLARE
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$users = array();
        
		# PREPARE QUERY STATEMENTS
		$get_all_users = $this->pdo->prepare('SELECT * FROM _users_view');
					
		# GET TIME ENTRIES
		try
		{	
			$get_all_users->execute();
			$result = $get_all_users->fetchAll();
			$get_all_users->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing get_all_users: ' . $e->getMessage();
		}
		
		
		# RETURN RESULT
		if(!$json['error'])
		{
			foreach($result as $row)
			{
				$user = array(	'uid' => $row['uid'],
								'firstname' => $row['firstname'],
								'lastname' => $row['lastname'],
								'username' => $row['username'],
								'active' => $row['active'],
								'role_id' => $row['role_id'],
								'activity_id' => $row['activity_id'],
								'hours_contract' => $row['hours_contract'],
								);
				$users[] = $user;
			}
			
			
			$json['msg'] = 'Users successfully retrieved!';
			$json['users'] = $users;
		}
		
		return $json;
	}
	
	
	public function get_user_roles($_parameters)
	{ 
	    # DECLARE
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$user_roles = array();
        
		# PREPARE QUERY STATEMENTS
		$get_user_roles = $this->pdo->prepare('SELECT * FROM user_roles');
					
		# GET TIME ENTRIES
		try
		{	
			$get_user_roles->execute();
			$result = $get_user_roles->fetchAll();
			$get_user_roles->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing get_user_roles: ' . $e->getMessage();
		}
		
		
		# RETURN RESULT
		if(!$json['error'])
		{
			foreach($result as $row)
			{
				$role = array('role_id' => $row['id'],'role_name' => $row['name']);
				$user_roles[] = $role;
			}
			
			$json['msg'] = 'User Roles successfully retrieved!';
			$json['user_roles'] = $user_roles;
		}
		
		return $json;
	}
	
	
	public function update_user($_parameters)
	{ 
	    # DECLARE
	    $_column = $_parameters['type'];
	    $_value = $_parameters['val']; 
	    $_uid = $_parameters['uid'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		
		# PREPARE QUERY STATEMENTS
		$update_user = $this->pdo->prepare('UPDATE users SET '. $_column .' = :value WHERE uid = :uid');
					
		# GET TIME ENTRIES
		try
		{	
			$update_user->execute(array(':value' => $_value, ':uid' => $_uid));
			$update_user->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing update_user: ' . $e->getMessage();
		}
		
		# ON SUCCES
		if(!$json['error'])
		{
			$json['msg'] = 'Updated the user successfully';
		}
		
		# RETURN RESULT
		return $json;
	}
	
	
	public function check_user_uniqueness($_parameters)
	{ 
	    # DECLARE
	    $_column = $_parameters['type'];
	    $_value = $_parameters['val'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$unique = FALSE;
        
		# PREPARE QUERY STATEMENTS
		$check_uniqueness = $this->pdo->prepare('SELECT * FROM users WHERE '. $_column .' = :value');
					
		# GET TIME ENTRIES
		try
		{	
			$check_uniqueness->execute(array(':value' => $_value));
			$result = $check_uniqueness->fetchAll();
			$check_uniqueness->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing check_user_uniqueness: ' . $e->getMessage();
		}
		
		(count($result) >= 1) ? $unique = FALSE : $unique = TRUE;
		
		
		# RETURN RESULT
		if(!$json['error'])
		{
			$json['msg'] = 'Checked uniqueness successfully.';
			$json['unique'] = $unique;
		}
		
		return $json;
	}
	
	
	public function save_new_user($_parameters)
	{
		# DECLARE
		$_firstname = $_parameters['firstname'];
		$_lastname = $_parameters['lastname'];
		$_username = $_parameters['username'];
		$_email = $_parameters['email'];
		$_hours_contract = $_parameters['hours_contract'];
		$_bc_token = $_parameters['bc_token'];
		$_password = $_parameters['password'];
		$_role_id = $_parameters['role_id'];
		$_activity_id = $_parameters['activity_id'];
		$json['error'] = FALSE;
		$json['session'] = TRUE;
		$uid = 0;
		
		# PREPARE QUERY STATEMENTS
		$check_uniqueness = $this->pdo->prepare('SELECT * FROM users WHERE uid = :uid');
		$save_new_user = $this->pdo->prepare('INSERT INTO users (uid, firstname, lastname, username, email, hours_contract, bc_token, password, role_id, activity_id, active) 
											VALUES(:uid, :firstname, :lastname, :username, :email, :hours_contract, :bc_token, :password, :role_id, :activity_id, :active)');
		
		# CREATE UNIQUE UID					
		for($i = 0; $i < 1000; $i++)
		{
			$uid = $this->common->random_string('nozero', 8);
			
			try
			{	
				$check_uniqueness->execute(array(':uid' => $uid));
				$result = $check_uniqueness->fetchAll();
				$check_uniqueness->closeCursor();
			}
			catch(PDOException $e)
			{
				$json['error'] 	= TRUE;
				$json['msg'] 	= 'Error executing check_uniqueness: ' . $e->getMessage();
			}
			
			(count($result) >= 1) ? $unique = FALSE : $unique = TRUE;
			if($unique) break;
		}
											
											
		# SAVE USER
		try
		{	
			$save_new_user->execute(array(	':uid' => $uid,
											':firstname' => $_firstname,
											':lastname' => $_lastname,
											':username' => $_username,
											':email' => $_email,
											':hours_contract' => $_hours_contract,
											':bc_token' => $_bc_token,
											':password' => $_password,
											':role_id' => $_role_id,
											':activity_id' => $_activity_id,
											':active' => 1));
			$save_new_user->closeCursor();
		}
		catch(PDOException $e)
		{
			$json['error'] 	= TRUE;
			$json['msg'] 	= 'Error executing save_new_user: ' . $e->getMessage();
		}
		
		# ON SUCCES
		if(!$json['error'])
		{
			$json['msg'] = 'Saved the user successfully';
		}
		
		# RETURN RESULT
		return $json;
	}
	*/
}

?>