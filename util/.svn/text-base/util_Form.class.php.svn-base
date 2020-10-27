<?php
//unfinished just an idea
class util_Form
{
	
	private $registry = array(
		'action' => '',
		'method' => 'post',
		'inputs' => array(),
		'selects'=> array(),
	);
	
	public function __get($varname)
	{
		$varname = strtolower($varname);
		return (isset($this->registry[$varname]))
			? $this->registry[$varname]
			: null;
	}
	
	public function __tostring()
	{
		printf('<form method="%s" action="%s">', $this->method, $this->action);
		
		printf('</form>');
	}
	
}

class util_FormInput
{
	
}

class util_FormSelect
{
	
}