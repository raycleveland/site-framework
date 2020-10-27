<?php



class util_Date
{
	
	// seconds constants
	const SECONDS_YEAR 	= 31536000;
	const SECONDS_MONTH = 2629744;
	const SECONDS_WEEK 	= 604800;
	const SECONDS_DAY  	= 86400;
	const SECONDS_HOUR	= 3600;
	const SECONDS_MINUTE= 60;
	
    public static function emptyDate($date)
    {
        if(empty($date)) return true;
        if(in_array($date, array('0000-00-00 00:00:00', '0000-00-00'))) return true;
        return false;
    }
    
    /**
	 * util_Date::elapsed()
	 * 
	 * @param mixed $start
	 * @param bool $end
	 * @return
	 */
	public static function elapsed( $start, $end = false, $endString = 'ago')
	{
		if(self::emptyDate($start)) return '';
        if(is_string($start)) $start = strtotime($start);
        if ($end == false) //if you don't pass a second parameter,
		$end = time(); //we assume you're comparing to now.
		
		$diff = $end - $start;
		
        $years = floor( $diff / self::SECONDS_YEAR ); //calculate the months
		$diff = $diff - ($years * self::SECONDS_YEAR ); // subtract the months
        
        $months = floor( $diff / self::SECONDS_MONTH ); //calculate the months
		$diff = $diff - ($months * self::SECONDS_MONTH ); // subtract the months
        
        $days = floor( $diff / self::SECONDS_DAY ); //calculate the days
		$diff = $diff - ($days * self::SECONDS_DAY ); // subtract the days
		
		$hours = floor ( $diff / self::SECONDS_HOUR ); // calculate the hours
		$diff = $diff - ($hours * self::SECONDS_HOUR); // subtract the hours
		
		$mins = floor ( $diff / self::SECONDS_MINUTE ); // calculate the minutes
		$diff = $diff - ($mins * self::SECONDS_MINUTE); // subtract the mins
		
		$secs = $diff; // what's left is the seconds;
		if ($secs > 0) {
			$retval = "$secs second".(($secs>1) ? "s":"");
		}
		
		if ($mins > 0) {
			$retval = "$mins minute".(($mins>1) ? "s":"");
		}
		
		if ($hours > 0) {
			$retval = "$hours hour".(($hours>1) ? "s":"");
		}
		
		if ($days > 0) {
			$retval = "$days day".(($days>1) ? "s":"");
		}
        
        if ($months > 0) {
			$retval = "$months month".($months>1 ? "s":"");
		}
        
        if ($years > 0) {
			$retval = "$years year".(($years>1) ? "s":"");
		}
		
		if(!empty($endString)) {
			$retval .= " {$endString}";
		}

		return $retval;
	}

}