<?php
/**
 * Config file for site
 * put in site root and include from any index file
 * This file should be called by the index of the site
 * many header confgurations can be set by action files to modify title etc
 */
// Set include path to controller
$site_path = realpath(dirname(__FILE__));
$include_path = dirname($site_path) . '/include/framework';
$include_path2 = $site_path;

set_include_path(get_include_path() 
	. PATH_SEPARATOR . $include_path
	);
require 'autoload.php';

/**
 * controller path setup
 * REQUIRED
 */
util_Path::add('site', $site_path);
util_Path::add('skin', $site_path);
util_Path::add('include', $include_path);
util_Path::add('view', $include_path . DIRECTORY_SEPARATOR . 'view');
util_Path::add('model', $include_path . DIRECTORY_SEPARATOR . 'model');
util_Path::add('action', $include_path2 . DIRECTORY_SEPARATOR . 'action');

/**
 * controller database setup
 * REQUIRED FOR MYSQL SITES
 */
Control::setVar('dsn', 'mysql://username:password@localhost/database_name');

/**
 * Site configurations
 */
// if root is not '/' set to '/path_name/'
Control::setVar('site_root', '/');
// only set to true if you know what you are doing with mod_rewirte
Control::setVar('mod_rewrite', false);

Control::addJS('jquery.autocomplete.js');
Control::addCSS('autocomplete.css');

/**
 * Controller header setup
 */
// title for website
Control::setVar('html_title', 'Untitled');
// adds css files call for every css file
Control::addCSS('common.css');
// adds javascript files call for every file
Control::addJS('res/js/common.js');

