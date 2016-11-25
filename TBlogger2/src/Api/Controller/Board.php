<?php
namespace Api\Controller;

use MrMe\Web\Validate as WebValidate;
use MrMe\Web\Controller;

use MrMe\Database\MySql\MySqlConnection as MySqlConnection;
use MrMe\Database\MySql\MySqlCommand as MySqlCommand;

class Board extends Controller
{
    public function add()
	{
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

        $user_id = $this->request->body->user_id;
        $text    = $this->request->body->text;

        $table 	 = "boards";
        $fields	 = ["user_id", "message"];
        $values  = [$user_id, "'$text'"];
        $this->db->insert($table, $fields, $values);
        $err     = $this->db->execute();
        if ($err)
        {
            $this->response->error(array("status" => false,
                                         "error" => $err));
        }
        else
        {
            $lastId = $this->db->getLastInsertId();
            $table	  = "boards b JOIN users u ON b.user_id = u.id";
            $select   = "b.id, b.message, u.first_name, u.last_name, u.picture_src";
            $this->db->select($table, $select);
            $this->db->where("b.id", "=", $lastId);
            $model    = $this->db->executeReader();
			$this->response->success(array("status" => true , "message" => $model));
        }
    }

    public function edit()
    {
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

        $user_id  = $this->request->body->user_id;
        $board_id = $this->request->body->post_id;
        $text     = $this->request->body->text;

        $table    = "boards";
        $set      = ["message = @text"];
        $clause = "WHERE id = @board_id";
        $this->db->update($table, $set, $clause);
        $this->db->bindParam("@text", "$text");
        $this->db->bindParam("@board_id", $board_id);

        $err      = $this->db->execute();

        if ($err)
		{
			$this->response->error(array("status" => false,"error" => $err));
		}
		else
		{
            $table	  = "boards b JOIN users u ON b.user_id = u.id";
            $select   = "b.id, b.message, u.first_name, u.last_name, u.picture_src";
            $this->db->select($table, $select);
            $this->db->where("b.user_id", "=", $user_id);
            $model    = $this->db->executeReader();
			$this->response->success(array("status" => true , "message" => $model));
		}
    }

    public function delete()
    {
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

        $user_id  = $this->request->body->user_id;
        $board_id = $this->request->body->post_id;

        // $test = $this->request->params->id;

        $this->db->delete('`boards`', "WHERE id = @board_id");
        $this->db->bindParam("@board_id", $board_id);

        $err      = $this->db->execute();
        if ($err)
		{
			$this->response->error(array("status" => false,
		        				         "error" => $err));
		}
		else
        {
            $table	  = "boards b JOIN users u ON b.user_id = u.id";
            $select   = "b.id, b.message, u.first_name, u.last_name, u.picture_src";
            $this->db->select($table, $select);
            $this->db->where("b.user_id", "=", $user_id);
            $model    = $this->db->executeReader();
			$this->response->success(array("status" => true , "message" => $model));
        }
    }

    public function getlisttext()
    {
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

        $user_id = $this->request->body->user_id;

        $table   = "boards";
        $select  = "*";
        $this->db->select($table, $select);
        $this->db->where("user_id", "=", $user_id);
        $model   = $this->db->executeReader();

        if($model)
        {
            $table	  = "boards b JOIN users u ON b.user_id = u.id";
            $select   = "b.id, b.message, u.first_name, u.last_name, u.picture_src";
            $this->db->select($table, $select);
            $this->db->where("b.user_id", "=", $user_id);
            $model    = $this->db->executeReader();
        }
        else
        {
            $table  = "users";
            $select = "first_name, last_name, picture_src";
            $this->db->select($table, $select);
            $this->db->where("id", "=", $user_id);
            $model    = $this->db->executeReader();
        }

        $response = array();
        $response['success'] = true;
        $response['message'] = $model;
        $this->response->success($response);
    }

}
?>
