<?php

/**
 * util_ImageResize
 * 
 * Uses Built in PHP GD image functions  
 * 
 * @package Site Framework
 * @author Ray Cleveland
 * @copyright 2009
 * @access public
 */
class image_Resize
{
	private $overwrite = false; 
	
	// original image path
	private $path;
	// original image resource
	private $original;
	
	private $save_file;
	
	// dimension properties
	private $image_info;
	private $target_w;
	private $target_h;
	private $landscape = false;
	private $portrait = false;
	private $square = false;
	private $offset_x = 0;
	private $offset_y = 0;
	private $resize_square = false;
	
	/**
	 * util_ImageResize::__construct()
	 * 
	 * @param mixed $file_path The path to the original file
	 * @param Integer $width  The desired resize width
	 * @param Integer $height The desired resize height
	 * @return void
	 */
	public function __construct($file_path, $width = null, $height = null)
	{
		if(!is_file($file_path)){
			throw new Exception("Inavlid File Path specified:\n {$file_path}");
		}
		$this->path = $file_path;
		$this->setTargetDimensions($width, $height);
	}
	
	
	/**
	 * image_Resize::setOverwrite()
	 * 
	 * @param bool $overwrite
	 * @return void
	 */
	public function setOverwrite($overwrite)
	{
		$this->overwrite = (bool)$overwrite;
	}
	
	/**
	 * util_ImageResize::setTargetDimensions()
	 * 
	 * @param Integer $width  The desired resize width
	 * @param Integer $height The desired resize height
	 * @return void
	 */
	public function setTargetDimensions($width, $height = null)
	{
		$this->target_w = $width;
		$this->target_h = $height;
		if(!empty($height) && $width === $height){
			 $this->resize_square = true;
		}
	}
	
	public function setSaveFile($file_name)
	{
		if(!strstr($file_name, '.')){
			throw new Exception('Inavlid file name specified for save file');
		}
		$dir = dirname($file_name);
		if(!is_dir($dir)){
			mkdir($dir);
		}
		$this->save_file = $file_name;
	}
	
	/**
	 * util_ImageResize::initTargetDimensions()
	 * 
	 * Caluculates the target dimensions and offsets for the target image
	 * 
	 * @return void
	 */
	private function initTargetDimensions()
	{
		//original dimensions
		list($orig_width, $orig_height) = $this->getImageInfo();
		$orig_large = ($orig_width > $orig_height)? $orig_width : $orig_height;
		$orig_small = ($orig_width < $orig_height)? $orig_width : $orig_height;
		
		// init the original image size
		if(empty($this->target_w))
		{
			$this->target_w = $orig_width;
			$this->target_h = $orig_height;
		}
		// init with missing height
		elseif(empty($this->target_h)){
			// new dimensions scaled
			$large = $this->target_w;
			$small = floor($orig_small * $large / $orig_large);
			$this->target_w = ($this->landscape)? $large : $small;
			$this->target_h = ($this->portrait)? $large : $small;
			
			// calculate square offset
			if($this->resize_square){
				$offset = floor(($large - $small) / 2);
				if($this->landscape){
					$this->offset_x = $offset;
				}else{
					$this->offset_y = $offset;
				}
			}
		}
		elseif($this->resize_square){
			// new dimensions scaled
			$small = $this->target_w;
			$large = floor($orig_small * $small/ $orig_small);
			$this->target_w = ($this->landscape)? $large : $small;
			$this->target_h = ($this->portrait)? $large : $small;
			
			// calculate square offset
			$offset = floor(($large - $small) / 2);
			if($this->landscape){
				$this->offset_x = $offset;
			}else{
				$this->offset_y = $offset;
			}
		}
	}
	
	/**
	 * util_ImageResize::getImageInfo()
	 * 
	 * @return Result of getimagesize for the image
	 */
	public function getImageInfo()
	{
		if(empty($this->image_info))
		{
			$this->image_info = getimagesize($this->path);
			// set $square $portrait and $landscape properties
			list($width, $height) = $this->image_info;
			if($width == $height){
				$this->square = true;
			}
			elseif($width > $height){
				$this->landscape = true;
			}
			elseif($width < $height){
				$this->portrait = true;
			}
		}
		return $this->image_info;
	}
	
	/**
	 * util_ImageResize::resize()
	 * 
	 * @param mixed $width
	 * @param mixed $height
	 * @return Image Resource
	 */
	private function getResizedImage()
	{
		$this->initTargetDimensions();
		if(is_file($this->save_file)){
			list($width, $height) = getimagesize($this->save_file);
			if($this->target_w == $width && $this->target_h == $height)
			{
				return imagecreatefromjpeg($this->save_file);
			}
		}
		
		//TODO make smarter implimentation of memory size which looks @ file size
		ini_set('memory_limit', '64M');
		// get a resource of the original image
		if(empty($this->original)){
			$this->original = imagecreatefromjpeg($this->path);	
		}
		list($w, $h) = $this->getImageInfo();
		// get the new dimensions
		$new_image = imagecreatetruecolor($this->target_w, $this->target_h);
		imagecopyresampled($new_image, $this->original, 0, 0, 0, 0
			, $this->target_w, $this->target_h, $w, $h);
		
		// crop square thumbnails
		if($this->resize_square){
			$src = $new_image;
			$sm = ($this->target_w <  $this->target_h)? $this->target_w : $this->target_h;
			$new_image = imagecreatetruecolor($sm, $sm);
			imagecopy ( $new_image, $src, $this->offset_x, $this->offset_y, 0, 0, $this->target_w, $this->target_h);
		}
		
		// save file
		if(!empty($this->save_file) && (!file_exists($this->save_file) || $this->overwrite){
			ob_start();
			if(!imagejpeg($new_image, $this->save_file, 75)){
				throw new Exception("unable to save file \"$this->save_file\"");	
			}
			ob_end_clean();
		}
		
		return $new_image;
	}
	
	/**
	 * util_ImageResize::display()
	 * 
	 * @return void
	 */
	public function display()
	{
		$image = $this->getResizedImage();
		header('Last-Modified: '.date('r'));
		header('Accept-Ranges: bytes');
		#header("Cache-Control: max-age=290304000, public");
		header('Content-Type: image/jpeg');
		
		// binary output
		if(is_file($this->save_file)){
			echo file_get_contents($this->save_file);
		} else {
			imagejpeg($image, NULL, 75);	
		}
	}
	
}