<?php
/**
 * class image_Meta
 *
 * All encompassing image meta data class
 * Covers XMP, Exif Etc
 * This is a work in progress
 * @author Ray Cleveland
 */
class image_Meta
{
	private $filename;
	private $xmp;
	private $raw_exif;
	private $raw_xmp;
	private $has_namespaces = false;
    private $header = '';
    private $namespaces;
	
	/**
	 * image_Meta::__construct()
	 * 
	 * @param String $filename The location of a valid JPEG IMAGE
	 */
	public function __construct($filename)
	{
		if(!stristr($filename, '.jpg') || !is_file($filename))
			throw new Exception('Please Use a Valid jpeg image to extract meta data');
		$this->filename = $filename;
	}
	
	public function __get($field)
	{
		return $this->getField($field);
	}
	
	public function getField($field)
	{
		$res = $this->getFieldEXIF($field);
		if(!empty($res)) return $res;
		$res = $this->getFieldXMP($field);
		if(!empty($res)) return $res;
		return '';
	}
	
	// API
	
	public function getDateTaken($format = 'Y-m-d H:i:s')
	{
		$res = $this->getFieldEXIF('DateTimeOriginal');
		if(!empty($res))
		{
			$res = date($format, strtotime($res));
		}
		return $res;
	}
	
	public function getSubjects()
	{
		$res = $this->getFieldXMP('subject');
		if(is_array($res)){
			foreach($res as &$val)
			{
				$val = (string)$val;
			}
		}
		return $res;
	}

    /**
     * getPeopleTags 
     * 
     * @access public
     * @return array of people tags (
     *   array => (
     *       ["location"] => coordinates on the image
     *       ["name"] => Person Name
     *       ["source"] => facebook etc
     */
    public function getPeopleTags() 
    {
		if($this->getRawXmp() == '') return array();

        $people = array();
        $stack = '//MP:RegionInfo//rdf:li/rdf:Description';
        $names = @$this->xmp->xpath("{$stack}/MPReg:PersonDisplayName");

        if(!$names) {
            return $people; // empty array    
        }

        $locs = @$this->xmp->xpath("{$stack}/MPReg:Rectangle");
        $sources = @$this->xmp->xpath("{$stack}/MPReg:PersonSourceID");

        foreach($names as $i => $name) {
            $people[] = array(
                'location' => isset($locs[$i]) ? (string) $locs[$i] : '',
                'name' =>  (string) $name,
                'source' =>  isset($sources[$i]) ? (string) $sources[$i] : '',
            );
        }

        return $people;
    }
	
    /**
     * getDescription 
     * 
     * @access public
     * @return String Description
     */
	public function getDescription()
	{
		$res = $this->getImplodedFieldXMP('description');
		if(empty($res)) $res = $this->getFieldEXIF('ImageDescription');
		return $res;
	}
	
	public function getTitle()
    {
        return $this->getImplodedFieldXMP('title');
    }
    
    /**
     * image_Meta::getHeader()
     * 
     * @return String the header contents of the image
     */
    public function getHeader()
    {
        if(empty($this->header))
        {
            // open the file to fetch the header
    		$handle = fopen($this->filename, 'rb');
    		
    		// loop through the lines
            // loop is stopped when binary image data is reached
            $lcount = 0;
    		while(!feof($handle) && ++$lcount < 1000){
                $line = fgets($handle);
                $this->header .= $line;
                
                if(strpos($line, '</x:xmpmeta>') !== FALSE){
                    break;
    			}
    		}
            
    		fclose($handle);
        }
        return $this->header;
    }
    
	////////////////////////
	// XMP METHODS
	////////////////////////
	
	/**
	 * image_Meta::getXMP()
	 * 
	 * @return SimpleXML object or FALSE on failure
	 */
	public function getXMP()
	{
		if(is_null($this->xmp))
        {
    		// open the file but only read the headers
    		$handle = fopen($this->filename, 'rb');
    		$header = $this->getHeader();
            //$header = mb_convert_encoding($header, "HTML-ENTITIES", "UTF-8");// convert header encoding 
    		
            $this->raw_xmp = $this->getTagFromRegex('x:xmpmeta', $header);
            
    		// xmp tag was not found in the header
            if(empty($this->raw_xmp))
    		{
                $this->xmp = FALSE;
    		}
    		else // Oh joy we have XMP data!
    		{   
                $string = "<?xml version=\"1.0\"?>\n" . $this->raw_xmp;
                $this->xmp = simplexml_load_string($string);
                
                // load the namespaces
                $this->namespaces = @$this->xmp->getNamespaces(true);
                if(!empty($this->namespaces) && is_array($this->namespaces)) {
                    $this->has_namespaces = true;
                    foreach ($this->namespaces as $key => $val) {
                        $this->xmp->registerXPathNamespace($key, $val);
                    }
                }
    		} 
		}
		return $this->xmp;
	}
	
	public function getRawXmp()
	{
		if(is_null($this->raw_xmp))
		{
			$this->getXMP();
		}
		return $this->raw_xmp;
	}
	
    public function getFieldXMP($field)
    {
        $this->getXMP();
        $res = ($this->getRawXMP() != '' && $this->has_namespaces)
        	? @$this->xmp->xpath("//dc:$field//rdf:li") : '';
		return $res;
    }
    
    public function getImplodedFieldXMP($field)
    {
    	$field = $this->getFieldXMP($field);
    	if(empty($field)) return '';
    	return implode("\n", $field);
    }
	
	private function getTagFromRegex($tag_str, $str, $contents_only = false)
	{
		/*
        // expiramental new way
		$parts = explode($tag_str, $str);
		if(isset($parts[1])){
			return "<{$tag_str}{$parts[1]}{$tag_str}>";
		}
        */
		
		$pattern = '#<' . $tag_str . '[^>]*>(.*?)</' . $tag_str . '>#ms';
		preg_match($pattern, $str, $matches);
		if(empty($matches)) return '';
		return ($contents_only)? $matches[1] : $matches[0];
	}
	
	////////////////////////
	// EXIF DATA METHODS
	////////////////////////
	
	public function getExif()
	{
		if(is_null($this->raw_exif))
		{
			$this->raw_exif = @exif_read_data($this->filename);
		}
		return $this->raw_exif;
	}
	
	public function getFieldEXIF($field)
	{
		$this->getExif();
		if(!isset($this->raw_exif[$field]))
		{
			return '';
		}
		return $this->raw_exif[$field];
	}
	
	/**
	 * custom meta information
	 */
	public function getCustomExif() {

		$exif = $this->getExif();
		if(empty($exif)) return array();
		foreach($exif as $name => $val) {
			if(strpos($name, 'UndefinedTag:') !== FALSE) {
				unset($exif[$name]);
			}
		}

		$flashValues = array(
			'0'  => 'Flash Did Not Fire',
			'1'  => 'Flash Fired',
			'2'  => 'Strobe Return Light Detected',
			'4'  => 'Strobe Return Light Not Detected',
			'8'  => 'Compulsory Flash Mode',
			'16' => 'Auto Mode',
			'32' => 'No Flash Function',
			'64' => 'Red Eye Reduction Mode'
		);

		$cust_exif = array(
			'camera_make' => $exif['Make'],
			'camera_model' => $exif['Model'],
			'aperture' => $exif['COMPUTED']['ApertureFNumber'],
			'exposure' => $exif['ExposureTime'],
			'iso' => $exif['ISOSpeedRatings'],
			'focal_length' => $exif['FocalLength'],
			'flash_fired' => !empty($exif['Flash']),
			'flash_value' => $flashValues[$exif['Flash']],
		);

		// calculate exposure further
		if(!empty($cust_exif['exposure'])) {
			$parts = explode("/", $cust_exif['exposure']);
			$cust_exif['exposure'] = implode("/", array(1, round($parts[1]/$parts[0])));	
		}

		return $cust_exif;
	}

}

function xml2array($xml) {
      $arXML=array();
      $arXML['name']=trim($xml->getName());
      $arXML['value']=trim((string)$xml);
      $t=array();
      foreach($xml->attributes() as $name => $value) $t[$name]=trim($value);
      $arXML['attr']=$t;
      $t=array();
      foreach($xml->children() as $name => $xmlchild) $t[$name]=xml2array($xmlchild);
      $arXML['children']=$t;
      return($arXML);
   }
