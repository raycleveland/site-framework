<?php
/**
 * image_Helper
 * 
 * @package Site Framework
 * @author Ray Cleveland
 * @copyright 2010
 * @access public
 */
class image_Helper {

    private $path;
    private $data;
    private $dbId;
    private static $symlinkDir;
   
    // ************************************************************
    // ** STATIC SETTERS ******************************************
    
    /**
     * Simple setter for symlink directory
     */
    public static function setSymlinkDir($dir)
    {
        if(!is_dir($dir)) {
            throw new Exception('Please use a valid directory to set the symlink dir');
        }
        self::$symlinkDir = $dir;
    }

    // ************************************************************
    // ** CONSTRUCTED METHODS *************************************
    
    /**
     * image_Helper::__construct()
     * 
     * @param Mixed $ident model_PhotosyncRow object, db id, or file path  to a valid image file
     */
    public function __construct($ident)
    {
        // identifier is a data row
        if(is_a($ident, 'model_PhotosyncRow')) {
            $this->data = $ident;
        }

        // identifier is a primary key
        elseif(is_numeric($ident)) {
            $this->dbId = $ident;
        }
        
        // identifier is a path
        elseif(is_file($ident)) {
        	$this->path = $ident;
        	$this->updateMetadata($ident);
        }
    }

    /**
     * Update the data for the image
     * if the image does not exist in the database it will add it to the database
     */
    public function updateMetadata($file = '') {
    	if(empty($file)) {
    		$data = $this->getData();
    		if(!isset($data['path'])) {
    			throw new Exception("unable to find file \"{$file}\"");
    		}
    		$file = $data['path'];
    	}
    	if(!is_file($file)) {
    		throw new Exception("unable to find file \"{$file}\"");
    	}

        $table = Control::getTable('photosync');
        $row = $table->getRow($file, 'path');

        // if directory or does not contain the string JPG return
        if(is_dir($file) || stripos($file, '.jpg') === FALSE){ return; }
        
        // The current time of looping for data entry
        $date = date('Y-m-d H:i:s');
        
        // get width and height of image
        $size = @getimagesize($file);
        if(empty($size)) return;
        list($width, $height) = $size;
        
        // base data
		$data = array(
			'path' 			=> $file,
			'filename' 		=> basename($file),
			'date_updated'	=> $date,
			'date_added'	=> $date,
			'width' 		=> $width,
			'height' 		=> $height,
			'date_taken'	=> date("Y-m-d H:i:s.", filectime($file)),
		);

		// get meta data
        try {
	        $update_tags = true;
	        $meta = new image_Meta($file);
	        $cust_exif = $meta->getCustomExif();
	        $data = array_merge($data, array(
				'title'   		=> $meta->getTitle(),
				'description'   => $meta->getDescription(),
				'date_taken'	=> $meta->getDateTaken(),
				// custom exif data
				'camera_make' => $cust_exif['camera_make'],
				'camera_model' => $cust_exif['camera_model'],
				'aperture' => $cust_exif['aperture'],
				'exposure' => $cust_exif['exposure'],
				'iso' => $cust_exif['iso'],
				'focal_length' => $cust_exif['focal_length'],
				'flash_fired' => $cust_exif['flash_fired'],
			));
    	} catch (Exception $e) {
    		$update_tags = false;
    	}

        // insert new data
        if($row->isEmpty())
        {
            $data['date_added']	= $date;
            $this->dbId = $table->insert($data);
            $row = $this->getData();
            $this->makeSymlink();
        }
        // OR if the file exists in the DB update it
        else
        {
            // update the date
            $row->update($data);
        }

        if($row->thumbs_needed) {
        	$this->makeImageSizes();
        }

        // add tags
		if($update_tags) {
			$this->updateTags($meta);
			unset($meta);
		}
    }

    /**
     * update the tags for the photo
     */
    public function updateTags(image_Meta $meta) {

		static $insert_tag, $insert_rel, $addMeta, $getRuleId, $tag_table;
		$row = $this->getData();

		// prepare statements
		if(empty($insert_tag)) {
			$dbh = Control::getDb();
			$getRuleId = $dbh->prepare('SELECT photo_tag_id FROM photo_tag_rules WHERE replace_name = ?');
			$insert_tag = $dbh->prepare(
				"INSERT INTO photo_tags"
			.	" (tag_category, tag_name, slug)"
			.	" VALUES(?, ?, ?)"
		 	); 
			$insert_rel = $dbh->prepare(
				"REPLACE INTO photosync_tags"
			.	" (photosync_id, photo_tag_id, location) VALUES (?,?,?)"
		 	);
		 	$tag_table = Control::getTable('photo_tags');
		}

    	$people = $meta->getPeopleTags();
		$subjects = $meta->getSubjects();

	    // add people tags
		foreach($people as &$person) {
	        
	        // get the id from the name
	        $name = ucwords($person['name']);
	        $id = 0;
	        while(empty($id)) {
	            
	            // try to get an id from rules first
	            $getRuleId->execute(array($name));
	            if(!$id = $getRuleId->fetch(PDO::FETCH_COLUMN)) {
	                $id = $this->getTagId($name, 'People');
	            }

	            if(empty($id)) {
	                try{$insert_tag->execute(array('People', $name, $tag_table->makeTagSlug($name)));} catch(Exception $e) {}
	            }
	            $person['id'] = $id;
	        }

			$res = $insert_rel->execute(array($row['photosync_id'], $id, $person['location']));
	    }
		
		// if the tags are not empty use them
		if(!empty($subjects)){
			
			// loop through tags in photo
			foreach($subjects as $subject)
			{
				$parts = explode('/', $subject);
				
				// extract tag name and category
				$tag_name = array_pop($parts);
				$category = array_pop($parts);
				if(count($parts) > 0) continue;
				
				// check for tag presence
				$id = $this->getTagId($tag_name, $category);
				
				// add the tag to the database
				if(empty($id)) {
					$insert_tag->execute(array($category,$tag_name, $tag_table->makeTagSlug($tag_name)));
					$id = $this->getTagId($tag_name, $category);
				}
				
				// if id is still empty continue loop
				if(empty($id)){
					continue;	
				}
					
				// add the photo to tag relationship
				$res = $insert_rel->execute(array($row['photosync_id'], $id, ''));
			}
		}
    }

    /**
     * @return model_PhotosyncRow of the current data row
     */
    private function getData() 
    {
        if(is_null($this->data)) {
        	$table = Control::getTable('photosync');
            if(!is_null($this->dbId)) {
                $this->data = $table->getRow($this->dbId);
            }
            elseif(!is_null($this->path)) {
            	
        		$this->data = $table->getRow($this->path, 'path');
            }
        }
        return $this->data;
    }

    /**
     * get the tag id from passed parameters
     */
    private function getTagId($name, $category) {
		
		$dbh = Control::getDb();
		if(empty($category)) {
			$stmt = $dbh->prepare('SELECT photo_tag_id FROM photo_tags WHERE tag_name=?');
			$stmt->execute(array($name)); 
		} else {
			$stmt = $dbh->prepare('SELECT photo_tag_id FROM photo_tags WHERE tag_category=? AND tag_name=?');
			$stmt->execute(array($category, $name));
		}
		return $stmt->fetchColumn();
	}

    /**
     * @return Int The database id
     */
    private function getId() 
    {
        if(is_null($this->dbId)) {
            $this->dbId = $this->getData()->getId();
        }
        return $this->dbId;
    }

    /**
     * @return Int The database id
     */
    private function getPath() 
    {
        if(is_null($this->path)) {
            $this->path = $this->getData()->path;
        }
        return $this->path;
    }

    /**
     * image_Helper::makeSymlink
     * 
     * 
     */
    public function makeSymlink() 
    {    
       if(empty(self::$symlinkDir)) {
            throw new Exception('Please call image_Helper::setSymlinkDir() before calling this method');
       } 
       $path = $this->getPath();
       $id = $this->getId();
       $dest = self::$symlinkDir . "/{$id}.jpg";
       if(is_link($dest)) {
            unlink($dest);
       }
       symlink($path, $dest);
    }

    /**
     * resize photos
     * 
     * make all the sizes for the photo
     */
    public function makeImageSizes() {
    	static $resize_file, $sizes;
    	if(empty($resize_file)) {
    		$resize_file = 'http://photos.raycleveland.com/resize.php';
    		$sizes = array(
		        '1200x800',
		        '600x400',
		        '100x100',
		    );
    	}

    	// make the image sizes
    	if($id = $this->getId()) {

    		// setup the curl connection
    		$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_FRESH_CONNECT => true,
				CURLOPT_TIMEOUT_MS => 1,
				CURLOPT_RETURNTRANSFER => 1,
			));

    		foreach($sizes as $size) {
    			$query = array(
    				'id' => $id,
    				'size' => $size
    			);
    			curl_setopt($ch, CURLOPT_URL, $resize_file . '?' . http_build_query($query));
    			curl_exec($ch);
    		}
    		curl_close($ch);

    		// update the database
    		$row = $this->getData();
    		$row->thumbs_needed = 0;
    	}
    }
}
