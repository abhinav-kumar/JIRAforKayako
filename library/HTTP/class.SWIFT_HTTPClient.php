<?php
/**
 * =======================================
 * ###################################
 * SWIFT Framework
 *
 * @package	SWIFT
 * @author		Kayako Infotech Ltd.
 * @copyright	Copyright (c) 2001-2009, Kayako Infotech Ltd.
 * @license		http://www.kayako.com/license
 * @link		http://www.kayako.com
 * @filesource
 * ###################################
 * =======================================
 */

SWIFT_Loader::LoadLibrary('HTTP:HTTPBase');
SWIFT_Loader::LoadLibrary('HTTP:HTTPResponse');

/**
 * The base class for all SWIFT_HTTP based library
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_HTTPClient extends SWIFT_HTTPBase
{
	/**
	 * Request method - defaults to SWIFT_HTTP::GET
	 * @var string
	 */
	protected $_method = self::GET;


	/**
	 * @var \SWIFT_HTTPAdapter
	 */
	protected $Adapter = null;

	/**
	 *
	 * @var string
	 */
	protected $_URI = null;

	/**
	 * Ascociative array of Request Headers
	 * @var array
	 */
	protected $_headers = array();

	/**
	 * Ascociative array of GET params
	 * @var array
	 */
	protected $_paramsGet = array();

	/**
	 * Ascociative array of POST params
	 * @var array
	 */
	protected $_paramsPost = array();

	/**
	 * Ascociative array of params
	 * Contains both the array
	 * @var array
	 */
	protected $_params = array();

	/**
	 * The Request encoding type
	 * @var string
	 */
	protected $_encType = self::ENC_PLAIN;

	/**
	 * Whether or not an error occured
	 * @var bool
	 */
	protected $_isError = false;

	/**
	 * The error message
	 * @var string
	 */
	protected $_error;

		/**
	 * Constructor
	 * Returns 'true' on success and 'false' otherwise
	* @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param $_URI string the default Uri
	 * @return bool
	 */

	public function __construct($_URI = null)
	{
		parent::__construct();

		if (null != $_URI && is_string($_URI)) {
			$this->_URI = $_URI;
		}
		return true;
	}

	/**
	 * Destructor
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool  'true' on success and 'false' otherwise
	 */
	public function __destruct()
	{
		return parent::__destruct();
	}

	/**
	 * Sets the method for future requests
	 * @param string $_method
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPClient on success and 'false' otherwise
	 */
	public function SetMethod($_method = self::GET)
	{
		if ( !$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ($this->isValidMethod($_method)) {
			$this->_method = $_method;
			return $this;
		}
		return false;
	}

	/**
	 * Gets the current Method
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string the current method
	 */
	public function GetMethod()
	{
		if ( !$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		return $this->_method;
	}

	/**
	 * Sets the adapter for future requests
	 * @param \SWIFT_HTTPAdapter $_Adapter
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPClient on success and 'false' otherwise
	 * @throws SWIFT_Exception if $_Adapter is not a valid Adapter
	 */
	public function SetAdapter($_Adapter)
	{
		if ( !$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( is_a($_Adapter, 'SWIFT_HTTPAdapter_Interface') ) {
			$this->Adapter = $_Adapter;
			return $this;
		}
		throw new SWIFT_Exception('Invalid Http Adapter specified ');
	}

	/**
	 * Gets the current Adapter
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPAdapter_Interface the current HTTP Adapter
	 */
	public function GetAdapter()
	{
		if ( !$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}
		return $this->Adapter;
	}

	/**
	 * Sets the URI
	 * @param string $_URI
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPClient
	 * @throws SWIFT_Exception if $_URI is not valid
	 */
	public function SetURI($_URI)
	{
		if ( !$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( is_string($_URI)) {
			/**
			 * @todo validate $_URI
			 */
			$this->_URI = $_URI;
			return $this;
		}
		throw new SWIFT_Exception('URI' . SWIFT_INVALIDDATA);
	}

	/**
	 * Set one of more request Headers
	 * The function can handle 3 types of parametres
	 * 1. By providing two parametres $name as the header &
	 *    $value as the value
	 *
	 * 2. By providing a single string with structure
	 *    e.g. Header:Value
	 *
	 * 3. By providing an array of headers as the first param
	 *    e.g. array('host' => 'www.example.com', 'foo' => 'bar')
	 *
	 * @param string | mixed name of the header or an array of headers
	 * @param string value of the $name header
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPClient
	 */
	public function SetHeaders($_name, $_value = null)
	{
		if ( ! $this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( is_array($_name))  {
			foreach ($_name as $key => $_val) 	{
				if ( is_string($key) )
					$this->SetHeaders($key, $_val);
				else
					$this->SetHeaders ($_val, null);
			}
			$this->SetHeaders($_name);
		}

		$normalized_name = strtolower($_name);

		// If $value is null or false, unset the header
		if ($_value === null || $_value === false) {
			unset($this->_headers[$normalized_name]);

		// Else, set the header
		} else {
			// Header names are stored lowercase internally.
			if (is_string($_value)) {
				$_value = trim($_value);
			}
			$this->_headers[$normalized_name] = $_value;
			$this->Adapter->setHeaders($normalized_name, $_value);
		}

		return $this;
	}

	/**
	 * Fetches a single Header
	 * @param string $_key
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string | mixed | bool the header value if $_Key is found and 'false' otherwise
	 */
	public function GetHeader($_key)
	{
		if ( ! $this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_key = strtolower($_key);

		if ( array_key_exists($key, $this->_headers)) {
			return $this->_headers[$_key];
		}
		return false;
	}

	/**
	 * Set the GET & POST params
	 * Can take simple string as well as array to parse
	 * Used by SetParameterPost & SetParameterGet for setting up the params
	 * @param string $_type HTTPRequest Method e.g. GET, POST
	 * @param string $_name
	 * @param string $_value
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_HTTPClient on success and 'false' otherwise
	 * @throws SWIFT_Exception if $_Type is not a valid method
	 */
	protected function _setParameter($_type, $_name, $_value = null)
	{
		if ( ! $this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( ! $this->isValidMethod($_type)) {
			throw new SWIFT_Exception('Invalid Method  ' . $_type . SWIFT_INVALIDDATA);
		}

		if ( _is_array($_name))  {
			foreach ($_name as $_key => $_value)  {
				$this->_setParameter($_type, $_key, $_value);
			}
		}

		$_type = strtoupper($_type);

		switch ($_type)  {
			case 'GET':
				$this->_paramsGet[$_name] = $_value;
				break;

			case 'POST':
				$this->_paramsPost[$_name] = $_value;
				break;
		}
		return $this;
	}

	/**
	 * Public Wrapper function on top of _setParameter
	 * Sets the POST parameters
	 * Accepts simple string as well as associative arrays
	 * @param mixed $_name
	 * @param string $_value
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'true' on success & 'false' otherwise
	 */
	public function SetParameterPost($_name, $_value = null)
	{
		if ( ! $this->GetIsClassLoaded() ) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( _is_array($_name) ) {
			foreach ($_name as $key => $value) {
				$this->_setParameter(self::POST, $key, $value);
			}
		}
		$this->_setParameter(self::POST, $_name, $_value);
	}

	/**
	 * Public Wrapper function on top of _setParameter
	 * Sets the GET parameters
	 * Accepts simple string as well as associative arrays
	 * @param mixed $_name name of the param
	 * @param string $_value value of the param
	 * @author Abhinav Kumar <abhinav
	 */
	public function SetParameterGet($_name, $_value = null)
	{
		if ( ! $this->GetIsClassLoaded())  {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( _is_array($_name) ) {
			foreach ($_name as $_key => $_val) {
				$this->_setParameter(self::GET, $_key, $_val);
			}
		}
		else
			$this->_setParameter(self::GET, $_name, $_value);
	}

	/**
	 * Fetches the last error
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string the error message
	 */
	public function GetError()
	{
		if ( ! $this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		return $this->_error;
	}

	/**
	 * Makes an HTTP Request
	 * 
	 * @param string $_method
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean | \SWIFT_HTTPResponse Response on success or 'false' otherwise
	 * @throws SWIFT_Exception
	 */
	public function Request($_method = self::GET, $_connectionTimeout = 10)
	{
		if ( ! $this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if ( null === $this->Adapter || ! is_a($this->Adapter, 'SWIFT_HTTPAdapter_Interface') )  {
			throw new SWIFT_Exception('HTTP Adapter not initialized yet or not compatible');
		}

		if ( null === $this->_URI) {
			throw new SWIFT_Exception('No URI specified');
		}

		if ( ! $this->isValidMethod($_method) ) {
			throw new SWIFT_Exception('Invalid Request Type');
		}

		$_params = array();

		switch ($_method) {
			case self::GET:
			case self::DELETE:
				$_params = $this->_paramsGet;
				break;

			case self::POST;
				$_params = $this->_paramsPost;
		}

		if ($_connectionTimeout) {
			$this->Adapter->AddOption('CONNECTTIMEOUT', $_connectionTimeout);
			$this->Adapter->AddOption('TIMEOUT', $_connectionTimeout);
		}

		$_Response = call_user_func_array(array($this->Adapter, $_method), array($this->_URI, $_params));

		if ( FALSE !== $_Response) {
			return new SWIFT_HTTPResponse ($_Response);
		}

		//if your program reaches this line . . .it means something is wrong
		$this->_isError = true;
		$this->_error   = $this->Adapter->getError();

		return false;
	}
}