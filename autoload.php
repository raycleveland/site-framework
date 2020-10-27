<?php

/**
 * autoload.php
 * this file auto loads classes
 * @author Ray Cleveland
 * @copyright 2009
 */

if(!function_exists('framework_autoload')){

require dirname(__FILE__) . DIRECTORY_SEPARATOR .  'util' . DIRECTORY_SEPARATOR . 'util_Path.class.php';

function framework_autoload($class_name) {
	$found = false;
	$file = $class_name;
	$pieces = explode('_', $class_name);
	if(count($pieces) > 1)
	{
		array_pop($pieces);
		$file = implode(DIRECTORY_SEPARATOR, $pieces) . DIRECTORY_SEPARATOR . $class_name;
	}
	
	// get all the paths to test
	$paths = explode(PATH_SEPARATOR, get_include_path());
	$paths = array_merge($paths, util_Path::getPaths());	
	
	$filename = $file . '.class.php';
	foreach($paths as $path){
		$test = $path . DIRECTORY_SEPARATOR . $filename;
		if(file_exists($test)){
			$found = true;
			$filename = $test;
			break;
		}
	}
	
	if(!$found){
		foreach($paths as $path){
			$files	= util_Path::getFiles($path, util_Path::TYPE_SYSTEM);
			foreach($files as $file){
				if(strtolower(basename($file)) == strtolower($filename))
				{
					$found = true;
					$filename = $file;
					break;
				}
			}
		}
	}
	
	if(!@include $filename) {
		throw new Exception("Failed to require {$filename}");
	}
}
}
spl_autoload_register('framework_autoload');

?>
