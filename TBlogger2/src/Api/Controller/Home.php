<?php
namespace Api\Controller;

use MrMe\Web\Controller;
use MrMe\Database\MySql\MySqlConnection as MySqlConnection;
use MrMe\Database\MySql\MySqlCommand as MySqlCommand;

class Home extends Controller
{
	public function index()
	{
		//var_dump($this->params);
		//echo "What the fuck";
		$_CONFIG = array();
		$_CONFIG['DB']['HOST'] = "localhost";
		$_CONFIG['DB']['USERNAME'] = "ejs_db";
		$_CONFIG['DB']['PASSWORD'] = "vug0gvlfu[u";
		$_CONFIG['DB']['NAME'] = "ejs_db";
		$con= new MySqlConnection($_CONFIG);
		// $mysql->execute("SELECT * FROM `order` WHERE id = ?", 3);
		// while ($result = $mysql->fetch())
		// 	var_dump($result);


		$cmd = new MySqlCommand($con);
		$cmd->sql = "SELECT * FROM `order` WHERE id = @id AND package_id = @pkg_id";
		$cmd->bindParam("@id", 5);
		$cmd->bindParam("@pkg_id", 4);
		$err = $cmd->execute();
		var_dump($err);

		while ($x = $con->fetch())
			var_dump($x);
	}

	public function option()
	{
		$con = new MySqlConnection();
		$cmd = new MySqlCommand($con);

		$cmd->sql = "SELECT * FROM `package` WHERE id = @id";
		$cmd->bindParam();
	}
}
?>
