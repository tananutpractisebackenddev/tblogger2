<?php
namespace Api\Controller;

use MrMe\Web\Controller;
use MrMe\Web\Validate as WebValidate;

class Package extends Controller 
{
    public function index()
    {
        $x = [];
        array_push($x, 5, 4, 5, 3, 2);
        print_r($x);
    }

    public function get()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: *');
        header('Content-Type: application/json');

        // receive from params
        $id = $this->request->params->id;

        WebValidate::isNumber($id, "id must be number.");
        $table = "`package`";
        $select = ["id", "image_path AS img_path", "name", "description AS `desc`",
                   "adult_price", "adult_price_opt", "child_price", "child_price_opt",
                   "infant_price", "infant_price_opt", "pickup_time AS pu_time",
                   "flag AS type", "province", "remark", "max_amount AS max_amt, `timestamp`" ];

        $clause = "WHERE id = @id";
        $this->db->select($table, $select, $clause);
        $this->db->bindParam("@id", $id);
        $model = $this->db->executeReader();

        $response = array();
        $response['success'] = true;
        $response['package'] = $model[0];
        $this->response->success($response);
    }

    public function list()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: *');
        header('Content-Type: application/json');

        // receive from params
        $province   = $this->request->params->province;

        // receive from body
        $offset = $this->request->body->offset;
        $size   = $this->request->body->size;
        $key    = $this->request->body->key;


        $table = "`package`";
        $select = ["id", "image_path AS img_path", "name", "description AS `desc`",
                   "adult_price", "adult_price_opt", "child_price", "child_price_opt",
                   "infant_price", "infant_price_opt", "pickup_time AS pu_time",
                   "flag AS type", "province","remark", "max_amount AS max_amt, `timestamp`" ];

        $clause = [];
        if (!empty($province) || 
            !empty($key))
        array_push($clause, "WHERE");

        //"flag = 3", "OR");
        if (!empty($province))
        array_push($clause, "province = $province");
        if (!empty($key))
        array_push($clause, "(name REGEXP  '^.*".$key."' OR description REGEXP '^.*".$key."') ", "AND");


        array_push($clause, "ORDER BY `timestamp`", "DESC");

        // if (!empty($type) OR !empty($key))
        // 	$clause = "WHERE ";
        // if (!empty($type))
        // {
        // 	$clause.= "flag = $type ";
        // 	if (!empty($key))
        // 		$clause.= "AND ";
        // }
        // if (!empty($key))
        // 	$clause.= "(name REGEXP  '^.*".$key."' OR description REGEXP '^.*".$key."') ";


        $this->db->select($table, $select, $clause, $offset, $size);
        $this->db->bindParam("@nkey", $key);
        $this->db->bindParam("@dkey", $key);
        $model = $this->db->executeReader();

        $this->db->select("`package`", "COUNT(id) AS count", $clause);
        $cntResult = $this->db->executeReader();

        $response = array();
        $response['success'] = true;
        $response['packages'] = $model;
        $response['count'] = count($cntResult) > 0 ? $cntResult[0]->count : 0;
        $this->response->success($response);
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
        $this->db->delete('`package`', "WHERE id = @id ");
        $this->db->bindParam("@id", $id);

        $err = $this->db->execute();
        if ($err)
            $this->response->error(array("success"=>false,
                                         "error"=>$err));
        else
            $this->response->success(array("success"=>true));
    }

    public function uploadImage()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *"); 


        $img_name = $_FILES['img']['name'];
        $img_tmp = $_FILES['img']['tmp_name'];

        $dir = "./temp/package/";
        if (!is_dir($dir))
        {
            mkdir($dir, 0777);   
        }

        $spt = explode('.', $img_name);
        $extension = strtolower($spt[sizeof($spt) - 1]);
        $newFilename = uniqid(date('mdy', time())).".".$extension;
        $img_path = $dir . $newFilename;

        $isMove = move_uploaded_file($img_tmp, $img_path);

        if ($isMove)
            $this->response->success(array("success" => true,
                                           "img_path" => $newFilename));
        else
            $this->response->error(array("success" => false,
                                         "error" => "Upload file failed."));

    }

    public function add()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // // receive from body
        $image_path      = $this->request->body->img_path;
        $name            = $this->request->body->name;
        $description     = $this->request->body->desc;
        $adult_price     = $this->request->body->adult_price;
        $adult_price_opt = $this->request->body->adult_price_opt;
        $child_price     = $this->request->body->child_price;
        $child_price_opt = $this->request->body->child_price;
        $infant_price    = $this->request->body->infant_price;
        $infant_price_opt= $this->request->body->infant_price_opt;
        $opt_price       = $this->request->body->opt_price;
        $type            = $this->request->body->type;
        $province        = $this->request->body->province;
        $remark 	     = $this->request->body->remark;
        $max_amount      = $this->request->body->max_amt;
        $pickup_time     = $this->request->body->pu_time;

        WebValidate::isEmpty ($name, "name cannot empty.");
        WebValidate::isNumber($type, "type must be number.");
        WebValidate::isNumber($province, "province must be number.");

        if (!empty($image_path))
        {
            $temp_dir = "./temp/package/".$image_path;
            $rsrc_dir = "./resource/package/".$image_path;

            $isMove = copy($temp_dir, $rsrc_dir);
        }

        if (!$isMove)
        {
           $this->response->error(array("success" => false,
                                        "error" => "Tempolary image file not exists."));
        }

        $table = "`package`";
        $field = ["image_path", "name", "description", "adult_price", "adult_price_opt", 
                  "child_price", "child_price_opt", "infant_price", "infant_price_opt", 
                  "flag", "province", "remark", "max_amount", "pickup_time"];

        $value = [empty($image_path)       ? null : "@image_path",
                  empty($name)             ? null : "@name",
                  empty($description)      ? null : "@description",
                  empty($adult_price)      ? null : "@adult_price",
                  empty($adult_price_opt)  ? null : "@adult_price_opt",
                  empty($child_price)      ? null : "@child_price",
                  empty($child_price_opt)  ? null : "@child_price_opt",
                  empty($infant_price)     ? null : "@infant_price",
                  empty($infant_price_opt) ? null : "@infant_price_opt",
                  empty($type)             ? null : "@type",
                  empty($province)         ? null : "@province",
                  empty($remark)           ? null : "@remark",
                  empty($max_amount)       ? null : "@max_amount",
                  empty($pickup_time)      ? null : "@pickup_time"];

        $this->db->insert($table, $field, $value);
        $this->db->bindParam("@image_path", $image_path);
        $this->db->bindParam("@name", $name);
        $this->db->bindParam("@description", $description);
        $this->db->bindParam("@adult_price", $adult_price);
        $this->db->bindParam("@adult_price_opt", $adult_price_opt);
        $this->db->bindParam("@child_price", $child_price);
        $this->db->bindParam("@child_price_opt", $child_price_opt);
        $this->db->bindParam("@infant_price", $infant_price);
        $this->db->bindParam("@infant_price_opt", $infant_price_opt);
        $this->db->bindParam("@type", $type);
        $this->db->bindParam("@province", $province);
        $this->db->bindParam("@remark", $remark);
        $this->db->bindParam("@max_amount", $max_amount);
        $this->db->bindParam("@pickup_time", $pickup_time);

        $err = $this->db->execute();

        if ($isMove && $err)
        {
          unlink($temp_dir);          
        }

        if ($err)
            $this->response->error(array("success"=>false,
                                         "error"=>$err));
        else
            $this->response->success(array("success"=>true));
    }

    public function edit()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from params
        $id              = $this->request->params->id;

        // receive from body
        $image_path      = $this->request->body->img_path;
        $name            = $this->request->body->name;
        $description     = $this->request->body->desc;
        $adult_price     = $this->request->body->adult_price;
        $adult_price_opt = $this->request->body->adult_price_opt;
        $child_price     = $this->request->body->child_price;
        $child_price_opt = $this->request->body->child_price;
        $infant_price    = $this->request->body->infant_price;
        $infant_price_opt= $this->request->body->infant_price_opt;
        $type            = $this->request->body->type;
        $province        = $this->request->body->province;
        $remark 	     = $this->request->body->remark;
        $max_amount      = $this->request->body->max_amt;
        $pickup_time     = $this->request->body->pu_time;

        WebValidate::isNumber($id, "id must be number.");

        if (!empty($image_path) && !file_exists("./resource/package/".$image_path))
        {
            $temp_dir = "./temp/package/".$image_path;
            $rsrc_dir = "./resource/package/".$image_path;

            $isMove = copy($temp_dir, $rsrc_dir);

            if ($isMove)
            {
                unlink($temp_dir);			    
            }
            else
            {
                $this->response->error(array("success" => false,
                "error" => "Tempolary image file not exists."));
            }
        }

        $table = "`package`";
        $sets  = [empty($image_path)       ? null : "image_path = @image_path",
                  empty($name)             ? null : "name = @name",
                  empty($description)      ? null : "description = @description",
                  empty($adult_price)      ? null : "adult_price = @adult_price",
                  empty($adult_price_opt)  ? null : "adult_price_opt = @adult_price_opt",
                  empty($child_price)      ? null : "child_price = @child_price",
                  empty($child_price_opt)  ? null : "child_price = @child_price_opt",
                  empty($infant_price)     ? null : "infant_price = @infant_price",
                  empty($infant_price_opt) ? null : "infant_price = @infant_price_opt",
                  empty($type)             ? null : "flag = @type",
                  empty($province)         ? null : "province = @province",
                  empty($remark)           ? null : "remark = @remark",
                  empty($max_amount)       ? null : "max_amount = @max_amount",
                  empty($pickup_time)      ? null : "pickup_time = @pickup_time"];

        $clause = "WHERE id = @id";

        $this->db->update($table, $sets, $clause);
        $this->db->bindParam("@image_path", $image_path);
        $this->db->bindParam("@name", $name);
        $this->db->bindParam("@description", $description);
        $this->db->bindParam("@adult_price", $adult_price);
        $this->db->bindParam("@adult_price_opt", $adult_price_opt);
        $this->db->bindParam("@child_price", $child_price);
        $this->db->bindParam("@child_price_opt", $child_price_opt);
        $this->db->bindParam("@infant_price", $infant_price);
        $this->db->bindParam("@infant_price_opt", $infant_price_opt);
        $this->db->bindParam("@type", $type);
        $this->db->bindParam("@province", $province);
        $this->db->bindParam("@remark", $remark);
        $this->db->bindParam("@max_amount", $max_amount);
        $this->db->bindParam("@pickup_time", $pickup_time);
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