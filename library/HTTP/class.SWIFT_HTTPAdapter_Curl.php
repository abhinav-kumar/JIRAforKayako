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
SWIFT_Loader::LoadLibrary('HTTP:HTTPBase');
SWIFT_Loader::LoadInterface('HTTP:HTTPAdapter', false, false);

/**
 * Curl wrapper for SWIFT_HTTP family of classes
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_HTTPAdapter_Curl extends SWIFT_HTTPBase Implements SWIFT_HTTPAdapter_Interface
{
	//Configuration Options

	/**
	 * Determines whether or not the requests should follow redirects
	 * @var bool
	 */
	private $_followRedirects = true;

	/**
	 * Ascociative array of Request Headers
	 * @todo some refactoring to see if we can have only one Headers array
	 * @var array
	 */
	private $_headers = array();

	/**
	 * Ascociative array of CURLOPT options
	 * @var array
	 */
	private $_options = array();

	/**
	 * User agent to send the request
	 * Defaults to 'SWIFT_HTTPClient'
	 * @var type
	 */
	private $_userAgent = 'SWIFT_HTTPClient';

	/**
	 * Response Type
	 * e.g. 'json' | 'xml'
	 * @var string
	 */
	private $_encoding = self::RESPONSETYPE_JSON;

	/**
	 * Stores the last error
	 * @var string
	 */
	private $_error = 'No Error';

	/**
	 * Request Handle
	 * @todo code SWIFT_HTTPRequest
	 * @var \SWIFT_HTTPRequest
	 */
	private $_Request;

	/**
	 * Sets any custom option for curl
	 *
	 * @param mixed|string $_option
	 * @param string $_value
	 * @return boolean
	 * @throws SWIFT_Exception if no option is supplied
	 */
	public function AddOption($_option = NULL, $_value = NULL)
	{
		if (NULL === $_option) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		if (_is_array($_option)) {
			foreach ($_option as $key => $val) {
				$this->addOption($key, $val);
			}
			return true;
		}
		$this->_options[$_option] = $_value;
		return true;
	}

	/**
	 * Default constructor.
	 * Checks if the curl extension is loaded
	 *
	 * @return boolean
	 * @throws SWIFT_Exception if curl extension is not loaded
	 */
	public function __construct()
	{
		parent::__construct();
		/**
		 * @todo Propose inclusion of a function to check loaded extension in global functions.php
		 */
		if (!in_array('curl', get_loaded_extensions())) {
			/**
			 * @todo could have been used some SWIFT_HTTPAdapter_CurlException  instead
			 */
			throw new SWIFT_Exception('Curl Extension ' . SWIFT_CLASSNOTLOADED);
			return false;
		}
	}

	/**
	 * Makes an HTTP DELETE request.
	 * @param string $_Url The URL to make the request to
	 * @param array $_Vars optional variables to send with the request
	 * @return \SWIFT_HTTPResponse
	 */
	public function Delete($_Url, $_Vars = array())
	{
		if ( ! empty($_Vars)) {
			/**
			 * Check if it has already '?' then append using '&'
			 */
			$_Url .= (stripos($_Url, '?') !== false) ? '&' : '?';
			$_Url .= (is_string($_Vars)) ? $_Vars : http_build_query($_Vars, '', '&');
		}

		return $this->request(self::DELETE, $_Url);
	}

	public function GetEncoding()
	{
		return $this->_encoding;
	}

	/**
	 * Retrieves the last error
	 * @return type
	 */
	public function GetError()
	{
		//parent::GetErrorMessage();
		return $this->_error;
	}

	/**
	 * appends the optional params to the $_Url &
	 * Makes an HTTP GET request.
	 * @param string $_Url The URL to make the request to
	 * @param mixed $_Vars optional variables to send with the request
	 * @return \SWIFT_HTTPResponse
	 */
	public function Get($_Url, $_Vars = array())
	{
		if ( ! empty($_Vars)) {
			/**
			 * Check if it has already '?' then append using '&'
			 */
			$_Url .= (stripos($_Url, '?') !== false) ? '&' : '?';
			$_Url .= (is_string($_Vars)) ? $_Vars : http_build_query($_Vars, '', '&');
		}
		return $this->request(self::GET, $_Url);
	}

	/**
	 * Makes an HTTP HEAS request.
	 * @param string $_Url The URL to make the request to
	 * @param array $_Vars optional variables to send with the request
	 * @return \SWIFT_HTTPResponse on success of 'false' otherwise
	 */
	public function Head($_Url, $_Vars = array())
	{
		return $this->request(self::HEAD, $_Url, $_Vars);
	}

	/**
	 * Makes an HTTP POST request.
	 * @param string $_Url The URL to make the request to
	 * @param array $_Vars optional variables to send with the request
	 * @return \SWIFT_HTTPResponse
	 */
	public function Post($_Url, $_Vars = array())
	{
		return $this->request(self::POST, $_Url, $_Vars);
	}

	/**
	 * Makes an HTTP PUT request.
	 * @param string $_Url The URL to make the request to
	 * @param array $_Vars optional variables to send with the request
	 * @return \SWIFT_HTTPResponse on success or 'false' otherwise
	 */
	public function Put($_Url, $_Vars = array())
	{
		return $this->request(self::PUT, $_Url, $_Vars);
	}

	/**
	 * Makes an HTTP request based on the specified $_Method
	 * to an $_Url with an optional array of string of $_Vars
	 * @param string $_Method
	 * @param string $_Url
	 * @param array $_Vars
	 * @return \SWIFT_HTTPResponse on success or 'false' otherwise
	 */
	public function Request($_Method = self::GET, $_Url ='', $_Vars = array())
	{
		/**
		 * Reset the error string
		 */
		$this->_error = 'No Error';

		/**
		 * Initialize the request
		 */
		$this->_Request = curl_init();

		/**
		 * Build query string
		 */
		if (_is_array($_Vars) && $_Method == self::GET) {
			$_Vars = http_build_query($_Vars, '', '&');
		}

		/**
		 * Set the request type
		 */
		$this->setRequestMethod($_Method);

		/**
		 * Set the required CURLOPT options
		 */
		$this->setRequestOptions($_Url, $_Vars, $_Method);

		/**
		 * Sets the required headers from $_Headers to CURLOPT
		 */
		$this->setRequestHeaders();

		/**
		 * Finally, fire the request
		 */
		$_Response = curl_exec($this->_Request);

		if ($_Response !== false) {
			curl_close($this->_Request);
			return $_Response;
			//= new SWIFT_HTTPResponse($_Response);
		} else {
			$this->_error = curl_errno($this->_Request) . ' - ' . curl_error($this->_Request);
		}

		/**
		 * Close the connection
		 */
		curl_close($this->_Request);
		return false;
	}

	/**
	 * Set the request encoding type
	 * @param string $_encoding
	 * @return \SWIFT_HTTPAdapter_Curl
	 */
	public function SetEncoding($_encoding)
	{
		if (is_string($_encoding))
			$this->_encoding = $_encoding;
		return $this;
	}

	/**
	 * Traverses through the $this->_Headers and sets the headers to CURLOPT
	 * @return \SWIFT_HTTPAdapter_Curl
	 */
	protected function SetRequestHeaders()
	{
		curl_setopt($this->_Request, CURLOPT_HTTPHEADER, $this->_headers);
		return $this;
	}

	public function setHeaders($name, $value)
	{
		$this->_headers[] = $name . ':' . $value;
	}

	/**
	 * Set the relevant CURL options for the request method
	 * @param string $_Method
	 * @return \SWIFT_HTTPAdapter_Curl
	 */
	private function SetRequestMethod($_Method)
	{
		switch (strtoupper($_Method)) {
			case 'HEAD':
				curl_setopt($this->_Request, CURLOPT_NOBODY, true);
				break;
			case 'GET':
				curl_setopt($this->_Request, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($this->_Request, CURLOPT_POST, true);
				break;
			default :
				curl_setopt($this->_Request, CURLOPT_CUSTOMREQUEST, $_Method);
				break;
		}
		return $this;
	}

	private function SetRequestOptions($_Url, $_Vars, $_Method = self::GET)
	{
		if (!is_string($_Url)) {
			throw new SWIFT_Exception('Request URL' . SWIFT_INVALIDDATA);
			return false;
		}
		/**
		 * Set the URL
		 */
		curl_setopt($this->_Request, CURLOPT_URL, $_Url);

		/**
		 * Set the POST fields if available
		 */
		if ( ! empty($_Vars) && $_Method == self::POST) {
			$data = '';

			if ($this->_encoding == 'json') {
				$data = json_encode($_Vars);
			}

			curl_setopt($this->_Request, CURLOPT_POST, true);
			curl_setopt($this->_Request, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($this->_Request, CURLOPT_HEADER, true);
		curl_setopt($this->_Request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_Request, CURLOPT_USERAGENT, $this->_userAgent);

		if ($this->_followRedirects)
			curl_setopt($this->_Request, CURLOPT_FOLLOWLOCATION, true);
		/**
		 * Not implemented yet
		 */
//		if ($this->_Referrer)
//			curl_setopt ($this->_Request, CURLOPT_REFERER, $this->_Referrer);


		 //Set any other custom CURL options
		foreach ($this->_options as $option => $value) {
			curl_setopt($this->_Request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
		}
	}

}