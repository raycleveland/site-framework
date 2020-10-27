<?php

/**
 * Path Utility Class
 * 
 * For managing paths setting and retrieving
 */ 

Class util_Path
{
	const TYPE_URL = 1;
	const TYPE_SYSTEM = 2;
	const TYPE_FILENAME = 3;
	const TYPE_RELATIVE = 4;
	
	private static $instance = null;
	public static $paths = array();
	public static $path_overrides = array();
	private static $root = null;
	
	/**
	 * Initializes this utility
	 * Use on any method that needs the root path
	 */
	private static function init()
	{
		if(empty(self::$root))
		{
			self::$root = dirname($_SERVER['SCRIPT_NAME']);
			self::$paths['root'] = self::$root;
		}
	}
	
	public function getRoot()
	{
		self::init();
		return self::$root;
	}
	
	/**
	 * util_Path::setRoot()
	 * 
	 * @param mixed $root
	 * @return void
	 */
	public function setRoot($root)
	{
		self::$root = $root;
	}
	
	/**
	 * util_Path::GetInstance()
	 * 
	 * @return
	 */
	public static function getInstance()
	{
		if(empty(self::$instance))
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * util_Path::add()
	 * 
	 * @param String $path_name The name of the path to store
	 * @param String $path the path to store relative to file
	 * @return
	 */
	public static function add($path_name, $path, $path2 = null)
	{
		// join path 2 if not empty
		if(!empty($path2)){
			$path = self::join($path, $path2);
		}
		$path_name = strtolower($path_name);
		if(!$dir = realpath($path)){
			throw new Exception(sprintf('The following path could not be resolved:' . "\n"
				. ' name: "%s" path: "%s"' . "\n", $path_name, $path));
		}
		self::$paths[$path_name] = is_link($path)? $path : $dir;
		return $dir; // for debugging	
	}
	
	/**
	 * util_Path::addOverride()
	 * 
	 * @param mixed $path_name
	 * @param mixed $path
	 * @return void
	 */
	public static function addOverride($path_name, $path, $path2 = null)
	{
		// join path 2 if not empty
		if(!empty($path2)){
			$path = self::join($path, $path2);
		}
		$path_name = strtolower($path_name);
		if(!$dir = realpath($path)){
			throw new Exception(sprintf('The following path could not be resolved:' . "\n"
				. ' name: "%s" path: "%s"' . "\n", $path_name, $path));
		}
		self::$path_overrides[$path_name] = $dir;
		return $dir; // for debugging
	}
	
	/**
	 * util_Path::get()
	 * 
	 * @param String $path_name The name of the stored path
	 * @param Integer $type [util_Path::TYPE_URL|util_Path::TYPE_SYSTEM]
	 * @return
	 */
	public static function get($path_name, $type = util_Path::TYPE_SYSTEM)
	{
		self::init();
		$util = self::getInstance();
		if(!isset(self::$paths[$path_name])) return null;
		
		switch($type)
		{
			case self::TYPE_URL:
				return self::makeUrl(self::$paths[$path_name]);
			case self::TYPE_SYSTEM:
				return self::$paths[$path_name];
		}
	}
	
		/**
	 * util_Path::get()
	 * 
	 * @param String $path_name The name of the stored path
	 * @param Integer $type [util_Path::TYPE_URL|util_Path::TYPE_SYSTEM]
	 * @return
	 */
	public static function getPathOverride($path_name, $type = util_Path::TYPE_URL)
	{
		self::init();
		$util = self::getInstance();
		if(!isset(self::$path_overrides[$path_name])) return null;
		
		switch($type)
		{
			case self::TYPE_URL:
				return self::makeUrl(self::$path_overrides[$path_name]);
			case self::TYPE_SYSTEM:
				return self::$path_overrides[$path_name];
		}
	}
	
	/**
	 * util_Path::testPath()
	 * 
	 * @param mixed $path
	 * @return
	 */
 	//TODO get working on linux
	public static function testPath($path)
	{	
		// do this for file paths
		$filename = basename($path);
		if(strstr($filename, '.')){
			$file = realpath(dirname($path)) . DIRECTORY_SEPARATOR . $filename;
			return is_file($file);	
		}
		
		// for directory paths
		return (realpath($path))? true : false;
	}
	
	/**
	 * util_Path::join()
	 * 
	 * @param string $path1
	 * @param string $path2
	 * @param string $path3
	 * @return String Joined Paths
	 */
	public static function join($path1, $path2, $systemize = true)
	{
		if(empty($path1)) return $path2;
        if($path1 == '/') return $path1 . $path2;
		if(stristr($path2, $path1) || stristr($path2, self::systemize($path1))) 
			return $path2;
		$paths = array($path1, $path2);
		$path = implode('/', $paths);
		// replace any duplicate slashes
		$path = str_replace(array('///', '//'), '/', $path);
        $path = str_replace(':/', '://', $path);
        //test for windows
		if($systemize)
			$path = self::systemize($path);
		return $path;
	}
	
	/**
	 * util_Path::joinPathArray()
	 * 
	 * @param mixed $paths
	 * @return
	 */
	public static function joinPathArray($paths)
	{
		if(!is_array($paths)) return;
		return self::join(array_shift($paths), implode($paths, '/'));
	}
	
	/**
	 * util_Path::joinTest()
	 * does a path join and returns false if path does not exist
	 * 
	 * @param mixed $path1
	 * @param mixed $path2
	 * @return
	 */
	public static function joinTest($path1, $path2)
	{
		$path = self::join($path1, $path2);
		return (self::testPath($path))? $path : false;
	}
	
	/**
	 * util_Path::requireName()
	 * 
	 * @param string $path_name
	 * @return void
	 */
	public static function requireName($path_name = '', $type = self::TYPE_SYSTEM)
	{
		if(empty(self::$paths[$path_name]))
		{
			$msg = sprintf('path name "%s" is required', $path_name);
			throw new exception($msg);
		}
		return self::get($path_name, $type);
	}
	
	/**
	 * util_Path::systemize()
	 * 
	 * @param mixed $path
	 * @return
	 */
	public static function systemize($path)
	{
		if(DIRECTORY_SEPARATOR == '/'){
			return str_replace('\\', '/', $path);
		} else {
			return str_replace('/', '\\', $path);	
		}
	}
	
	/**
	 * util_Path::getPaths()
	 * 
	 * @param mixed $type
	 * @return
	 */
	public static function getPaths($type = util_Path::TYPE_SYSTEM)
	{
		$util = self::getInstance();
		$paths = array();
		foreach(self::$paths as $name => $path){
			$paths[$name] = $util->get($name, $type);
		}
		foreach(self::$path_overrides as $name => $path){
			$paths[$name . '_override'] = $path;
		}
		$paths = array_unique($paths);
		return $paths;
	}
	
	//////////////////////////////////////////////
	// Scan Dir methods
	/////////////////////////////////////////////
		
	/**
	 * util_Path::getContents()
	 * 
	 * @param String $path_name The name of the stored path
	 * @param Bool $full_path Whether or not to include full path with contents
	 * @return Array of files in the path specified 
	 */
	public static function getContents($path_name, $full_path = false)
	{
		if(empty($path_name)) return array();
		
		if(!self::isPathName($path_name)){
			if(!$dir = realpath($path_name))
				throw new Exception(sprintf('The following path could not be resolved: "%s"', $path_name));
		} else {
			$dir = self::get($path_name, self::TYPE_SYSTEM);	
		}
		
		if(empty($dir)){
			throw new Exception("Path is not set for key {$path_name} Could not scan the folder");
		}
		$contents = scandir($dir);
		$files = array();
		foreach($contents as $file)
		{
			if(in_array($file, array('.', '..'))) continue;
			if(substr($file, 0, 1) == '.') continue;
			$files[] = ($full_path)
				? self::join($dir, $file) 
				: $file;
		}
		return $files;
	}
	
	/**
	 * util_Path::getFiles()
	 * Gets the filenames from the given Dir path_name
	 * 
	 * @param String $path_name The name of the stored path
	 * @return Array of files in the path specified 
	 */
	public static function getFiles($path_name, $type = self::TYPE_FILENAME)
	{
		$contents = self::getContents($path_name, true);
		$files = array();
		foreach($contents as $file)
		{
			if(!is_file($file)) continue;
			// set the filename to the value
			if($type == self::TYPE_FILENAME){
				$files[] = basename($file);
			}
			if($type == self::TYPE_SYSTEM){
				$files[] = $file;
			}
			elseif($type == self::TYPE_URL){
				$files[] = self::makeURL($file);
			}
		}
		return $files;
	}
	
	/**
	 * util_Path::getFiles()
	 * Gets the filenames from the given Dir path_name
	 * 
	 * @param String $path_name The name of the stored path
	 * @return Array of files in the path specified 
	 */
	public static function getFilesRecusive($path_name, $type = self::TYPE_RELATIVE)
	{
		// resolve path
		if(!self::isPathName($path_name)){
			if(!$dir = realpath($path_name))
				throw new Exception(sprintf('The following path could not be resolved: "%s"', $path_name));
		} else {
			$dir = self::get($path_name, self::TYPE_SYSTEM);
			$dir .= DIRECTORY_SEPARATOR;	
		}
		
		$contents = self::getContents($path_name, true);
		$files = array();
		foreach($contents as $file)
		{
			if(is_dir($file)){
				$recur_files = self::getFilesRecusive($file, self::TYPE_SYSTEM);
				foreach($recur_files as $file){
					if($type == self::TYPE_RELATIVE){
						$files[] = str_replace($dir, '', $file);
					}else{
						$files[] = $file;
					}
				}
			} elseif(is_file($file)) {
				if($type == self::TYPE_RELATIVE){
					$files[] = str_replace($dir, '', $file);
				}else{
					$files[] = $file;
				}
			}
		}
		return $files;
	}
	
	/**
	 * util_Path::getDirs()
	 * 
	 * @param String $path_name The name of the stored path
	 * @return Array of files in the path specified 
	 */
	public static function getDirs($path_name)
	{
		if(empty($path_name)) return array();
		$contents = self::getContents($path_name, true);
		$dirs = array();
		foreach($contents as $dir)
		{
			if(!is_dir($dir)) continue;
			$dirs[] = basename($dir);
		}
		return $dirs;
	}
	
	/**
	 * Gets an image URL form the path set
	 * For this to work the path name 'image' must be set
	 * 
	 * @param String $image The path to the image 
	 * @return String Path to the image
	 */
	public static function getImage($file_name)
	{
		$exts = array('', '.jpg', '.png', '.gif');
		foreach($exts as $ext)
		{
			$path = self::getFilePath($file_name . $ext, 'image');
			if($path) break;
			if(strstr($file_name, '.')) break; // break if file_name has a set extension
		}
		if($path){
			return self::makeURL($path);
		}
		return $file_name;
	}
	
	/**
	 * util_Path::makeURL()
	 * 
	 * @param mixed $path
	 * @return
	 */
	public static function makeURL($path)
	{
		$orig = $path;
		$replacements = array(
			self::systemize($_SERVER['DOCUMENT_ROOT']),
			self::systemize(dirname($_SERVER['SCRIPT_FILENAME'])),
		);
		$path = str_replace($replacements, '', $path);
		$path = str_replace('\\', '/', $path);
		//$path = self::$root . $path;
		$path = str_replace('//', '/', $path);
		return $path;
	}

	/**
	 * util_Path::getFile()
	 * Locates a file in the path specified and return a path or false on failure
	 * 
	 * @param string $path_name
	 * @param string $file_name
	 * @return void
	 */
	public static function getFilePath($file_name, $base_path)
	{
		if(empty($base_path)) return false;
		
		if(self::isPathName($base_path)){
			$path_name = $base_path;
			$override = self::getPathOverride($base_path, self::TYPE_SYSTEM);
			$base_path = ($override)? $override : self::get($base_path, self::TYPE_SYSTEM); 
		} else {
			$override = false;
		}
		
		if($path = self::joinTest($base_path, $file_name)){
			return $path;
		}
		
		// test path_file to path/file
		if(strstr($file_name, '_')){
			list($name) = explode('.', $file_name);
			$dirs = explode('_', $name);
			$path = util_Path::join($base_path, implode('/', $dirs));
			if($path = util_Path::testPath($path, $file_name))
				return $path;
		}
		
		// get all the subdirs
		$dirs = self::getDirs($base_path);
		foreach($dirs as $dir)
		{
			if($path = self::joinTest($dir, $file_name)){
				return $path;
			}
		}
		
		// if override got here try regular
		if($override){
			return self::getFilePath($file_name, util_Path::get($path_name, self::TYPE_SYSTEM));	
		}
	}
	
	/**
	 * util_Path::isPathName()
	 * 
	 * @param String $test_string The string to test if it is a path name
	 * @return Bool Whether or not the string given is a path name
	 */
	public static function isPathName($test_string)
	{
		return (in_array($test_string, array_keys(self::$paths))
			||	in_array($test_string, array_keys(self::$path_overrides))
			);
	}
	
 	/**
 	 * util_Path::addIncludePath()
 	 * Adds an include path
	 * 
 	 * @param String $path The path to add to the include path
 	 * @return void
 	 */
 	public static function addIncludePath($path)
 	{
 		$paths = explode(PATH_SEPARATOR, get_include_path());
 		$paths[] = $path;
 		array_unique($paths);
 		$paths = implode(PATH_SEPARATOR, $paths);
 		set_include_path($paths);
 	}
}
