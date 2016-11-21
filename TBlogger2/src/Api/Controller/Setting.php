<?php
namespace Api\Controller;

use MrMe\Web\Controller;
use MrMe\Web\Validate as WebValidate;

class Setting extends Controller
{
	public function add()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from body 
        $start_date = $this->request->body->start_date;
        $end_date   = $this->request->body->end_date;
        $remark     = $this->request->body->remark;

        WebValidate::isEmpty($start_date, "start_date cannot empty.");

        $table = "`unavailable_booking`";
        $fields = ["start_date", "end_date", "remark"];
        $value = [empty($start_date) ? null : "@start_date",
         		  empty($end_date)   ? null : "@end_date",
         		  empty($remark)     ? null : "@remark"];

        $this->db->insert($table, $fields, $value);
        $this->db->bindParam("@remark", $remark);
        $this->db->bindParam("@start_date", $start_date);
        $this->db->bindParam("@end_date", $end_date);
        
        $err = $this->db->execute();

        if ($err)
			$this->response->error(array("success" => false,
										 "error" => $err));
		else
			$this->response->success(array("success" => true));
	}

	public function delete()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from body
        $start_date = $this->request->body->start_date;
        $end_date   = $this->request->body->end_date;

        $this->db->delete('`unavailable_booking`', "WHERE start_date >= '$start_date' AND end_date <= '$end_date' ");
        

        $err = $this->db->execute();
		if ($err)
        	$this->response->error(array("success"=>false,
        								 "error"=>$err));
        else
        	$this->response->success(array("success"=>true));

	}

	public function list()
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: POST");  
		header('Access-Control-Allow-Methods: *');
		header('Content-Type: application/json');

		// // receive from BODY
		$offset = $this->request->body->offset;
		$size   = $this->request->body->size;

		$pkg_id = $this->request->body->pkg_id;

		$table  = "`unavailable_booking`";
		$select = ["id", "start_date", "end_date", "remark", "timestamp"];
		$clause = [];
		array_push($clause, "ORDER BY `timestamp`", "DESC");
		$this->db->select($table, $select, $clause, $offset, $size);
		$model = $this->db->executeReader();

		$this->db->select("`unavailable_booking`", "COUNT(id) AS count");
		$cntResult = $this->db->executeReader();

		if ($pkg_id)
		{
			$this->db->select("package", "flag AS type", "WHERE id = $pkg_id");
			$packageModel = $this->db->executeReader();
			if (count($packageModel)  > 0)
			{
				$pkg_type = $packageModel[0]->type;	
				date_default_timezone_set("Asia/Bangkok");
				
				// 1 = Haft Day, 2 = Haft Afternoon
				//echo $pkg_type;
				switch ($pkg_type) {
					case 1:
						$curH = (int)date("H", time());
						if ($curH > 21) // More than 9 PM cannot book haftday tomorrow
						{
							date("Y-m-d", time() + 86400);

							$disableDay = date("Y-m-d", time() + 86400); // tomorrow (plus 1 day)
							array_push($model, array("start_date" => $disableDay,
								  			         "end_date"   => $disableDay));
							$cntResult[0]->count++;
						}
						break;
					
					case 2:
						$curH = (int)date("H", time());
						if ($curH >= 10) // More than 10 AM cannot book !
						{
							$disableDay = date("Y-m-d"); // today
							array_push($model, array("start_date" => $disableDay,
								  			         "end_date"   => $disableDay));
							$cntResult[0]->count++;
						}
						break;
				}
			}
			
		}
		

		$response = array();
		$response['success'] = true;
		$response['disable'] = $model;
		$response['count'] = count($cntResult) > 0 ? $cntResult[0]->count : 0;
		$this->response->success($response);
	}
}
?>