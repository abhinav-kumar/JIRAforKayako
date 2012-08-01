<?php
/**
 * =======================================
 * ###################################
 * SWIFT Framework
 *
 * @package		SWIFT
 * @author		Kayako Infotech Ltd.
 * @copyright	Copyright (c) 2001-2009, Kayako Infotech Ltd.
 * @license		http://www.kayako.com/license
 * @link		http://www.kayako.com
 * @filesource
 * ###################################
 * =======================================
 */

/**
 * The base class for all SWIFT_HTTP based library
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_HTTPBase extends SWIFT_Library
{

	//HTTP Version
	const HTTP_OLD = '1.0';
	const HTTP_NEW = '1.1';

	//Method Types
	const GET		= 'GET';
	const POST	= 'POST';
	const PUT		= 'PUT';
	const DELETE	= 'DELETE';
	const HEAD	= 'HEAD';

	//Response Codes
	const HTTP_OK					= '200';
	const HTTP_MOVED_PERMANENTLY	= '301';
	const HTTP_BAD_REQUEST			= '400';
	const HTTP_FORBIDDEN			= '403';
	const HTTP_NOT_FOUND			= '404';
	const HTTP_SERVER_ERROR			= '500';
	const HTTP_SERVICE_UNAVAILABLE	= '503';

	//Encoding Types
	const ENC_FORMDATA	 = 'multipart/form-data';
	const ENC_URLENCODED	 = 'application/x-www-form-urlencoded';
	const ENC_PLAIN	         = 'text/plain';

	//Response Types
	const RESPONSETYPE_XML	= 'xml';
	const RESPONSETYPE_JSON	= 'json';

	//Authentication methods
	const AUTH_BASIC = 'basic';

	/**
	 * Constructor
	 * returns 'true' on success and 'false' otherwise
	 * @return bool
	 */
	public function __construct() {
		return parent::__construct();
	}

	/**
	 * Destructor
	 * returns 'true' on success and 'false' otherwise
	 * @return bool
	 */
	public function __destruct() {
		return parent::__destruct();
	}

	/**
	 * Checks if a Request Type is valid
	 * @param type $_RequestType
	 * @return boolean
	 */
	public function isValidMethod($_Method)
	{
		$_Method = strtoupper($_Method);
		if($_Method == self::GET || $_Method == self::POST ||
				$_Method == self::PUT || $_Method == self::DELETE)
			return true;
		return false;
	}
}