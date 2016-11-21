<?php
namespace Api\Controller;

use MrMe\Web\Validate as WebValidate;
use MrMe\Web\Controller;

use MrMe\Database\MySql\MySqlCommand;
use MrMe\Database\MySql\MySqlConnection;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;



class User extends Controller
{
	public function login()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
    	header('Access-Control-Allow-Methods: POST');
    	header('Content-Type: application/json');

		// $log = new Logger('Tananut');
		// $log->pushHandler(new StreamHandler('app.log', Logger::DEBUG));

		$access_token = $this->request->body->access_token;
		// $log->info(print_r("Access_token : " .$access_token,true));

		if (!(empty($access_token))) // Facebook Login
		{
			// $log->info(print_r("Access_token : " .$access_token,true));

			$user_details = "https://graph.facebook.com/me?fields=id,name,picture&access_token=" .$access_token;

			// $log->info("Response ID : " .($user_details));

			$response 	  = file_get_contents($user_details);
     		$response 	  = json_decode($response, true);

			// $log->info("Response ID : " .($response['id']));
     	// 	$log->info("Response Name : " .($response['name']));
     	// 	$log->info("Response Picture_url : " .($response['picture']['data']['url']));

			$facebook_id  = $response['id'];
			$full_name 	  = explode(" ", $response['name']);
			$first_name   = $full_name[0];
			$last_name    = $full_name[1];
			$pic_url      = $response['picture']['data']['url'];

			$table        = "fb_users";
			$this->db->select($table);
			$this->db->where("facebook_id", "LIKE", $facebook_id);
			$result   	  = $this->db->executeReader();

			//var_dump($result);

			if (!($result)) // Never login with facebook
			{
				$table 	  = "users";
				$fields	  = ["first_name", "last_name", "picture_src"];
				$values   = ["'$first_name'", "'$last_name'", "'$pic_url'"];
				$this->db->insert($table, $fields, $values);
				$err 	  = $this->db->execute();

				$select   = "id";
				$this->db->select($table, $select);
				$this->db->where("first_name", "LIKE", "'$first_name'")
						 ->and("last_name", "LIKE", "'$last_name'")
						 ->and("picture_src", "LIKE", "'$pic_url'");
				$result    = $this->db->executeReader();
				//$result   = mysqli_fetch_assoc($model);

				$table 	  = "fb_users";
				$fields	  = ["user_id", "facebook_id", "access_token"];
				$values   = [$result['0']->id, $facebook_id, "'$access_token'"];

				$this->db->insert($table, $fields, $values);
				$err 	  = $this->db->execute();
		   	}
			else // Ever login with facebook
			{
				$fields	  = "access_token";
				$values   = "'$access_token'";
				$this->db->insert($table, $fields, $values);
				$err 	  = $this->db->execute();

				// $table    = "users u JOIN fb_users f ON (u.id = f.user_id)"; // Automatic change picture System
				// $select   = "u.picture_src";
				// $this->db->select($table, $select);
				// $this->db->where("facebook_id", "=", $facebook_id);
				// $response = $this->db->executeReader();
				// $result = mysqli_fetch_assoc($response);
				//
				// if ($result["picture_src"] != $pic_url)
				// {
				// 	$table  = "users";
				// 	$fields	= "picture_src";
				// 	$values = $pic_url;
				// 	$this->db->insert($table, $fields, $values);
				// 	$err    = $this->db->execute();
				// }
			}

			$table	  = "fb_users";
			$select   = "user_id";
			$this->db->select($table, $select);
			$this->db->where("facebook_id", "LIKE", "'$facebook_id'");
			$result   = $this->db->executeReader();

			$response = array();
			$response['success'] = true;
			$response['user_id'] = $result['0']->user_id;

			$this->response->success($response);
		}
		else // Common Login
		{
			$username = $this->request->body->username;
			$password = $this->request->body->password;

			//var_dump($this->request->body->username);

			WebValidate::isEmpty($username, "username cannot empty.");
			WebValidate::isEmpty($password, "password cannot empty.");

			$table    = "com_users";
			$field 	  = "user_id";
			$this->db->select($table, $field);
			$this->db->where("username", "LIKE", "'$username'")
					 ->and("password", "LIKE", "'$password'");
			$result   = $this->db->executeReader();
			//var_dump($result);
			if ($result)
			{
				$response = array();
				$response['success'] = true;
				$response['user_id'] = $result['0']->user_id;

				$this->response->success($response);
			}
	        else
			{
	        	$this->response->error(array("success"=>false,
	                                         "error"  =>"invalid username or password."));
			}
		}
	}

}
?>
