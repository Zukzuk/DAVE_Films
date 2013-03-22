<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/* Author: Dave Timmerman
 * Description: Data model class
 */
class Data_model extends CI_Model
{
	function __construct()
	{
		parent::__construct();

		$this -> pdo = $this -> db;
		$this -> pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

///////////////
// GLOBAL
///////////////

	public function get_user_privileges($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		$response['msg'] = 'Setting user privileges to ' . $this -> session -> userdata('firstname') . ' ' . $this -> session -> userdata('lastname');
		$response['login_status'] = $this -> session -> userdata('login_status');
		$response['firstname'] = $this -> session -> userdata('firstname');
		$response['lastname'] = $this -> session -> userdata('lastname');
		$response['role_id'] = $this -> session -> userdata('role_id');
		$response['uid'] = $this -> session -> userdata('uid');
		return $response;
	}

///////////////
// PROJECT_REPORTS MODULE
///////////////

	public function get_all_films($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;

		$directories = glob('F:/*', GLOB_ONLYDIR);
		$count = 0;

		foreach ($directories as $key => $value)
		{
			$directory = $directories[$key];
			$dir_name = str_replace("F:/", "", $directory);

			if ($dir_name != "System Volume Information" && 
				$dir_name != "FFOutput" && 
				$dir_name != "HandBrake Output" && 
				substr($dir_name, 1) != "RECYCLE.BIN")
			{
				$payload[$count]['name'] = "";
				$payload[$count]['year'] = "";
				$payload[$count]['data']['series'] = FALSE;
				$payload[$count]['data']['types'] = FALSE;
				$payload[$count]['data']['directory'] = rawurlencode($dir_name);
				// space to %20
				$payload[$count]['data']['filename'] = "";
				$payload[$count]['data']['filetype'] = "";
				$payload[$count]['data']['subtitle'] = FALSE;
				$payload[$count]['data']['poster'] = FALSE;
				$payload[$count]['data']['count'] = 0;

				// get name
				$payload[$count]['name'] = $this -> helper_get_name_from_dirname($dir_name);

				// get year
				$payload[$count]['year'] = $this -> helper_get_year_from_dirname($dir_name);

				// get series (Terminator -1- The Terminator)
				$result = $this -> helper_get_series_and_name_from_currentname($payload[$count]['name']);
				$payload[$count]['name'] = $result['name'];
				$payload[$count]['data']['series'] = $result['series'];

				// get type(s)
				// [lowres], [screener], [collection], [remake], [nosub], [nodub]
				$payload[$count]['data']['types'] = $this -> helper_get_types_from_dirname($dir_name);

				// get contents from directory
				$contents = scandir($directory);
				foreach ($contents as $_key => $content)
				{
					if ($content === '.' or $content === '..') continue;

					$result = $this -> helper_get_film_by_filetype($content);
					if ($result)
					{
						$payload[$count]['data']['filename'] = $result['filename'];
						$payload[$count]['data']['filetype'] = $result['filetype'];
						$payload[$count]['data']['count'] ++;
					}

					$result = $this -> helper_get_subtitle_by_filetype($content);
					if ($result)
					{
						$payload[$count]['data']['subtitle'] = $result['subtitle'];
					}

					$result = $this -> helper_get_poster_by_filetype($content);
					if ($result)
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
		$directory = 'F:/' . rawurldecode($_parameters['directory']);
		$dir_name = rawurldecode($_parameters['directory']);

		$payload['name'] = "";
		$payload['year'] = "";
		$payload['data']['series'] = FALSE;

		if (is_dir($directory))
		{
			$contents = scandir($directory);

			// get name
			$payload['name'] = $this -> helper_get_name_from_dirname($dir_name);

			// get year
			$payload['year'] = $this -> helper_get_year_from_dirname($dir_name);

			// get series (Cowboy Bebop -1- Cowboy Bebop Complete Sessions)
			$result = $this -> helper_get_series_and_name_from_currentname($payload['name']);
			$payload['name'] = $result['name'];
			$payload['data']['series'] = $result['series'];

			$entries = array();
			$count = 0;

			if (!empty($contents))
			{
				// entries in root, without subfolders
				$entries = $this -> helper_get_films_from_directory($contents);

				// normalize collection array
				$entries = array_values($entries);
				$payload['data']['entries'] = $entries;

				// DEPRECATED! NO MORE SUBDIRS!
				/**
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
		$content = '' . '<html><head></head><body>' . '<video controls autoplay poster="' . $_parameters['poster'] . '" name="media">' . '<source src="' . $_parameters['film'] . '" type="video/mp4">' . '</video>' . '</body></html>';
		// Write the contents to the file,
		// LOCK_EX flag to prevent anyone else writing to the file at the same time
		file_put_contents($file, $content, LOCK_EX);

		$response['msg'] = 'Successfully written php for iframe insertion.';
		$response['player_uri'] = $file;

		return $response;
	}

	public function synchronize_films($_parameters)
	{
		$response['error'] = FALSE;
		$response['session'] = TRUE;
		
		$response['synced'] = FALSE;
		$response['films'] = array();
		
		$do_sync = TRUE;
		$films = $_parameters['films'];
		$local_film_names = array();
		
		// Prepare query statement
		$check_films = $this->pdo->prepare('SELECT * FROM films');		
		$check_film = $this->pdo->prepare('SELECT * FROM films WHERE name=:name AND year=:year');
		$add_film = $this->pdo->prepare('INSERT INTO films (name,year) VALUES(:name, :year)');
		$update_film = $this->pdo->prepare('UPDATE films SET active=:active, exists=:exists WHERE name=:name AND year=:year');		
		
		foreach ($films as $key => $film) 
		{
			// Check if film needs to be synced			
			try {	
				$check_film->execute(array(':name' => $film['name'], ':year' => $film['year']));
				$do_sync = $check_film->fetchAll();
				$check_film->closeCursor();
			}
			catch(PDOException $e) {
				array_push( $response['films'], array('error'=>TRUE, 'active'=>FALSE, 'msg'=>'Error executing check_sync : '.$e->getMessage(), 'name'=>$film['name'], 'year' => $film['year']) );
			}
			
			// Double sync target found, not good!
			if(count($do_sync) > 1) {
				array_push( $response['films'], array('error'=>TRUE, 'active'=>FALSE, 'msg'=>'Double sync target', 'name'=>$film['name'], 'year' => $film['year']) );
				$do_sync = FALSE;
			}
			// Year is not a number, not good!
			if(!intval($film['year'])) {
				array_push( $response['films'], array('error'=>TRUE, 'active'=>FALSE, 'msg'=>'Year is not a number', 'name'=>$film['name'], 'year' => $film['year']) );
				$do_sync = FALSE;
			}
			
			if($do_sync !== FALSE)
			{
				if(!count($do_sync))
				{
					// add film to db
					try {	
						$add_film->execute(array(':name' => $film['name'], ':year' => $film['year']));
						$add_film->closeCursor();
						array_push( $response['films'], array('error'=>FALSE, 'active'=>TRUE, 'msg'=>'Film added', 'name'=>$film['name'], 'year' => $film['year']) );;
						$response['synced'] = TRUE;
					}
					catch(PDOException $e) {
						array_push( $response['films'], array('error'=>TRUE, 'active'=>FALSE, 'msg'=>'Error executing add_film : '.$e->getMessage(), 'name'=>$film['name'], 'year' => $film['year']) );
					}				
				}
				// activate old film in db
				else if(count($do_sync) == 1 && !$do_sync[0]['active'])
				{
					try {	
						$update_film->execute(array(':active' => 1, ':name' => $film['name'], ':year' => $film['year']));
						$update_film->closeCursor();
						array_push( $response['films'], array('error'=>FALSE, 'active'=>TRUE, 'msg'=>'Film activated', 'name'=>$film['name'], 'year' => $film['year']) );
						$response['synced'] = TRUE;
					}
					catch(PDOException $e) {
						array_push( $response['films'], array('error'=>TRUE, 'active'=>FALSE, 'msg'=>'Error executing activation with update_film : '.$e->getMessage(), 'name'=>$film['name'], 'year' => $film['year']) );
					}			
				}
			}

		}
	
		if($response['synced'])
			$response['msg'] = 'Some films were synced.';
		else 
			$response['msg'] = 'No films to sync.';	
		
		return $response;
	}

/**
 *
		array_push($local_film_names, $film['name'].$film['year'] );

		$check_films->execute(array());
		$db_films = $check_films->fetchAll();
		$check_films->closeCursor();
		$db_film_names = array();
		foreach ($db_films as $key => $db_film) {			
			array_push($db_film_names, $db_film['name'].$db_film['year'] );
		}
		
		// deactivate film in db
		$deactivate_films = array_diff($db_film_names, $local_film_names);
		
		die(print_r($deactivate_films));
		
		foreach ($deactivate_films as $key => $db_film)
		{
			try {
				$update_film->execute(array(':active' => 0, ':name' => substr($db_film, 0, -4), ':year' => substr($db_film, -4) ));
				$update_film->closeCursor();
				array_push( $response['films'], array('error'=>FALSE, 'active'=>FALSE, 'msg'=>'Film deactivated', 'name'=>substr($db_film, 0, -4)) );
				$response['synced'] = TRUE;
			}
			catch(PDOException $e) {
				array_push( $response['films'], array('error'=>TRUE, 'msg'=>'Error executing deactivation with update_film : '.$e->getMessage(), 'name'=>substr($db_film, 0, -4)) );
			}
		}
 * 
 */

	
///////////////
// PRIVATE METHODS
///////////////
	

	private function helper_get_name_from_dirname($dir_name)
	{
		if (strpos($dir_name, '[') === FALSE)
			$name = substr($dir_name, 0, -5);
		else
			$name = substr(substr($dir_name, 0, strpos($dir_name, '[') - 1), 0, -5);
		return $name;
	}

	private function helper_get_year_from_dirname($dir_name)
	{
		if (strpos($dir_name, '[') === FALSE)
			$year = substr($dir_name, -4);
		else
			$year = substr(substr($dir_name, 0, strpos($dir_name, '[') - 1), -4);
		return $year;
	}

	private function helper_get_series_and_name_from_currentname($currentname)
	{
		$result = FALSE;
		if (strpos($currentname, '-') !== FALSE)
		{
			$names = explode('-', $currentname);
			if (substr($names[2], 1) == substr($names[0], 0, -1))
				$result['name'] = substr($names[2], 1);
			else
				$result['name'] = substr($names[0], 0, -1) . " - " . substr($names[2], 1);
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
		if (strpos($dir_name, '[') !== FALSE)
		{
			$types = explode('[', substr($dir_name, strpos($dir_name, '[') + 1));
			if (count($types))
			{
				$result = array();
				foreach ($types as $key => $type)
				{
					array_push($result, substr($type, 0, -1));
				}
			}
		}
		return $result;
	}

	private function helper_get_film_by_filetype($file)
	{
		$result = FALSE;
		$filetype = strtolower(substr($file, -3));
		if ($filetype == 'mp4' || 
			$filetype == 'mkv' || 
			$filetype == 'm4v' || 
			$filetype == 'mov' || 
			$filetype == 'avi' || 
			$filetype == 'mpg' || 
			$filetype == 'wmv')
		{
			$result['filename'] = $file;
			$result['filetype'] = $filetype;
			$result['name'] = substr($file, 0, -4);
		}
		$filetype = strtolower(substr($file, -4));
		if ($filetype == 'xvid' || $filetype == 'm2ts')
		{
			$result = array();
			$result['filename'] = $file;
			$result['filetype'] = $filetype;
			$result['name'] = substr($file, 0, -5);
		}
		return $result;
	}

	private function helper_get_subtitle_by_filetype($file)
	{
		$result = FALSE;
		$filetype = strtolower(substr($file, -3));
		if ($filetype == 'srt' || $filetype == 'sub')
		{
			$result['subtitle'] = $file;
		}
		return $result;
	}

	private function helper_get_poster_by_filetype($file)
	{
		$result = FALSE;
		$filetype = strtolower(substr($file, -3));
		if ($filetype == 'jpg')
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
			if ($content === '.' or $content === '..') continue;

			// TODO: check for real dirs! glob() and is_dir somehow don't detect subdirs...
			$check1 = strtolower(substr($content, -3, 1));
			$check2 = strtolower(substr($content, -4, 1));
			if ($check1 != '.' && $check2 != '.')
			{
				if (!$result) $result = array();
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
			if ($content === '.' or $content === '..') continue;

			$_result = $this -> helper_get_film_by_filetype($content);
			if ($_result)
			{
				if (!$result) $result = array();
				array_push($result, $_result);
			}
		}
		return $result;
	}
	
	private function array_recursive_diff($aArray1, $aArray2) 
	{
		$aReturn = array();	
		foreach ($aArray1 as $mKey => $mValue) {
			if (array_key_exists($mKey, $aArray2)) {
				if (is_array($mValue)) {
					$aRecursiveDiff = $this->array_recursive_diff($mValue, $aArray2[$mKey]);
					if (count($aRecursiveDiff)) {
						 $aReturn[$mKey] = $aRecursiveDiff; 
					}
				} 
				else {
					if ($mValue != $aArray2[$mKey]) {
						$aReturn[$mKey] = $mValue;
	        		}
	      		}
	    	} 
	    	else {
	      		$aReturn[$mKey] = $mValue;
	    	}
	  }
	  return $aReturn;
	}
	
}
?>
