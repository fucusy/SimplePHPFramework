<?php
class SimpleController
{
	public function actionStart()
	{
		$name =  "simple";
		$this->render("start",array('name'=>$name));
		$link = mysql_connect('localhost', 'root', '');
		if (!$link) {
		    die('Could not connect: ' . mysql_error());
		}
		echo 'Connected successfully';
		mysql_close($link);
	}

	public function actionEnd()
	{
		echo "I am end action";
	}

	public function actionTestORM()
	{
		echo "ORM<br/>";
		
		$t = new Test();
		
		foreach ($t->findAll() as $key => $value) {
			print_r($value);
		}
	}

	public function render($file,$data)
	{
		foreach ($data as $key => $value) 
		{
			$$key = $value;
		}
		include("./protected/views/".strtolower(Simple::$controllerName)."/".$file.".php");
	}
}

?>