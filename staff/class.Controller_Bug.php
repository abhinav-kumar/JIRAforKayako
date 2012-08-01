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
 * The Bug Controller
 * Handles all the bug(s) logged per ticet/user
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class Controller_Bug extends Controller_staff
{
	// Core Constants

	const MENU_ID = 2;
	const NAVIGATION_ID = 1;

	/**
	 * Constructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __construct()
	{
		parent::__construct();

		$_SWIFT = SWIFT::GetInstance();

		$this->Load->LoadModel('Ticket:Ticket', APP_TICKETS);
		$this->Load->LoadApp('AuditLog:TicketAuditLog', APP_TICKETS);

		$this->Load->Library('JIRA:JIRABridge', false, false);
		$this->Load->Library('JIRA:JIRAComment', false, false);

		$this->Language->Load('jira');
		$_SWIFT->Language->LoadApp('jira', 'jira');

		SWIFT_Ticket::LoadLanguageTable();

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
	 * Creates & Renders the 'Export to JIRA' form
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param int $_ticketID
	 * @return boolean 'true' on success & 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function CreateIssue($_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' ' . SWIFT_CLASSNOTLOADED);
		}

		$_ticketID = (int) $_ticketID;

		$_SWIFT = SWIFT::GetInstance();
		$_SWIFT->Language->LoadApp('jira', 'jira');

		$this->UserInterface->Header($this->Language->Get('settings_bj') . ' -  ' . $this->Language->Get('exportToJIRA'), self::MENU_ID, self::NAVIGATION_ID);

		$this->View->RenderExportForm($_ticketID);

		$this->UserInterface->Footer();
		return true;
	}

	/**
	 * Creates & Renders the 'Link to JIRA' form
	 * Used when you want to link the current ticket with an existing JIRA issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param int $_ticketID
	 * @return boolean 'true' on success & 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function LinkIssue($_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' ' . SWIFT_CLASSNOTLOADED);
		}

		$_ticketID = (int) $_ticketID;

		$_SWIFT = SWIFT::GetInstance();
		$_SWIFT->Language->LoadApp('jira', 'jira');

		$this->UserInterface->Header($this->Language->Get('settings_bj') . ' -  ' . $this->Language->Get('exportToJIRA'), self::MENU_ID, self::NAVIGATION_ID);

		$this->View->RenderLinkIssueForm($_ticketID);

		$this->UserInterface->Footer();
		return true;
	}

	/**
	 * Process the Export to JIRA form on submission
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'true' on success and 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function ProcessIssueForm()
	{
		$_JIRABridge = SWIFT_JIRABridge::GetInstance();
		$_SWIFT = SWIFT::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}


		if (array_key_exists('ticketId', $_POST)) {
			$_ticketID = $_POST['ticketId'];
		} else {
			throw new SWIFT_Exception('Ticket ID' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('project', $_POST)) {
			$_projectKey = $_POST['project'];
		} else {
			throw new SWIFT_Exception('Project Key' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('issueType', $_POST)) {
			$_issueType = $_POST['issueType'];
		} else {
			throw new SWIFT_Exception('Issue Type' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('summary', $_POST)) {
			$_summary = $_POST['summary'];
		} else {
			throw new SWIFT_Exception('Summary' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('description', $_POST)) {
			$_description = $_POST['description'];
		} else {
			throw new SWIFT_Exception('Description' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('priority', $_POST)) {
			$_priority = $_POST['priority'];
		} else {
			throw new SWIFT_Exception('Priority' . $this->Language->Get('jira_noempty'));
		}


		if (isset($_POST['summary'])) {


			if (!$_JIRABridge) {
				if ($_SWIFT->Settings->Get('bj_isenabled')) {
					SWIFT::Alert($this->Language->Get('jira_error'), $this->Language->Get('connection_error') . $_SWIFT->Settings->Get('bj_jiraurl'));
				}
				return false;
			}

			$_Data = array(
				'kayakoTicketId'	=> $_ticketID,
				'project'		=> $_projectKey,
				'issueType'		=> $_issueType,
				'summary'		=> $_summary,
				'description'		=> $_description,
				'priority'		=> $_priority
			);

			if (array_key_exists('securityLevel', $_POST) && $_POST['securityLevel']) {
				$_Data['security'] = $_POST['securityLevel'];
			}

			$_JIRAIssue = $_JIRABridge->CreateIssue($_Data);

			if ($_JIRAIssue !== false) {
				$_SWIFT->Language->LoadApp('jira', 'jira');

				$_SWIFT_TicketObject = SWIFT_Ticket::GetObjectOnID($_JIRAIssue->GetKayakoTicketId());

				SWIFT::Notify(SWIFT::NOTIFICATION_INFO, $this->Language->Get('exportedToJIRA'));

				//SWIFT_TicketAuditLog::Create($_SWIFT_TicketObject, null, SWIFT_TicketAuditLog::ACTION_UPDATESTATUS, $this->Language->Get('exportedToJIRA') . ' - ' . $_JIRAIssue->GetKey(), SWIFT_TicketAuditLog::VALUE_NONE, 0, '', 0, '');
				SWIFT_TicketAuditLog::Create($_SWIFT_TicketObject, null, SWIFT_TicketAuditLog::CREATOR_STAFF, $_SWIFT->Staff->GetStaffID(), $_SWIFT->Staff->GetProperty('fullname'), SWIFT_TicketAuditLog::ACTION_UPDATESTATUS, $this->Language->Get('exportedToJIRA') . ' - ' . $_JIRAIssue->GetKey(), SWIFT_TicketAuditLog::VALUE_STATUS);
			} else {
				SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, strip_tags($this->Language->Get('jira_error') . $_JIRABridge->GetErrorMessage()));
			}

			$this->Load->Controller('Ticket', APP_TICKETS)->Load->Method('View', $_POST['jira_ticketid'], $_POST['jira_listtype'], $_POST['jira_departmentid'], $_POST['jira_ticketstatusid'], $_POST['jira_tickettypeid']);

			return true;
		}
		return false;
	}

	/**
	 * Process the Export to JIRA form on submission
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'true' on success and 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function ProcessLinkIssueForm()
	{
		$_JIRABridge = SWIFT_JIRABridge::GetInstance();
		$_SWIFT = SWIFT::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
		}


		if (array_key_exists('jira_issue_id', $_POST) && $_POST['jira_issue_id'] != '') {
			$_JIRAIssueID = $_POST['jira_issue_id'];
		} else {
			throw new SWIFT_Exception('JIRA Issue ID' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('ticketId', $_POST)) {
			$_ticketID = $_POST['ticketId'];
		} else {
			throw new SWIFT_Exception('Ticket ID' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('ticketkey', $_POST)) {
			$_ticketKey = $_POST['ticketkey'];
		} else {
			throw new SWIFT_Exception('Ticket Key' . $this->Language->Get('jira_noempty'));
		}

		if (array_key_exists('description', $_POST)) {
			$_description = $_POST['description'];
		} else {
			throw new SWIFT_Exception('Description' . $this->Language->Get('jira_noempty'));
		}

		if (!$_JIRABridge->IsIssueValid($_JIRAIssueID)) {
			$_JIRABridge->SetErrorMessage($this->Language->Get('jira_issueinvalid'));

			SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, $this->Language->Get('jira_issueinvalid'));

			$this->Load->Controller('Ticket', APP_TICKETS)->Load->Method('View', $_POST['jira_ticketid'], $_POST['jira_listtype'], $_POST['jira_departmentid'], $_POST['jira_ticketstatusid'], $_POST['jira_tickettypeid']);

			return true;
		}

		$_linked = $_JIRABridge->LinkIssue($_ticketID, $_JIRAIssueID, array(
			'description'	=> $_description
		));

		if ($_linked) {
			SWIFT::Notify(SWIFT::NOTIFICATION_INFO, $this->Language->Get('jira_linkedtojira'));
			SWIFT_TicketAuditLog::Create($_SWIFT_TicketObject, null, SWIFT_TicketAuditLog::ACTION_UPDATESTATUS, $this->Language->Get('jira_linkedtojira') . ' - ' . $_JIRAIssueID, SWIFT_TicketAuditLog::VALUE_NONE, 0, '', 0, '');

			$_JIRAIssue = $_JIRABridge->Get($_JIRAIssueID);
			if ($_JIRAIssue && $_JIRAIssue instanceof SWIFT_JIRAIssue) {
				$_JIRAComment = new SWIFT_JIRAComment();
				$_JIRAComment->SetBody(sprintf($this->Language->Get('jira_linkedtojira_comment'), $_ticketKey, $_SWIFT->Staff->GetProperty('fullname')));

				$_JIRAIssue->PostComment($_JIRAComment);
			}
		}

		$this->Load->Controller('Ticket', APP_TICKETS)->Load->Method('View', $_POST['jira_ticketid'], $_POST['jira_listtype'], $_POST['jira_departmentid'], $_POST['jira_ticketstatusid'], $_POST['jira_tickettypeid']);

		return true;
	}

	/**
	 * Creates & Renders the 'Post comments to JIRA' form
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_issueKey The issue key where the comments is to be posted
	 * @return boolean
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function PostCommentForm($_issueKey)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
			return false;
		}

		if (empty($_issueKey) || !is_string($_issueKey))
			throw new SWIFT_Exception('IssueKey ' . SWIFT_INVALIDDATA);

		$this->UserInterface->Start(get_class($this), '/JIRA/Bug/ProcessCommentForm', SWIFT_UserInterface::MODE_INSERT, true);
		$this->UserInterface->Header($this->Language->Get('tabjira') . ' - ' . $this->Language->Get('postJIRAComment'), self::MENU_ID, self::NAVIGATION_ID);

		$this->View->RenderCommentForm($_issueKey);

		$this->UserInterface->End();
		$this->UserInterface->Footer();
		return true;
	}

	/**
	 * Process the 'post comments to JIRA' form on submission
	 * Checks for validity and then posts the comment to JIRA web service
	 * using REST API
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'true' on success and 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function ProcessCommentForm()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
			return false;
		}

		$_SWIFT		= SWIFT::GetInstance();
		$_issueKey	= null;
		$_rawComment	= null;
		$_comment	= null;

		if (array_key_exists('issueKey', $_POST) && $_POST['issueKey'] != '') {
			$_issueKey = $_POST['issueKey'];
		}

		if (array_key_exists('comment', $_POST) && $_POST['comment'] != '') {
			$_comment = $_rawComment = $_POST['comment'];
		}

		if (array_key_exists('visibility', $_POST)) {
			$_visibility = $_POST['visibility'];
		}

		if ($_issueKey && $_comment) {
			$this->Load->Library('JIRA:JIRABridge', false, false, 'jira');

			$_JIRABridge = SWIFT_JIRABridge::GetInstance();

			if (!$_JIRABridge || !$_JIRABridge instanceof SWIFT_JIRABridge || !$_JIRABridge->GetIsClassLoaded()) {
				$this->UserInterface->DisplayAlert($this->Language->Get('jira_error'), $this->Language->Get('connection_error') . $_SWIFT->Settings->Get('bj_jiraurl'));
				return false;
			}

			$_staffDataStore = $_SWIFT->Staff->GetDataStore();
			$_issueCreator = $_staffDataStore['grouptitle'];

			$_staff = $_SWIFT->Staff->GetProperty('fullname') . ' (' . $_issueCreator . ')' . PHP_EOL;
			$_comment = $_staff . $_comment;

			$_JIRAIssue = $_JIRABridge->GetIssueBy('issuekey', $_issueKey);

			if ($_JIRAIssue && $_JIRAIssue instanceof SWIFT_JIRAIssue && $_JIRAIssue->GetIsClassLoaded()) {
				$_JIRAComment = new SWIFT_JIRAComment();
				$_JIRAComment->SetBody($_comment);

				if (isset($_visibility)) {
					$_visibilityArray = array(
						'type'		=> 'role',
						'value'	=> $_visibility,
					);
					$_JIRAComment->SetVisibility($_visibilityArray);
				}

				$_commentPosted = $_JIRAIssue->PostComment($_JIRAComment);

				if ($_commentPosted) {
					$_SWIFT_TicketObject = SWIFT_Ticket::GetObjectOnID($_JIRAIssue->GetKayakoTicketId());

					// Did the object load up?
					if (!$_SWIFT_TicketObject || !$_SWIFT_TicketObject instanceof SWIFT_Ticket || !$_SWIFT_TicketObject->GetIsClassLoaded()) {
						throw new SWIFT_Exception('Ticket Object ' . SWIFT_INVALIDDATA);
					}
					SWIFT_TicketAuditLog::Create($this->Language->Get('jira_comment_posted'), false, SWIFT_Log::TYPE_OK);

					SWIFT::Notify(SWIFT::NOTIFICATION_INFO, $this->Language->Get('jira_comment_posted'));
				} else {
					SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, $this->Language->Get('jira_comment_notposted'));
				}
			} else {
				SWIFT::Notify(SWIFT::NOTIFICATION_ERROR, $this->Language->Get('jira_noissuefound'));
			}
		}
		$this->Load
			->Controller('Ticket', APP_TICKETS)
			->Load
			->Method('View', $_POST['jira_ticketid'], $_POST['jira_listtype'], $_POST['jira_departmentid'], $_POST['jira_ticketstatusid'], $_POST['jira_tickettypeid']);
	}

	/**
	 * Unlinks a JIRA issue with a Kayako ticket
	 *
	 * @param string $_issueKey The issue key to be unlinked
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success and 'FALSE' otherwise
	 * @throws SWIFT_Exception if class is not loaded or no issue key is provided
	 */
	public function Unlink($_issueKey, $_ticketID)
	{
		$_SWIFT			= SWIFT::GetInstance();
		$_JIRABridge	= SWIFT_JIRABridge::GetInstance();
		$_response		= array();

		if (!$this->GetIsClassLoaded())
			throw new SWIFT_Exception(__CLASS__ . ' - ' . SWIFT_CLASSNOTLOADED);

		if (!empty($_issueKey)) {
			$this->Load->Library('JIRA:JIRABridge', false, false, 'jira');
			$this->Load->Library('JIRA:JIRAIssue', false, false, 'jira');
			$this->Language->LoadApp('jira', 'jira');


			if ($_JIRABridge) {
				$_JIRAIssue = $_JIRABridge->GetIssueBy('issuekey', $_issueKey);
				if ($_JIRAIssue && $_JIRAIssue instanceof SWIFT_JIRAIssue && $_JIRAIssue->GetIsClassLoaded()) {
					$_unlinked = $_JIRABridge->UnlinkIssue($_issueKey, $_ticketID);
					if ($_unlinked) {
						$_SWIFT_TicketObject = SWIFT_Ticket::GetObjectOnID((int) $_ticketID);
						if ($_SWIFT_TicketObject && $_SWIFT_TicketObject instanceof SWIFT_Ticket && $_SWIFT_TicketObject->GetIsClassLoaded()) {
							SWIFT_TicketAuditLog::Create($_SWIFT_TicketObject, null, SWIFT_TicketAuditLog::CREATOR_STAFF, $_SWIFT->Staff->GetStaffID(), $_SWIFT->Staff->GetProperty('fullname'), SWIFT_TicketAuditLog::ACTION_UPDATESTATUS, $this->Language->Get('unlinkedFromJIRA') . ' - ' . $_JIRAIssue->GetKey(), SWIFT_TicketAuditLog::VALUE_STATUS);
						}
						$_response['code']		= 200;
						$_response['message']	= 'success';
					}
					else {
						$_response['code']		= 500;
						$_response['message']	= $_JIRABridge->GetErrorMessage();
					}
				} else {
					$_response['code']		= 500;
					$_response['message']	= 'noissuekey';
				}
			}
			else {
				$_response['code']		= 500;
				$_response['message']	= $this->Language->Get('jira_error');
			}
		}
		else {
			$_response['code']		= 500;
			$_response['message']	= 'noissuekey';
		}
		echo json_encode($_response);
		return TRUE;

	}

	/**
	 *
	 *
	 * @author Varun Shoor
	 * @param string $_PARAM PARAMDESC
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetIssueTypesByProject($_projectKey)
	{
		header('content-type: application/json');

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('projectKey ' . SWIFT_INVALIDDATA);
		}

		$_JIRABridge = SWIFT_JIRABridge::GetInstance();

		$_issueTypes = $_JIRABridge->GetIssueTypesByProject($_projectKey);

		if ($_issueTypes && _is_array($_issueTypes)) {
			$_issueTypeSelect = array();
			foreach ($_issueTypes as $_issueType) {
				$_issueTypeSelect[]	= array(
					'value'	=> $_issueType->id,
					'title'		=> $_issueType->name
				);
			}
			echo json_encode($_issueTypeSelect);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 *
	 *
	 * @author Varun Shoor
	 * @param string $_PARAM PARAMDESC
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function IsIssueValid($_issueKey)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		$_response = array(
			'code'	=> 400,
			'response'	=> 'noissuekey'
		);

		if (empty($_issueKey)) {
			echo json_encode($_response);
			return FALSE;
		}

		$_JIRABridge = SWIFT_JIRABridge::GetInstance();
		if ($_JIRABridge && $_JIRABridge instanceof SWIFT_JIRABridge && $_JIRABridge->GetIsClassLoaded()) {
			$_issueIsValid = $_JIRABridge->IsIssueValid($_issueKey);
			if ($_issueIsValid) {
				$_response['code']		= 200;
				$_response['response']	= 'success';

				echo json_encode($_response);
				return TRUE;
			} else {
				$_response = array(
					'code'	=> 404,
					'response'	=> 'issue ' . $_issueKey . ' not found'
				);
				echo json_encode($_response);
				return TRUE;
			}
		}
		else {
			return FALSE;
		}
		return true;
	}

	/**
	 *
	 *
	 * @author Varun Shoor
	 * @param string $_PARAM PARAMDESC
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetSecurityLevel($_projectKey, $_issueType = '')
	{
		header('content-type: application/json');

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key ' . $this->Language->Get('jira_noempty'));
		}

		$_SWIFT = SWIFT::GetInstance();

		$_JIRABridge = SWIFT_JIRABridge::GetInstance();
		$_securityLevelContainer = array();

		if ($_JIRABridge && $_JIRABridge instanceof SWIFT_JIRABridge && $_JIRABridge->GetIsClassLoaded()) {
			$_ProjectSecurityLevels = $_JIRABridge->GetSecurityLevelsByProject($_projectKey, $_issueType);

			if ($_ProjectSecurityLevels && _is_array($_ProjectSecurityLevels)) {
				foreach ($_ProjectSecurityLevels as $_value => $_title) {
					$_securityLevelContainer[] = array(
						'title'		=> $_title,
						'value'	=> $_value
					);
				}
				echo json_encode(array(
					'code'	=> 200,
					'data'		=> $_securityLevelContainer
				));
			} else {
				echo json_encode(array(
					'code'	=> 204,
					'data'		=> 'No security settings for this project'
				));
				return TRUE;
			}


		}
		return FALSE;
	}

	/**
	 * Deletes a JIRA Issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_queryString The Query String encoded with base64_encode
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded or If Invalid Data is Provided
	 */
	public function Delete($_issueID)
	{
		/**
		 * Not implemented yet
		 */
	}

}