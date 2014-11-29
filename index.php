<?php
	/**
		register class auto loader, which include  
		class file  whenever  a class is instantiated
	**/
    /*** class Loader ***/
    function classLoader($class)
    {
        $filename = ($class) . '.php';
        $file ='./controllers/' . $filename;
        if (!file_exists($file))
        {
            return false;
        }
        include $file;
    }

    /*** register the loader functions ***/
    spl_autoload_register('classLoader');

    
    $simple = "./framework/simple.php";
    include $simple;

	$route = isset($_GET['r'])?$_GET['r']:"simple/start";
	$pos=strpos($route,'/');


    Simple::$controllerName = ucfirst(strtolower(substr($route,0,$pos)));
    Simple::$actionName = ucfirst(strtolower(substr($route,$pos+1)));

    $controllerName = Simple::$controllerName."Controller";
    $actionName = "action".Simple::$actionName;





	$controller = new $controllerName();
	$controller->$actionName();




?>