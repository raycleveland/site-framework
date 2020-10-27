<?php

class util_Error extends Exception
{
	
	private $exception_object;
	private $code_name;
	private $code_message;
	
	// error codes
	const CODE_AUTHORIZATION = 1;
	
	public function __construct($message = null, $code = 0)
	{
		if(is_object($message)){
			$this->exception_object = $message;
			parent::__construct($message->getMessage(), $message->getCode());
		}else{
			parent::__construct($message, $code);	
		}
		$this->getCodeInfo();
	}
	
	private function getCodeInfo()
	{
		switch($this->code)
		{
			case self::CODE_AUTHORIZATION:
				$code_name = 'Authorization';
				$code_message = 'You are not authorized to see this page';
				break;
			default:
				$code_name = 'Unknown';
				$code_message = '';
		}
	}
	
	// custom string representation of object
    public function __toString() 
	{
    	if(Auth::isAdmin())
		{
    		$str = "$this->code_name Error\n";
			if(!empty($this->exception_object)){
				$str.= $this->exception_object;
			}else{
				$str.= parent::__tostring();
			}
			return '<pre>' . $string . '</pre>';
    	}
		
        return "{$this->code_name} Error: [{$this->code}]: {$this->message}\n";
    }
	
}