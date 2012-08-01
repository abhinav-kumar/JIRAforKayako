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
 * The Issue History Controller
 * Handles all the issue(s) logged per ticet
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class Controller_IssueHistory extends Controller_staff
{
	// Core Constants
	const MENU_ID		= 111;
	const NAVIGATION_ID	= 1;

	/**
	 * Constructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __construct()
	{
		parent::__construct();

		$this->Load->Library('JIRA:JIRABridge', false, false, 'jira');
		$this->Load->Library('JIRA:JIRAComment', false, false);

		return true;
	}

	/**
	 * The default destructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __destruct()
	{
		parent::__destruct();

		return true;
	}

	/**
	 * Fetches bugs history per client
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_queryString The Query String encoded with base64_encode
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded or If Invalid Data is Provided
	 */
	public function History($_ticketID)
	{
		$_SWIFT			= SWIFT::GetInstance();
		$_JIRABridge	= SWIFT_JIRABridge::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		} else if (!$_JIRABridge || !$_JIRABridge instanceof SWIFT_JIRABridge || !$_JIRABridge->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		$_ticketID = (int) $_ticketID;

		$_issuesContainer = $_JIRABridge->GetIssuesBy('ticketid', $_ticketID);

		if (_is_array($_issuesContainer)) {
			$this->View->RenderHistoryTab($_issuesContainer);

		} else {
			echo $this->Language->Get('jira_noissuefound');
		}

		return true;
	}
}