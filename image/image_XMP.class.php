<?php
/**
 * image_XMP
 * 
 * @author Ray Cleveland
 *
 * XMP meta data extraction.
 * usage: 
 * $xmp = new image_XMP(<image file path>);
 *  $xmp->getSubjects() // for tags
 *  $xmp->getTitle() // for image title
 */

class image_XMP
{
	private $filename;
	private $xmp;
	
	public function __construct($filename)
	{
		if(stripos($filename, '.jpg') === FALSE || !is_file($filename))
			throw new Exception('Please Use a Valid jpeg image to extract XMP');
		$this->filename = $filename;
	}
	
	public function getXMP()
	{
		if(is_null($this->xmp)) { 
		
            $handle = fopen($this->filename, 'rb');
            $data = '';
            while(!feof($handle)){
                $str = fgets($handle);
                $flag = strstr($str, 'x:xmpmeta');
                if(!empty($data)){
                    $data .= $str;
                    if($flag) break;
                }
                elseif($flag){
                    $data .= $str;	
                }
            }
            fclose($handle);
            $data = "<?xml version=\"1.0\"?>\n" . $this->getTagFromRegex('x:xmpmeta', $data);
            $this->xmp = simplexml_load_string($data);
            var_dumP($this->xmp);
            $namespaces = $this->xmp->getNamespaces(true);
            foreach ($namespaces as $key => $val) {
                $this->xmp->registerXPathNamespace($key, $val);
            }
        }
		return $this->xmp;
	}
	
    public function getField($field)
    {
        $this->getXMP();
        if(is_object($this->xmp)) {
		    return $this->xmp->xpath("//dc:$field//rdf:li");
        }
        else {
            return array();    
        }
    }
    
    public function getImplodedField($field)
    {
    	$field = $this->getField($field);
    	if(empty($field)) return '';
    	return implode("\n", $field);
    }
	
	public function getSubjects()
	{
		return $this->getField('subject');
	}
	
	public function getDescription()
	{
		return $this->getImplodedField('description');
	}
	
	public function getTitle()
    {
        return $this->getImplodedField('title');
    }
	
	private function getTagFromRegex($tag_str, $str, $contents_only = false)
	{
		$pattern = '#<' . $tag_str . '[^>]*>(.*?)</' . $tag_str . '>#ms';
		preg_match($pattern, $str, $matches);
		if(empty($matches)) return '';
		return ($contents_only)? $matches[1] : $matches[0];
	}
}
