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


/**
 * HTTPResponse Class
 * Handles and abstracts the HTTP response returned from the HTTP Requests
 */
class SWIFT_HTTPResponse extends SWIFT_HTTPBase
{
	/**
     * The HTTP version (1.0, 1.1)
     *
     * @var string
     */
    protected $_Version;

    /**
     * The HTTP response code
     *
     * @var int
     */
    protected $_ResponseCode;

    /**
     * The HTTP response code as string
     * (e.g. 'Not Found' for 404 or 'Internal Server Error' for 500)
     *
     * @var string
     */
    protected $_Message;

    /**
     * The HTTP response Type as string
     * (e.g. Application/json)
     *
     * @var string
     */
    protected $_responseType;

    /**
     * The HTTP response headers array
     *
     * @var array
     */
    protected $_Headers = array();

    /**
     * The HTTP response body
     *
     * @var string
     */
    protected $_Body;

	/**
	 * The RAW HTTP Response
	 * Could be used for custom parsing
	 * @var string
	 */
	protected $_RawData;

	/**
	 * The ContentType of the returned response
	 * e.g. JSON, XML etc.
	 * @var type
	 */
	protected $_ContentType;

	/**
	 * Stores the last error
	 * @var string
	 */
	protected $_Error = 'No last error';


	public function __construct($_Response)
	{
		parent::__construct();
		if ( !is_string($_Response) || $_Response == '') {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			return false;
		}
		$this->_RawData = $_Response;
		$this->ParseRawData($this->_RawData);

		if ($this->getHeader('Content-Type')) {
			$this->_responseType = $this->getHeader('Content-Type');
		}

		return TRUE;
	}


	private function ParseRawData($_RawData)
	{
		/**
		 * Try to fetch & set the HTTP Version
		 * If no version is read, it defaults to 1.0
		 */
		preg_match("|^HTTP/([\d\.x]+) \d+|", $_RawData, $m);

        if (isset($m[1])) {
            $this->_Version = $m[1];
        } else {
            $this->_Version = SWIFT_HTTPBase::HTTP_OLD;
        }

		/**
		* Extract the HTTP response code from a response
		* If no message is read, it defaults to 0
		*/
		preg_match("|^HTTP/[\d\.x]+ (\d+)|", $_RawData, $m);

        if (isset($m[1])) {
            $this->_ResponseCode =  (int) $m[1];
        } else {
            $this->_ResponseCode = 0;
        }

		/**
		* Extract the HTTP message from a response
		* If no message is read, it defaults to 'Service Unavailable'
		*/

        preg_match("|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $_RawData, $m);

        if (isset($m[1]))
		{
            $this->_Message = $m[1];
        }
		else
		{
            $this->_Message = 'Service Unavailable';
        }

		/**
		 * Extracts the Body(content) from a response
		 * If no body is read, it defaults to an empty string
		 */
		$parts = preg_split('|(?:\r?\n){2}|m', $_RawData, 2);
        if (isset($parts[1]))
		{
            $this->_Body = $parts[1];
        }
        else
		{
			$this->_Body = '';
		}

		if($this->_ResponseCode == 100)
			$this->ParseRawData ($this->_Body);

	}

	/**
	 * Gets the HTTP Version
	 * e.g. 1.0, 1.1
	 * @return string
	 */
	public function getVersion()
	{
		return $this->_Version;
	}

	/**
	 * Gets the Response code
	 * e.g. 200, 404, 500
	 * @return string
	 */
	public function getResponseCode()
	{
		return $this->_ResponseCode;
	}

	/**
	 * Gets the Response Type
	 * e.g. json | xml
	 * @return string
	 */
	public function getResponseType()
	{
		return $this->_responseType;
	}

	/**
     * Get a specific header as string, or null if it is not set
     *
     * @param string$header
     * @return string|array|null
     */
    public function getHeader($header)
    {
        $header = ucwords(strtolower($header));
        if (! is_string($header) || ! isset($this->_Headers[$header])) return null;

        return $this->_Headers[$header];
    }

	/**
	 * Gets the Headers
	 * @return string
	 */
	public function getHeaders()
	{
		return $this->_Headers;
	}

	/**
	 * Gets the Response Body
	 * @return string
	 */
	public function getBody()
	{
		$body = '';

        // Decode the body if it was transfer-encoded
        switch (strtolower($this->getHeader('transfer-encoding'))) {

            // Handle chunked body
            case 'chunked':
                $body = $this->decodeChunkedBody($this->body);
                break;

            // No transfer encoding, or unknown encoding extension:
            // return body as is
            default:
                $body = $this->_Body;
                break;
        }

        // Decode any content-encoding (gzip or deflate) if needed
        switch (strtolower($this->getHeader('content-encoding'))) {

            // Handle gzip encoding
            case 'gzip':
                $body = self::decodeGzip($body);
                break;

            // Handle deflate encoding
            case 'deflate':
                $body = self::decodeDeflate($body);
                break;

            default:
                break;
        }

        return $body;
		return $this->_Body;
	}

	public function getRawData()
	{
		return $this->_RawData;
	}

	public function GetContentType()
	{
		return $this->_ContentType;
	}

	/**
     * Decode a "chunked" transfer-encoded body and return the decoded text
     *
     * @param string $body
     * @return string
     */
    public function decodeChunkedBody($body)
    {
        $decBody = '';

        // If mbstring overloads substr and strlen functions, we have to
        // override it's internal encoding
        if (function_exists('mb_internal_encoding') &&
           ((int) ini_get('mbstring.func_overload')) & 2) {

            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        while (trim($body)) {
            if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m))
			{
				throw new SWIFT_Exception(SWIFT_INVALIDDATA);
            }

            $length = hexdec(trim($m[1]));
            $cut = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
            $body = substr($body, $cut + $length + 2);
        }

        if (isset($mbIntEnc)) {
            mb_internal_encoding($mbIntEnc);
        }

        return $decBody;
    }

	/**
	 * Check whether the response is error
	 *
	 * @return boolean
	 */
	public function IsError()
	{
		$_ResponseCode = (int) $this->getResponseCode();
		$_ResponseCode /= 100;
		if($_ResponseCode == 4 || $_ResponseCode == 5)
		{
			//Something's wrong
			$this->_errorMsg = $this->_Message;
			return true;
		}
		return false;
	}

	/**
     * Check whether the response in successful
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        $_ResponseCode = (int) $this->getResponseCode();
		$_ResponseCode /= 100;
		$_ResponseCode = floor($_ResponseCode);
		if($_ResponseCode == 1 || $_ResponseCode == 2)
		{
            return true;
        }

		$this->_Error = $this->_ResponseCode . ' - ' . $this->_Message;
        return false;
    }

    /**
     * Check whether the response is a redirection
     *
     * @return boolean
     */
    public function isRedirect()
    {
        $_ResponseCode = (int) $this->getResponseCode();
		$_ResponseCode /= 100;
		$_ResponseCode = floor($_ResponseCode);
		if($_ResponseCode == 3)
		{
            return true;
        }

        return false;
    }

	public function GetErrorMessage()
	{
		return $this->_Error;
	}

}