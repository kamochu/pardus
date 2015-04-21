<?php

/**
 * This function auto loads classes. Checks the core and model 
 * folders within the application
 *
 * @param $class name of the class to be loaded
 * @see http://php.net/manual/en/function.spl-autoload-register.php
 */
function my_class_autoloader($class) {
	$file_name= '../application/core/' . $class . '.php';
	
	if(!file_exists($file_name)){ 
		//check the core application folder
		$file_name= '../application/model/' . $class . '.php';
	}
	
	//include the file
	include $file_name;
    
}

//register the autoloader function  named 'my_class_autoloader'
spl_autoload_register('my_class_autoloader');
