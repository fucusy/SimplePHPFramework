<?php
class SimpleController
{
	public function actionStart()
	{
		$name =  "simple";
		$this->render("start",array('name'=>$name));
	}

	public function actionEnd()
	{
		echo "I am end action";
	}

	public function render($file,$data)
	{
		foreach ($data as $key => $value) 
		{
			$$key = $value;
		}
		include("views/".strtolower(Simple::$controllerName)."/".$file.".php");
	}
}

?>