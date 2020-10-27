<?php

/**
 * Framework Initialization File
 * 
 * Include this file to initialize the framework
 * 
 * @author Ray Cleveland
 */

if (!defined('FRAMEWORK_LOADED')) {

    define('FRAMEWORK_LOADED', true);

    // set the framework path to the include paths
    $framework_path = dirname(__FILE__);
    if(strpos(get_include_path(), $framework_path)){
        set_include_path(get_include_path() . PATH_SEPARATOR . $framework_path);	
    }

    // include autoload
    require $framework_path . DIRECTORY_SEPARATOR . 'autoload.php';

    util_Path::add('include', $framework_path);

}
