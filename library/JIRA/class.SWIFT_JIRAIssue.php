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
 * Main class for JIRA Issue
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_JIRAIssue extends SWIFT_Library
{
	// Core constants
	const TYPE_BUG			= 1;
	const TYPE_FEATURE		= 2;
	const TYPE_TASK			= 3;
	const TYPE_IMPROVEMENT	= 4;

	/**
	 * Sub task is not supported at the moment
	 */
	//const TYPE_SUBTASK		= 5;

	/**
	 * Issue Priority
	 */
	const PRIORITY_BLOCKER	= 1;
	const PRIORITY_CRITICAL	= 2;
	const PRIORITY_MAJOR	= 3;
	const PRIORITY_MINOR	= 4;
	const PRIORITY_TRIVIAL	= 5;

	/**
	 * Issue statuses
	 */
	const STATUS_OPEN		= 1;
	const STATUS_INPROGRESS = 3;
	const STATUS_REOPENED   = 4;
	const STATUS_RESOLVED   = 5;
	const STATUS_CLOSED     = 6;

	/**
	 * Issue Id
	 * @var string
	 */
	private $_ID;

	/**
	 * Issue Key
	 * @var string
	 */
	private $_key;

	/**
	 * Corresponding Kayako Ticket ID
	 * @var int
	 */
	private $_kayakoTicketId;

	/**
	 * Project Key under which the issue is logged
	 * @var string
	 */
	private $_project;

	/**
	 * Current status of the issue
	 * @var type
	 */
	private $_status = self::STATUS_OPEN;

	/**
	 * Number of comments the issue has
	 * @var int
	 */
	private $_commentsCount = 0;

	/**
	 * Type of Issue
	 * e.g \SWIFT_JIRAIssue::TYPE_BUG
	 * @var int
	 */
	private $_issueType;

	/**
	 * Issue Summary
	 * @var string
	 */
	private $_summary;

	/**
	 * Issue Priority
	 * e.g \SWIFT_JIRAIssue::PRIORITY_BLOCKER
	 * @var string
	 */
	private $_priority;

	/**
	 * Not Implemented Yet
	 * @var type

	  private $_dueDate;
	 */
	private $_assignee;

	/**
	 * Issue Reporter
	 * @var string
	 */
	private $_reporter;

	/**
	 * Issue Description
	 * @var string
	 */
	private $_description;

	/**
	 * Issue label(s)
	 * @var string
	 */
	private $_labels;

	/**
	 * Date/time when the issue was created
	 * @var int
	 */
	private $_created;

	/**
	 * Date/time when the issue was updated
	 * @var type
	 */
	private $_updated;

	/**
	 * Not implemented yet
	 * @var type

	  private $_environment;

	  private $_originalEstimate;

	  private $_remainingEstimate;

	  private $_attachment;
	 */

	/**
	 * The default constructor
	 * Calls the Set method for initialization
	 *
	 * @param mixed $_data an associative array of issue parameters
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue
	 */
	public function __construct($_data)
	{
		parent::__construct();
		return $this->Set($_data);
	}

	/**
	 * The default destructor
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'true' on success and 'false' otherwise
	 */
	public function __destruct()
	{
		parent::__destruct();
		return true;
	}

	/**
	 * Takes an associative array with issue params & initializes
	 *
	 * @param mixed $_data An associative array issue
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 * @throws SWIFT_Exception if $_data is not an array or class is not loaded
	 */
	protected function CreateFromArray($_data)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' ' . SWIFT_CLASSNOTLOADED);
		}

		/**
		 * Check if $data is an array
		 * Throw a SWIFT_Exception & return false on failure
		 */
		if (!_is_array($_data)) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			return false;
		}

		/**
		 * Check & set the Project
		 */
		if (array_key_exists('project', $_data)) {
			$this->_project = $_data['project'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Project ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Issue Type
		 */
		if (array_key_exists('issueType', $_data)) {
			$this->_issueType = $_data['issueType'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_IssueType ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Summary
		 */
		if (array_key_exists('summary', $_data)) {
			$this->_summary = $_data['summary'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Summary ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Priority
		 */
		if (array_key_exists('priority', $_data)) {
			$this->_priority = $_data['priority'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Priority ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Due Date
		 */
		if (array_key_exists('dueDate', $_data)) {
			$this->_dueDate = $_data['dueDate'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_DueDate ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Assignee
		 */
		if (array_key_exists('assignee', $_data)) {
			$this->_assignee = $_data['assignee'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Assignee ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Reporter
		 */
		if (array_key_exists('reporter', $_data)) {
			$this->_reporter = $_data['reporter'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Reporter ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Environment
		 */
		if (array_key_exists('environment', $_data)) {
			$this->_environment = $_data['environment'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Environment ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Description
		 */
		if (array_key_exists('description', $_data)) {
			$this->_description = $_data['description'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Description ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Original Estimate
		 */
		if (array_key_exists('originalEstimate', $_data)) {
			$this->_originalEstimate = $_data['originalEstimate'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_OriginalEstimate ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Remaining Estimate
		 */
		if (array_key_exists('remainingEstimate', $_data)) {
			$this->_remainingEstimate = $_data['remainingEstimate'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_RemainingEstimate ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Attachment
		 */
		if (array_key_exists('attachment', $_data)) {
			$this->_attachment = $_data['attachment'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Attachment ' . SWIFT_INVALIDDATA);
		}

		/**
		 * Check & set the Labels
		 */
		if (array_key_exists('labels', $_data)) {
			$this->_labels = $_data['labels'];
		} else {
			throw new SWIFT_Exception('JIRAIssue::_Labels ' . SWIFT_INVALIDDATA);
		}
	}

	/**
	 * Sets the issue params
	 *
	 * @param mixed $_data
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue
	 */
	protected function Set($_data)
	{
		/**
		 * Set the required items
		 */
		$this->SetFromArray('id', $_data, true);
		$this->SetFromArray('key', $_data, true);
		$this->SetFromArray('project', $_data, true);
		$this->SetFromArray('issueType', $_data, true);
		$this->SetFromArray('summary', $_data, true);
		$this->SetFromArray('kayakoTicketId', $_data, true);

		/**
		 * Set the optional items
		 */
		$this->SetFromArray('created', $_data, false);
		$this->SetFromArray('updated', $_data, false);
		$this->SetFromArray('commentsCount', $_data, false);
		$this->SetFromArray('status', $_data, false);
		$this->SetFromArray('priority', $_data, false);
		$this->SetFromArray('dueDate', $_data, false);
		$this->SetFromArray('assignee', $_data, false);
		$this->SetFromArray('reporter', $_data, false);
		$this->SetFromArray('environment', $_data, false);
		$this->SetFromArray('description', $_data, false);
		$this->SetFromArray('originalEstimate', $_data, false);
		$this->SetFromArray('remainingEstimate', $_data, false);
		$this->SetFromArray('attachment', $_data, false);
		$this->SetFromArray('labels', $_data, false);

		return $this;
	}

	/**
	 * Does the actual initialization
	 * Checks if $_Data is an array & $_key is a key then reads and sets the value
	 * to the corresponding class attribute
	 *
	 * @param string $_key The key to read - This also relate to the class attribute
	 * @param mixed $_data The associative array containing all the keys
	 * @param bool $_isRequired whether mandatory or not. If set to 'true' will throw an \SWIFT_Exception if no value is passed
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 * @throws SWIFT_Exception
	 */
	protected function SetFromArray($_key, $_data, $_isRequired)
	{
		if (!_is_array($_data)) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			return false;
		}

		if (array_key_exists($_key, $_data)) {// && $_data[$_key] != '')
			$_Var = '_' . $_key;

			$this->$_Var = $_data[$_key];
			return true;
		}

		if ($_isRequired) {
			$_SWIFT = SWIFT::GetInstance();
			$_SWIFT->Language->LoadApp('jira', 'jira');
			throw new SWIFT_Exception(__CLASS__ . '::' . $_key . $_SWIFT->Language->Get('jira_noempty'));
		}
	}

	/**
	 * Fetches the issue ID
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int The current issue id
	 */
	public function GetId()
	{
		return $this->_ID;
	}

	/**
	 * Fetches the Issue Key
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string The current issue key
	 */
	public function GetKey()
	{
		return $this->_key;
	}

	/**
	 * Fetches the associated kayako ticket id
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int The associated kayako ticket id
	 */
	public function GetKayakoTicketID()
	{
		return $this->_kayakoTicketId;
	}

	/**
	 * Sets the associated kayako ticket id
	 *
	 * @param int $_kayakoTicketID The kayako ticket id
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_kayakoTicketID is not a valid int
	 */
	public function SetKayakoTicketID($_kayakoTicketID = 0)
	{
		$_kayakoTicketID = (int) $_kayakoTicketID;
		if ($_kayakoTicketID) {
			$this->_kayakoTicketId = $_kayakoTicketID;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the current project key
	 * e.g. 'TEST'
	 *
	 * @author Abhinav Kumar <abhinav.kumar.kayako.com>
	 * @return string The current project key
	 */
	public function GetProject()
	{
		return $this->_project;
	}

	/**
	 * Set the project key
	 *
	 * @param string $_project
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue
	 */
	public function SetProject($_project)
	{
		$this->_project = $_project;
		return $this;
	}

	/**
	 * Get the current status of the issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int The current issue status
	 */
	public function GetStatus()
	{
		return $this->_status;
	}

	/**
	 * Sets the current staus of the issue
	 *
	 * @param int $_status The issue status
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_status is not valid
	 */
	public function SetStatus($_status = self::STATUS_OPEN)
	{
		$_status = (int) $_status;
		if ($_status) {
			$this->_status = $_status;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the no of comments on the issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int
	 */
	public function GetCommentsCount()
	{
		return $this->_commentsCount;
	}

	/**
	 * Sets the number of comments
	 *
	 * @param int $_commentCount
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_commentCount is not a positive int
	 */
	public function SetCommentsCount($_commentCount = 0)
	{
		$_commentCount = (int) $_commentCount;

		if (is_numeric($_commentCount)) {
			$this->_commentsCount = $_commentCount;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the current issue type
	 * e.g. 1 => Bugs
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int The current issue type
	 */
	public function GetIssueType()
	{
		return $this->_issueType;
	}

	/**
	 * Set the issue type
	 *
	 * @param int $_issueType The issue type
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_issueType is not a string
	 */
	public function SetIssueType($_issueType)
	{
		$_issueType = (int) $_issueType;
		if ($_issueType) {
			$this->_issueType = $_issueType;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the current summary
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string The current issue summary
	 */
	public function GetSummary()
	{
		return $this->_summary;
	}

	/**
	 * Set the summary
	 *
	 * @param string $_summary
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_summary is not a string
	 */
	public function SetSummary($_summary)
	{
		if (is_string($_summary)) {
			$this->_summary = $_summary;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the current priority
	 *
	 * @author Abhinav Kumar <abhiinav.kumar@kayako.com>
	 * @return int The current issue priority
	 */
	public function GetPriority()
	{
		return $this->_priority;
	}

	/**
	 * Set the Priority
	 * e.g. SWIFT_JIRAIssue::PRIORITY_BLOCKER
	 *
	 * @param int $_priority The current priority id
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_priority is not valid
	 */
	public function SetPriority($_priority)
	{
		$_priority = (int) $_priority;
		if ($_priority) {
			$this->_priority = $_priority;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the assignee
	 * @return string
	 */
	public function GetAssignee()
	{
		return $this->_assignee;
	}

	/**
	 * Set the Assignee
	 * @param int $_assignee
	 * @return \SWIFT_JIRAIssue
	 */
	public function SetAssignee($_assignee)
	{
		$this->_assignee = $_assignee;
		return $this;
	}

	/**
	 * Get the reporter
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return int The current reporter id
	 */
	public function GetReporter()
	{
		return $this->_reporter;
	}

	/**
	 * Set the reporter
	 *
	 * @param string $_reporter The reporter id
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_reporter is not a string
	 */
	public function SetReporter($_reporter)
	{
		if (is_string($_reporter)) {
			$this->_reporter = $_reporter;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the description
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string the current description
	 */
	public function GetDescription()
	{
		return $this->_description;
	}

	/**
	 * Set the description
	 *
	 * @param string $_description
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_description is not a string
	 */
	public function SetDescription($_description)
	{
		if (is_string($_description)) {
			$this->_description = $_description;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the labels
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string The current label
	 */
	public function GetLabels()
	{
		return $this->_labels;
	}

	/**
	 * Set the Labels
	 *
	 * @param string $_labels The label
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_labels is not a string
	 */
	public function SetLabels($_labels)
	{
		if (is_string($_labels)) {
			$this->_labels = $_labels;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Get the Creation Datetime on the issue
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string
	 */
	public function GetCreated()
	{
		return $this->_created;
	}

	/**
	 * Sets the creation time of the Issue
	 *
	 * @param string $_created The issue creation time
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_created is not a string
	 */
	public function SetCreated($_created)
	{
		if (is_string($_created)) {
			$this->_created = $_created;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Gets the Creation Datetime on the issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string The issue updated time
	 */
	public function GetUpdated()
	{
		return $this->_updated;
	}

	/**
	 * Sets the updation time of the Issue
	 *
	 * @param string $_updated The time to set
	 * @author Abhianv Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success
	 * @throws SWIFT_Exception if $_updated is not a string
	 */
	public function SetUpdated($_updated)
	{
		if (is_string($_string)) {
			$this->_updated = $_updated;
			return $this;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
	}

	/**
	 * Checks if the JIRA Issue is still valid
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' if the issue is valid & 'FALSE' otherwise
	 */
	public function IsValid()
	{
		if (!$this->_ID)
			return false;

		$_JIRABridge = SWIFT_JIRABridge::GetInstance();

		if ($_JIRABridge == false)
			return false;

		return $_JIRABridge->IsIssueValid($this->_ID);
	}

	/**
	 * Posts a comment to the JIRA Issue
	 *
	 * @param SWIFT_JIRAComment $_Comment the Comemnt object
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 */
	public function PostComment(SWIFT_JIRAComment $_Comment)
	{
		if ($_Comment) {
			$_Comment->SetIssue($this);

			$_JIRABridge = SWIFT_JIRABridge::GetInstance();

			$_JIRABridge->PostComment($_Comment);
			return TRUE;
		}
		throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		return FALSE;
	}

	/**
	 * Fetch all JIRA Comment of the current issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 */
	public function FetchAllComments()
	{
		$_JIRABridge = SWIFT_JIRABridge::GetInstance();

		if ($_JIRABridge) {
			$_JIRABridge->FetchAllCommentsBy('issuekey', $this->_key);
			return TRUE;
		}

		return FALSE;
	}

}