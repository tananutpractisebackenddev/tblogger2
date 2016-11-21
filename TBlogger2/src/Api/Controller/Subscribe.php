<?php
namespace Api\Controller;

use MrMe\Web\Controller as Controller;
use MrMe\Web\Validate   as WebValidate;
use Mailgun\Mailgun;
use PHPMailer\PHPMailer;

class Subscribe extends Controller
{
	public function list()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        // receive from body
        $offset = $this->request->body->offset;
        $size   = $this->request->body->size;
        $key    = $this->request->body->key;


        $table = "`subscribe`";
        $select = ["*"];
        $clause = [];

        if (!empty($key))
        	array_push($clause, "WHERE", "(`email` REGEXP  '^.*".$key."' ) ", "AND");

        array_push($clause, "ORDER BY `timestamp`", "DESC");
        $this->db->select($table, $select, $clause, $offset, $size);
        $model = $this->db->executeReader();

        $this->db->select("`subscribe`", "COUNT(id) AS count", $clause);
        $cntResult = $this->db->executeReader();

        $response = array();
        $response['success'] = true;
        $response['subscribes'] = $model;
        $response['count'] = count($cntResult) > 0 ? $cntResult[0]->count : 0;
        $this->response->success($response);
	}

	public function add()
	{
		header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');

        //receive from body
        $email = $this->request->body->email;

        WebValidate::isEmpty($email, "email cannot empty.");
        $table  = "subscribe";
        $fields = ["email"];
        $values = ["@email"];

        $this->db->insert($table, $fields, $values);
        $this->db->bindParam("@email", $email);

        $err = $this->db->execute();

        if ($err)
            $this->response->error(array("success"=>false,
                                         "error"=>$err));
        else
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
        $this->db->delete('`subscribe`', "WHERE id = @id ");
        $this->db->bindParam("@id", $id);

        $err = $this->db->execute();
        if ($err)
            $this->response->error(array("success"=>false,
                                         "error"=>$err));
        else
            $this->response->success(array("success"=>true));
	}

	public function sendMail()
	{
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");  
        header('Access-Control-Allow-Methods: POST');
        header('Content-Type: application/json');
        $CONFIG = $this->_CONFIG;
        if (empty($CONFIG)) 
            $this->response->error(array("success"=>false,
                                         "error"=>"No email information setting."));

        // receive from body
        $receiver = $this->request->body->recvr;
        $to       = $this->request->body->to;
        $title    = $this->request->body->title;
        $message  = $this->request->body->msg;
        $attach   = $this->request->body->attach;

        $receiver = json_decode($receiver); // receive content as array string need to decode to json format
        $attach   = json_decode($attach);
        WebValidate::isEmpty($receiver, "recvr cannot empty.");
        WebValidate::isEmpty($title, "title cannot empty.");
        WebValidate::isEmpty($message, "msg cannot empty.");

       
        $table = "subscribe";
        $select = ["email"];
        $clause = ["WHERE"];
        foreach ($receiver as $r) 
        {
            array_push($clause, "id = $r", "OR");
        }
        $this->db->select($table, $select, $clause);
        $emailModel = $this->db->executeReader();

        $temp_path = "./temp/file/";

        $mail = new \PHPMailer();
        $mail->isSMTP();    
        //$mail->SMTPDebug = 2;
        $mail->SMTPAuth   = $CONFIG['SMTP']['AUTH'];          // Set mailer to use SMTP
        $mail->Host       = $CONFIG['SMTP']['HOST'];          // Specify main backup                               
        $mail->Username   = $CONFIG['SMTP']['USERNAME'];      // SMTP username
        $mail->Password   = $CONFIG['SMTP']['PASSWORD'];      // SMTP password
        $mail->SMTPSecure = $CONFIG['SMTP']['SECURE'];        // Enable encryption, only 'tls' is accepted
        $mail->IsHTML(true);
        $mail->From = 'supanut.pgs@gmail.com';
        $mail->FromName = 'Elephant Jungle Sanctury';

        if ($to)
        {
            $mail->addAddress($to);
        }
        else
        {
            foreach ($emailModel as $m)
                $mail->addAddress($m->email);                     // Add a recipient
        }
       

        foreach ($attach as $a) 
        {
            $file_path = $temp_path.$a;
            if (file_exists($file_path))
            {
                $mail->AddAttachment($file_path);
            }    
        }
        $mail->WordWrap = 50;                                 // Set word wrap to 50 characters

        $mail->Subject = $title;
        $mail->Body    = $message;

        if(!$mail->send()) 
        {
            $this->response->error(array("success" => false,
                                          "error" => $mail->ErrorInfo));
        }
        else 
        {
             $this->response->success(array("success" => true));
        }
	}

	public function uploadFile()
	{
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *"); 


        $files = array();
        foreach ($_FILES as $file)
        {
            $file_name = $file['name'];
            $file_tmp  = $file['tmp_name'];

            $dir = "./temp/file/";
            if (!is_dir($dir))
            {
                mkdir($dir, 0777);   
            }

            $spt = explode('.', $file_name);
            $extension = strtolower($spt[sizeof($spt) - 1]);
            $newFilename = uniqid(date('mdy', time())).".".$extension;
            $file_path = $dir . $newFilename;

            $isMove = move_uploaded_file($file_tmp, $file_path);

            if (!$isMove)
                $this->response->error(array("success" => false,
                                             "error" => "Upload file failed."));

            array_push($files, $newFilename);
             
        }
        $this->response->success(array("success" => true,
                                       "img_paths" => $files));
           
	}
}
?>