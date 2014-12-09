<?php


/**
	register class auto loader, which include  
	class file  whenever  a class is instantiated
**/
/*** class Loader ***/
function classLoader($class)
{
    $filename = ($class) . '.php';
    
    $filePaths = array("./protected/controllers/"
    					,"./protected/models/"
    					,"./framework/base/"
    					,"./framework/db/"
    					,"./framework/db/schema/"
    					,"./framework/db/schema/mysql/"
    					,"./framework/util/"
    					,"./framework/web/");

    foreach ($filePaths as $key => $path) {
    	$file = $path.$filename;
    	if (file_exists( $file ))
    	{
	        include $file;
	        return true;
	    }
	    
    }
    return false;
    
}

/*** register the loader functions ***/
spl_autoload_register('classLoader');
$route = isset($_GET['r'])?$_GET['r']:"simple/start";
$pos=strpos($route,'/');


Simple::$controllerName = ucfirst(strtolower(substr($route,0,$pos)));
Simple::$actionName = ucfirst(strtolower(substr($route,$pos+1)));

$controllerName = Simple::$controllerName."Controller";
$actionName = "action".Simple::$actionName;


$controller = new $controllerName();
$controller->$actionName();



class Simple
{
	private static $_app;


	public static $controllerName;
	public static $actionName;

	public static function app()
	{
		if( self::$_app === null )
			self::$_app = new CWebApplication();
		return self::$_app;
	}

	public static function createComponent($config)
	{
		if(is_string($config))
		{
			$type=$config;
			$config=array();
		}
		elseif(isset($config['class']))
		{
			$type=$config['class'];
			unset($config['class']);
		}
		else
			throw new CException(Yii::t('yii','Object configuration must be an array containing a "class" element.'));

		// if(!class_exists($type,false))
		// 	$type=Simple::import($type,true);

		if(($n=func_num_args())>1)
		{
			$args=func_get_args();
			if($n===2)
				$object=new $type($args[1]);
			elseif($n===3)
				$object=new $type($args[1],$args[2]);
			elseif($n===4)
				$object=new $type($args[1],$args[2],$args[3]);
			else
			{
				unset($args[0]);
				$class=new ReflectionClass($type);
				// Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
				// $object=$class->newInstanceArgs($args);
				$object=call_user_func_array(array($class,'newInstance'),$args);
			}
		}
		else
			$object=new $type;

		foreach($config as $key=>$value)
			$object->$key=$value;

		return $object;

	}
}
?>