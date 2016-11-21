<?php 
namespace Api\Controller;

use MrMe\Web\Validate as WebValidate;
use MrMe\Web\Controller;

use MrMe\Database\MySql\MySqlCommand;
use MrMe\Database\MySql\MySqlConnection;


class Pickup extends Controller
{
	public function list()
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");  
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

		// // receive from BODY
		$offset = $this->request->body->offset;
		$size   = $this->request->body->size;
		$key    = $this->request->body->key;

		$table  = "`pickup_place`";
		$select = ["id", "hotel_name", "price", "X(location) AS lat", "Y(location) AS lng", "timestamp",
				   "address", "contact"];

		$clause = [];
		if (!empty($key))
		{
			array_push($clause, "WHERE");
			array_push($clause, "(hotel_name REGEXP  '^.*".$key."') ", "AND");
		}

		array_push($clause, "ORDER BY `timestamp`", "DESC");
		$this->db->select($table, $select, $clause, $offset, $size);
		$model = $this->db->executeReader();
	
		for ($i = 0; $i < count($model); $i++)
		{
			$table  = "`pickup_time`";
			$select = "`time`";
			$tmp_clause = "WHERE pickup_place_id = @pId";
			
			$this->db->select($table, $select, $tmp_clause);
			$this->db->bindParam("@pId", $model[$i]->id);
			$timeModel = $this->db->executeReader();
			
			$times = array();		
			foreach ($timeModel as $tm)
				array_push($times, $tm->time);

			$model[$i]->times = $times;
		}

		$this->db->select("`pickup_place`", "COUNT(id) AS count", $clause);
		$cntResult = $this->db->executeReader();

		$response = array();
		$response['success'] = true;
		$response['pickups'] = $model;
		$response['count'] = count($cntResult) > 0 ? $cntResult[0]->count : 0;
		$this->response->success($response);
	}

	public function add()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from body
        $hotel_name = $this->request->body->hotel_name;
        $remark 	= $this->request->body->remark;
        $price 		= $this->request->body->price;
        $lat        = $this->request->body->lat;
        $lng        = $this->request->body->lng;
        $address    = $this->request->body->address;
        $contact    = $this->request->body->contact;
        $times      = $this->request->body->times;
        $times      = json_decode($times);
        
        WebValidate::isEmpty ($hotel_name, "hotel_name cannot be null.");
        WebValidate::isNumber($price, "price must be number.");

		$table = "`pickup_place`";

		$field = ["hotel_name",
				  "remark", 
				  "price", 
				  "location",
				  "address",
				  "contact"];

		$value = ["@hotel_name", 
				  !empty($remark) ? "@remark" : null, 
				  "@price", 
				  $lat + $lng > 0 ? "POINT(@lat, @lng)" : null,
				  empty($address) ? null : "@address",
				  empty($contact) ? null : "@contact"];

		$this->db->insert($table, $field, $value);
		$this->db->bindParam("@hotel_name", $hotel_name);
		$this->db->bindParam("@remark", $remark);
		$this->db->bindParam("@price", $price);
		$this->db->bindParam("@lat", $lat);
		$this->db->bindParam("@lng", $lng);
		$this->db->bindParam("@address", $address);
		$this->db->bindParam("@contact", $contact);

		$err = $this->db->execute();

		if ($err)
			$this->response->error(array("success" => false,
										 "error" => $err));
		
		$insertId = $this->db->getLastInsertId();
		
		foreach ($times as $tm)
		{
			$this->db->insert('`pickup_time`', 
							  ["time", "pickup_place_id"], 
							  ["@time", "@pickup_place_id"]);
			$this->db->bindParam("@time", $tm);
			$this->db->bindParam("@pickup_place_id", $insertId);

			$err = $this->db->execute();

			if ($err)
			$this->response->error(array("success" => false,
										 "error" => $err));
		}

		$this->response->success(array("success" => true));
	}

	public function edit()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from params
        $id = $this->request->params->id;
        // receive from body
        $hotel_name = $this->request->body->hotel_name;
        $remark 	= $this->request->body->remark;
        $price 		= $this->request->body->price;
        $lat        = $this->request->body->lat;
        $lng        = $this->request->body->lng;
        $address    = $this->request->body->address;
        $contact    = $this->request->body->contact;
 		$times      = $this->request->body->times;
 	
        $times      = json_decode($times, false);
   
        WebValidate::isNumber($id, "id must be number.");

        $table = "`pickup_place`";
        $sets = [empty($hotel_name) ? null : "hotel_name = @hotel_name",
        		 empty($remark)     ? null : "remark = @remark",
        		 empty($price)      ? null : "price = @price",
        		 $lat + $lng == 0   ? null : "location = POINT($lat, $lng)",
        		 empty($address)    ? null : "address = @address",
        		 empty($contact)    ? null : "contact = @contact"];

        $clause = "WHERE id = @id";

        $this->db->update($table, $sets, $clause);
        $this->db->bindParam("@hotel_name", $hotel_name);
        $this->db->bindParam("@remark", $remark);
        $this->db->bindParam("@price", $price);
        $this->db->bindParam("@lat", $lat);
        $this->db->bindParam("@lng", $lng);
        $this->db->bindParam("@id", $id);
        $this->db->bindParam("@address", $address);
        $this->db->bindParam("@contact", $contact);

        $err = $this->db->execute();
        if ($err)
        	$this->response->error(array("success"=>false,
        								 "error"=>$err));
        if (count($times) > 0)
        {
        	$this->db->delete("pickup_time", "WHERE pickup_place_id = @pickup_place_id");
        	$this->db->bindParam("@pickup_place_id", $id);
        	$err = $this->db->execute();

        	if ($err)	$this->response->error(array("success" => false,
										 			 "error" => $err));
        }

        foreach ($times as $tm)
		{
			$this->db->insert('`pickup_time`', 
							  ["time", "pickup_place_id"], 
							  ["@time", "@pickup_place_id"]);
			$this->db->bindParam("@time", $tm);
			$this->db->bindParam("@pickup_place_id", $id);

			$err = $this->db->execute();

			if ($err)	$this->response->error(array("success" => false,
										 			 "error" => $err));
		}

        
        $this->response->success(array("success"=>true));
	}

	public function delete()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from params
        $id = $this->request->params->id;

        WebValidate::isNumber($id, "id must be number.");
        $this->db->delete('`pickup_place`', "WHERE id = @id");
        $this->db->bindParam("@id", $id);

        $err = $this->db->execute();
		if ($err)
        	$this->response->error(array("success"=>false,
        								 "error"=>$err));
        else
        	$this->response->success(array("success"=>true));

	}
}
?>