<?php

/**
 * util_String
 * 
 * Utility class for Curl Connections
 * 
 * @package rays-framework
 * @author Ray Cleveland
 * @copyright 2014
 * @access public
 */
class util_Curl
{
	/**
	 * fetch data using a curl connection or throw exception on error
	 * @param String $url the url to fetch data from
	 * @param Array $curlopts Associative array of curl options [see: http://www.php.net/manual/en/function.curl-setopt.php]
	 * @return Mixed result of the curl connection
	 */
	public static function get($url, $curlopts = array()) {
		// setup the curl connection
		$ch = curl_init($url);
		$params = array(
			CURLOPT_CONNECTTIMEOUT => 120,// wait up to 2 minutes per connection
			CURLOPT_RETURNTRANSFER => true,
			//CURLOPT_SSL_VERIFYPEER => false
		);
		foreach($curlopts as $opt => $val) {
			$params[$opt] = $val;
		}
		curl_setopt_array($ch, $params);

		// fetch the data from the connection
		$return_data = curl_exec($ch);

		// throw an exception if return data is false
		if($return_data === FALSE) {
			throw new Exception('Curl Error: ' . curl_error($ch));
		}

		// close the curl connection
		curl_close($ch);

		return $return_data;
	}
}