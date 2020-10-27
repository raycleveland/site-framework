<?php

class util_Element
{
    private $tagname;
    private $attributes = array();
    private $children = array();
    private $indent;
    
    private static $self_close = array(
        'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta',
    );
    
    /**
     * util_Element::__construct()
     * 
     * @param mixed $tagname
     * @return void
     */
    public function __construct($tagname, $attributes = array(), $contents = null)
    {
        $this->tagname = $tagname;
        if(is_array($attributes)){
            $this->attributes = $attributes;
        }
        if(!is_null($contents)){
            $this->children[] = $contents;
        }
    }
    
    /**
     * util_Element::__tostring()
     * 
     * @return String the markup for the attribute
     */
    public function __tostring()
    {
        $element = "{$this->indent}<{$this->tagname}";
        $element .= ($attributes = $this->getAttributes())
             ? "{$attributes}>\n" : '>';
        // close self closing elements
        if(in_array($this->tagname, self::$self_close)){
            return $element . "/>\n";
        }
        if($contents = $this->getContents()){
            $element .= $contents;
            if(strlen($contents > 15)){
                $element .= "\n";
            }
        } else {
            return '';
        }
        $element .= "</{$this->tagname}>\n";
        if($this->tagname == 'a'){
            $element = str_replace("\n", '', $element);
        }
        return $element;
    }
    
    /**
     * util_Element::__set()
     * 
     * Adds an attribute to the element
     * 
     * @param mixed $name name of the attribute to add
     * @param mixed $value value of the attribute to a
     * @return void
     */
    public function __set($name, $value)
    {
        $this->addAttribute($name, $value);
    }
    
    /**
     * util_Element::addAttribute()
     * 
     * Adds an attribute to the element
     * 
     * @param mixed $name name of the attribute to add
     * @param mixed $value value of the attribute to add
     * @return void
     */
    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * util_Element::getAttributes()
     * 
     * @return String of all the arrtibutes in the instance
     */
    private function getAttributes()
    {
        $string = '';
        foreach($this->attributes as $name => $value){
            $string .= " {$name}=\"{$value}\"";
        }
        return $string;
    }
    
    /**
     * util_Element::getContents()
     * 
     * @return String the contents of the element
     */
    private function getContents()
    {
        $contents = '';
        foreach($this->children as $child)
        {
            $contents .= $child;
        }
        return $contents;
    }
    
    /**
     * util_Element::addIndent()
     * 
     * @param mixed $indent
     * @return void
     */
    public function addIndent($indent)
    {
        $this->indent = $indent;
    }   
     
    /**
     * util_Element::addChild()
     * 
     * @param mixed $tagname Instance of this object or sting of the tagname
     * @param Array $attributes Associative array of attributes
     * @param mixed $contents String of contents for element
     * @return void
     */
    public function addChild($tagname, $attributes = array(), $contents = null)
    {
        if(is_object($tagname)){
            $tagname->addIndent($this->indent . "\t");
            $this->children[]  = $tagname;
        } else {
            $this->children[] = new self($tagname, $attributes, $contents);    
        }
    }
    
    /**
     * util_Element::addContent()
     * 
     * @param mixed $content
     * @return void
     */
    public function addContent($content)
    {
        $this->children[] = $content;
    }
    
    // API constructors
    
    /**
     * util_Element::getTable()
     * 
     * Warning: this returns a class with potentiallty A LOT of child elements
     * 
     * @param mixed $header
     * @param mixed $data
     * @return void
     */
    public static function getTable($data, $header)
    {
        $table = new self('table');
        
        // header
        $head = new self('tr');
        foreach($header as $title)
        {
            $head->addChild('th', null, $title);
        }
        $head = new self('thead', null, $head);
        $table->addChild($head);
        
        // body
        $body = new self('tbody');
        $keys = array_keys($header);
        foreach($data as $num => $row)
        {
            $class = ($num % 2 == 0)? 'odd' : 'even';
            $tr = new self('tr', array('class'=>$class));
            foreach($keys as $key){
                if(!isset($row[$key])) continue;
                $tr->addChild('td', null, $row[$key]);  
            }
            $body->addChild($tr);
        }
        $table->addChild($body);
        
        return $table;
    }
    
    /**
     * util_Element::getList()
     * 
     * @param Array $list
     * @return util_element()
     */
    public function getList($list)
    {
        if(!is_array($list)){
            throw new Exception('$list is expected to be an array');
        }
        $element = new self('ul');
        foreach($list as $item)
        {
            $element->addChild('li', null, $item);
        }
        return $element;
    }
    
    /**
     * util_Element::getSelect()
     * 
     * @param mixed $options
     * @param mixed $selected_value
     * @param mixed $attributes
     * @return void
     */
    public function getSelect($options, $selected_value = null, $attributes = array())
    {
        $select = new self('select', $attributes);
        foreach($options as $value => $name){
            $option = new self('option', array('value'=>$value), $name);
            if($value == $selected_value) $option->selected = 'selected';
            $select->addChild($option);
        }
        return $select;
    }
}