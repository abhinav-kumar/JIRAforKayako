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
 * Base Interface to be implemented to all HTTP Adapters
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
interface SWIFT_HTTPAdapter_Interface {
	/**
	 * Makes an HTTP GET Request
	 */
	public function Get($_URL, $_vars = array());

	/**
	 * Makes an HTTP POST Request
	 */
	public function Post($_URL, $_vars = array());

	/**
	 * Makes an HTTP PUT Request
	 */
	public function Put($_URL, $_vars = array());

	/**
	 * Makes an HTTP DELETE Request
	 */
	public function Delete($_URL, $_vars = array());

	/**
	 * Makes an HTTP request based on the specified $_Method
	 * to an $_URL with an optional array of string of $_vars
	 *
	 * @param string $_Method
	 * @param string $_URL
	 * @param array $_vars
	 * @return \SWIFT_HTTPResponse on success or 'false' otherwise
	 */
	public function Request($_method, $_URL, $_vars = array());
}