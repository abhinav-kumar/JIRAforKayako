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
 * JIRA Bridge
 * Handles all the JIRA interactions to and from Kayako Fusion
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_JIRABridge extends SWIFT_Library
{
	/*
	 * =============================================
	 * Access Details
	 * =============================================
	 */

	/**
	 * JIRA Url
	 * e.g. http://localhost:8080
	 * @var string
	 */
	private $_url;

	/**
	 * user name to access JIRA
	 * e. g. admin
	 * @var admin
	 */
	private $_userName;

	/**
	 * password to access JIRA
	 * @var password
	 */
	private $_password;

	/**
	 * Authentication token
	 * @var string
	 */
	private $_authToken;

	/**
	 * Timeout in seconds to connect to the JIRA webservice
	 * @var type
	 */
	private $_connectionTimeout;

	/*
	 * =============================================
	 * Project Details
	 * =============================================
	 */

	/**
	 * Default project key to post issues in
	 * e.g. TEST
	 * @var string
	 */
	private $_projectKey;

	/*
	 * =============================================
	 * Bridge Details
	 * =============================================
	 */

	/**
	 * HTTP Client for connecting & making the API requests to the JIRA web service
	 * @var \SWIFT_HTTPClient
	 */
	private $Client;

	/**
	 * Table name in the Kayako DB
	 * default : TABLE_PREFIX . jira_issues
	 * @var string
	 */
	private static $_tableName = 'jiraissues';

	/**
	 * Single Instance to be used for Singleton JIRABridge
	 * @var \SWIFT_JIRABridge
	 */
	private static $_Instance = NULL;

	/**
	 * JIRA create issue meta data
	 * @var mixed
	 */
	private static $_Meta;

	/**
	 * The last error message
	 * @var string
	 */
	private $_error = 'No Error';

	/**
	 * The default constructor
	 * Reads & initializes the module settings from SWIFT
	 * Prepares the basic JIRA authentication
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'TRUE' on success and 'FALSE' otherwise
	 */
	protected function __construct()
	{
		parent::__construct();

		$_SWIFT = SWIFT::GetInstance();

		if (!$_SWIFT->Settings->Get('bj_isenabled')) {
			return FALSE;
		}

		$this->_url = $_SWIFT->Settings->Get('bj_jiraurl');
		$this->_userName = $_SWIFT->Settings->Get('bj_username');
		$this->_password = $_SWIFT->Settings->Get('bj_password');
		$this->_connectionTimeout = $_SWIFT->Settings->Get('bj_timeout') ? $_SWIFT->Settings->Get('bj_timeout') : 1;

		$this->_projectKey = strtoupper($_SWIFT->Settings->Get('bj_defaultProject'));

		$this->_authToken = base64_encode($this->_userName . ':' . $this->_password);

		$this->Load->Library('HTTP:HTTPClient', array(), TRUE, 'jira');
		$this->Load->Library('HTTP:HTTPAdapter_Curl', array(), TRUE, 'jira');
		$this->Load->Library('JIRA:JIRAComment', FALSE, FALSE);
		$this->Load->LoadApp('Ticket:Ticket',  APP_TICKETS);

		$this->Client = new SWIFT_HTTPClient($this->_url);

		$_Adapter = new SWIFT_HTTPAdapter_Curl();

		$_Adapter->AddOption('CURLOPT_CONNECTTIMEOUT', $this->_connectionTimeout);
		$_Adapter->AddOption('CURLOPT_TIMEOUT', $this->_connectionTimeout);

		$_Adapter->SetEncoding(SWIFT_HTTPBase::RESPONSETYPE_JSON);

		$this->Client->SetAdapter($_Adapter);

		$this->Client->SetHeaders('Authorization', 'Basic ' . $this->_authToken);
		$this->Client->SetHeaders('Accept', 'application/json');
		$this->Client->SetHeaders('Content-Type', 'application/json');
	}

	/**
	 * The default destructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'TRUE' on success & 'FALSE' otherwise
	 */
	public function __destruct()
	{
		return parent::__destruct();
	}

	/**
	 * Creates an JIRA Issue with the provided associated array $_data
	 * prepares the 'POST' body for the request & finally fires the REST request
	 * More Info on the REST API : https://developer.atlassian.com/display/JIRADEV/JIRA+REST+API+Example+-+Create+Issue
	 *
	 * @param mixed $_data an associative array of issue parametres
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function CreateIssue($_data)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (array_key_exists('project', $_data) && $_data['project'] != '') {
			$_projectKey = $_data['project'];
		} else {
			$_projectKey = $this->_projectKey;
		}

		if (array_key_exists('labels', $_data)) {
			if (_is_array($_data['labels'])) {
				$_data['labels'][]		= 'Kayako';
				$_labels			= $_data['labels'];
			}
		} else {
			$_labels = array('Kayako');
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/issue');

		$_fields = array(
			'project'		=> array(
				'key' => $_projectKey
			),
			'summary'		=> $_data['summary'],
			'description'	=> $_data['description'],
			'issuetype'		=> array(
				'id' => $_data['issueType']
			),
			'priority'		=> array(
				'id' => $_data['priority']
			),
			'labels'		=> $_labels
		);

		if (array_key_exists('security', $_data) && !empty($_data['security'])) {
			$_fields['security'] = array(
				'id'	=> $_data['security']
				);
		}

		$this->Client->SetParameterPost('fields', $_fields);

		//Body prepared . . .time to fire the Request
		$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);
		//Check if the response is not an error
		if ($_Response !== FALSE) {
			if ($_Response->isSuccessful()) {
				//seems good so far . . .readh the response body
				$_Decoded = json_decode($_Response->getBody());

				if ($_Decoded) {
					if (isset($_Decoded->id) && isset($_Decoded->key)) {
						$_data['id']	= $_Decoded->id;
						$_data['key']	= $_Decoded->key;

						//We are almost there . . . time to create a local record for Ticket <->Issue reference
						$this->Load->Library('JIRA:JIRAIssue', FALSE, FALSE, 'jira');
						$_SWIFT = SWIFT::GetInstance();
						$_SWIFT->Database->AutoExecute(TABLE_PREFIX . self::$_tableName, array(
							'ticketid'	=> $_SWIFT->Database->Escape($_data['kayakoTicketId']),
							'issueid'	=> $_SWIFT->Database->Escape($_data['id']),
							'issuekey'	=> $_SWIFT->Database->Escape($_data['key'])), 'INSERT');

						$_SWIFTTicketObject		= SWIFT_Ticket::GetObjectOnID($_data['kayakoTicketId']);
						$_ticketSummary		= '';

						if ($_SWIFTTicketObject && $_SWIFTTicketObject instanceof SWIFT_Ticket && $_SWIFTTicketObject->GetIsClassLoaded()) {
							$_title			= $_SWIFTTicketObject->GetTicketDisplayID();
							$_ticketSummary	= $_SWIFTTicketObject->GetProperty('subject');
						} else {
							$_title			= $this->Language->Get('jira_kayakoticket');
							$_ticketSummary	= '';
						}

						$_ticketURL	= SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_data['kayakoTicketId'];

						if (!$_SWIFT->Settings->Get('bj_jiraissuelinking') || $this->PostRemoteLink($_data['key'], $_ticketURL, $_title, $_ticketSummary)) {
							//Finally, create and return a new SWIFT_JIRAIssue object
							return new SWIFT_JIRAIssue($_data);
						}
						else {
							$this->SetErrorMessage($this->Language->Get('jira_issuelinkingfail'));
						}
					}
					else {
						$this->SetErrorMessage($this->Language->Get('jira_error'));
					}
				}
				else {
					$this->SetErrorMessage($this->Language->Get('jira_error'));
				}
			}
			else {
				$this->SetErrorMessage($this->parseErrorMessage($_Response));
			}
		}
		return FALSE;
	}

	/**
	 * GetInstance method for ensuring a single instance per application is created
	 * Tests the connection and returns 'FALSE' if the connection is lost
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed \SWIFT_JIRABridge on success and 'FALSE' otherwise
	 */
	public static function GetInstance()
	{
		if (!self::$_Instance) {
			self::$_Instance = new SWIFT_JIRABridge();
		}
		return self::$_Instance;
	}

	/**
	 * Calls the JIRA REST API and checks if an issue is still active in JIRA
	 * If an issue is not found on JIRA deletes the corresponding entry from the
	 * Kayako database.
	 *
	 * @param mixed $_issueID - Issue key or id
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' if the issue is still active and 'FALSE' otherwise
	 */
	public function IsIssueValid($_issueKey = NULL)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (NULL == $_issueKey) {
			return FALSE;
		}

		if (!$this->Test()) {
			return FALSE;
		}


		$this->Client->setUri($this->_url . 'rest/api/latest/issue/' . $_issueKey);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if (!$_Response || !$_Response->isSuccessful()) {
			return FALSE;
		}

		$_Decoded = json_decode($_Response->getBody());

		if (isset($_Decoded->errorMessages)) {
			/**
			 * Issue seems to have been deleted
			 * Delete from kayako database as well & return FALSE
			 */
			$_Sql = 'DELETE FROM ' . TABLE_PREFIX . self::$_tableName .
					' WHERE issueid=' . $this->Database->Escape($_issueKey);

			$this->Database->Execute($_Sql);
			return FALSE;
		}
		return TRUE;
	}
	/**
	 * Calls the JIRA REST API and checks if an issue is still active in JIRA
	 * If an issue is not found on JIRA deletes the corresponding entry from the
	 * Kayako database.
	 *
	 * @param mixed $_issueID - Issue key or id
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' if the issue is still active and 'FALSE' otherwise
	 */
	public function IsProjectValid($_projectKey = NULL)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (NULL == $_projectKey) {
			return FALSE;
		}

		if (!$this->Test()) {
			return FALSE;
		}


		$this->Client->setUri($this->_url . 'rest/api/latest/project/' . $_projectKey);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Parses a JSON decoded response &
	 * Converts that into an \SWIFT_JIRAIssue
	 *
	 * @param \stdClass $_JSONDecoded - Decoded PHP object
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue
	 */
	public function ParseResponse($_JSONDecoded)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_Data = array();

		$_dataStore = $this->Database->QueryFetch("SELECT ticketid FROM " . TABLE_PREFIX . self::$_tableName . " WHERE issueid = '" .
				intval($_JSONDecoded->id) . "'");

		$_Data['id']			= $_JSONDecoded->id;
		$_Data['key']			= $_JSONDecoded->key;
		$_Data['project']		= $_JSONDecoded->fields->project->key;
		$_Data['status']			= $_JSONDecoded->fields->status->name;
		$_Data['commentsCount']	= $_JSONDecoded->fields->comment->total;
		$_Data['issueType']		= $_JSONDecoded->fields->issuetype->name;
		$_Data['summary']		= $_JSONDecoded->fields->summary;
		$_Data['kayakoTicketId']	= $_dataStore['ticketid'];
		$_Data['priority']		= $_JSONDecoded->fields->priority->name;
		$_Data['assignee']		= isset($_JSONDecoded->fields->assignee->displayName) ? $_JSONDecoded->fields->assignee->displayName : "Unassigned";
		$_Data['reporter']		= $_JSONDecoded->fields->reporter->name;
		$_Data['description']		= $_JSONDecoded->fields->description;
		$_Data['labels']			= $_JSONDecoded->fields->labels;
		$_Data['created']		= strtotime($_JSONDecoded->fields->created);
		$_Data['updated']		= strtotime($_JSONDecoded->fields->updated);

		$this->Load->Library('JIRA:JIRAIssue', FALSE, FALSE, 'jira');

		return new SWIFT_JIRAIssue($_Data);
	}

	/**
	 * Calls the JIRA REST Api and fetches the issue details
	 *
	 * @param mixed $_issueID issue key | issue id
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function Get($_issueID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_issueID) || !$this->IsIssueValid($_issueID)) {
			return FALSE;
		}

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueID;

		$this->Client->setUri($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_Decoded = json_decode($_Response->getBody());

			return $this->ParseResponse($_Decoded);
		}
		return FALSE;
	}

	/**
	 * Fetches ONE JIRA Issue based on passed parameter from the local database
	 *
	 * @param string $_param the search parameter
	 * @param string $_value the search value
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function GetIssueBy($_param, $_value)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_query = "SELECT * FROM " . TABLE_PREFIX . self::$_tableName . " WHERE " . $this->Database->Escape($_param) . " = '" .
				$this->Database->Escape($_value) . "'";

		$_DataStore = $this->Database->QueryFetch($_query);

		if ($this->IsIssueValid($_DataStore['issueid'])) {
			return $this->Get($_DataStore['issueid']);
		}
		return FALSE;
	}

	/**
	 * Fetches All the JIRA Issue based on passed parameter from the local database
	 *
	 * @param string $_param the search parameter
	 * @param string $_value the search value
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed an array of \SWIFT_JIRAIssue on success and 'FALSE' otherwise
	 */
	public function GetIssuesBy($_param, $_value)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_Query = "SELECT * FROM " . TABLE_PREFIX . self::$_tableName . " WHERE " . $this->Database->Escape($_param) . " = '" .
				$this->Database->Escape($_value) . "'";
		$_DataStore = $this->Database->QueryFetchAll($_Query);

		$_issues = array();

		$this->Load->Library('JIRA:JIRAIssue', FALSE, FALSE, 'jira');

		foreach ($_DataStore as $_Data) {
			if ($this->IsIssueValid($_Data['issuekey'])) {
				$_issues[] = $this->Get($_Data['issuekey']);
			}
		}

		if (_is_array($_issues)) {
			return $_issues;
		} else {
			return FALSE;
		}
	}

	/**
	 * Fetches the available issue types from JIRA REST API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetIssueTypes()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$this->Client->setUri($this->_url . 'rest/api/latest/issuetype');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful())
			return json_decode($_Response->getBody());
		return FALSE;
	}

	/**
	 * Fetches the available issue types from JIRA REST API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetIssueTypesByProject($_projectKey)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key ' . $this->Language->Get('jira_noempty'));
		}

		if (!empty($_projectKey) && is_string($_projectKey) && $this->isProjectValid($_projectKey)) {
			$_apiURL = $this->_url . 'rest/api/latest/project/' . $_projectKey;
			$this->Client->setUri($_apiURL);

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

			if ($_Response && $_Response->isSuccessful()) {
				$_Decoded = json_decode($_Response->getBody());
				if ($_Decoded && isset($_Decoded->issueTypes)) {
					return $_Decoded->issueTypes;
				}
			}
			return FALSE;
		} else {
			echo 'No project key specified or project ', $_projectKey, ' not found';
		}
	}

	/**
	 * Fetches the available security levels from JIRA REST API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetSecurityLevelsByProject($_projectKey, $_issueType)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key ' . $_SWIFT->Language->Get('jira_noempty'));
		}

		if (!$this->IsProjectValid($_projectKey)) {
			throw new SWIFT_Exception($_projectKey . ' is not a valid project');
		}

		$_CreateMeta = $this->GetCreateMeta();

		if ($_CreateMeta && _is_array($_CreateMeta) && array_key_exists($_projectKey, $_CreateMeta)) {
			if (array_key_exists('security', $_CreateMeta[$_projectKey])) {
				return $_CreateMeta[$_projectKey]['security'];
			}
			return FALSE;
		}

//		$_Meta = $this->GetCreateMetaByProject($_projectKey, $_issueType);
//
//		if ($_Meta) {
//			if (isset($_Meta->projects[0]->issuetypes[0]->fields->security->allowedValues)) {
//				return $_Meta->projects[0]->issuetypes[0]->fields->security->allowedValues;
//			}
//		}
//		return FALSE;
	}

	/**
	 * Fetches the available issue priorities from JIRA REST API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetPriorities()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		$_JIRAPriorities = $_SWIFT->Cache->Get('jirapriorities');

		if (!$_JIRAPriorities || !_is_array($_JIRAPriorities)) {
			$this->Client->setUri($this->_url . 'rest/api/latest/priority');

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);
			if ($_Response && $_Response->isSuccessful()) {
				$_PrioritiesDecoded = json_decode ($_Response->getBody ());
				if ($_PrioritiesDecoded && _is_array($_PrioritiesDecoded)) {
					$_prioritiesContainer = array();
					foreach ($_PrioritiesDecoded as $_Priority) {
						$_prioritiesContainer[] = array(
							'title' => $_Priority->name,
							'value' => $_Priority->id
						);
					}
					if (_is_array($_prioritiesContainer)) {
						$_SWIFT->Cache->Update('jirapriorities', $_prioritiesContainer);
					}
				}
			}
		}
		return $_SWIFT->Cache->Get('jirapriorities');
		return FALSE;
	}

	/**
	 * Fetches the available projects from JIRA REST API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return mixed json decoded object on success and 'FALSE' otherwise
	 */
	public function GetProjects()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		$_JIRAProjects = $_SWIFT->Cache->Get('jiraprojects');

		if (!$_JIRAProjects || !_is_array($_JIRAProjects)) {
			$this->Client->setUri($this->_url . 'rest/api/latest/project');

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

			echo 'Response received <pre><br/>', $_Response->getRawData(), '</pre><br/>';
			if ($_Response && $_Response->isSuccessful()) {
				$_JIRAProjects = json_decode($_Response->getBody());

				if (_is_array($_JIRAProjects)) {
					$_cachedProjects = array();
					foreach ($_JIRAProjects as $_Project) {
						$_cachedProject = array(
							'title'		=> $_Project->name,
							'value'	=> $_Project->key
						);
						$_cachedProjects[] = $_cachedProject;
					}
				} else if ($_JIRAProjects) {
					$_cachedProjects = array(
						'title' => $_Projects->name,
						'value' => $_Projects->id
					);
				}

				if (_is_array($_cachedProjects)) {
					$_SWIFT->Cache->Update('jiraprojects', $_cachedProjects);
				} else {
					$this->SetErrorMessage('jira_noprojectsloaded');
					return FALSE;
				}
			} else {
				$this->SetErrorMessage('No Projects found');
			}
		}
		return $_SWIFT->Cache->Get('jiraprojects');
	}

	/**
	 * Not Implemented yet
	 * @return boolean
	 */
	public function GetReporters()
	{
		return FALSE;
		$this->Client->setUri($this->_url . 'rest/api/latest/project');

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful())
			return json_decode($_Response->getBody());

		return FALSE;
	}

	/**
	 * Reads and parses an error message returned from an JIRA REST API Request
	 *
	 * @param SWIFT_HTTPResponse $_Response
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string the error message or FALSE(bool) if no error is read
	 */
	protected function parseErrorMessage(SWIFT_HTTPResponse $_Response)
	{
		$_Decoded = json_decode($_Response->getBody());

		$_parsedErrors = '';

		if ($_Decoded) {
			if (isset($_Decoded->errors)) {
				$_errors = $_Decoded->errors;
				foreach ($_errors as $_Key => $_Val) {
					$_parsedErrors .= $_Val . PHP_EOL;
				}
			}
			if (isset($_Decoded->errorMessages)) {
				foreach ($_Decoded->errorMessages as $_errorMessage) {
					$_parsedErrors .= $_errorMessage . PHP_EOL;
				}
			}
			return $_parsedErrors;
		}
		return FALSE;
	}

	/**
	 * Unlinks a JIRA Issue from a Kayako Support Ticket
	 *
	 * @param string $_issueKey The issue key to unlink
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 * @throws SWIFT_Exception
	 */
	public function UnlinkIssue($_issueKey, $_ticketID)
	{
		if (!$this->GetIsClassLoaded())
			throw new SWIFT_Exception(__CLASS__ . ' - ' . SWIFT_CLASSNOTLOADED);

		if ($_issueKey) {
			$_JIRAIssue = $this->GetIssueBy('issuekey', $_issueKey);

			if ($_JIRAIssue) {
				$_SWIFT = SWIFT::GetInstance();
				$_SWIFT_TicketObject = SWIFT_Ticket::GetObjectOnID($_ticketID);
				$_JIRAComment = new SWIFT_JIRAComment();
				$_JIRAComment->SetBody(sprintf($this->Language->Get('unlinkedFromJIRA'),$_SWIFT_TicketObject->GetTicketDisplayID(), $_SWIFT->Staff->GetProperty('fullname')));
				if ($_JIRAIssue->PostComment($_JIRAComment) !== FALSE) {
					if ($this->RemoveRemoteLink($_issueKey, $_ticketID)) {
						/**
						* Comment posted & Remote link removed
						* Delete from kayako database as well & return TRUE
						*/
						$_query = 'DELETE FROM ' . TABLE_PREFIX . self::$_tableName
								. ' WHERE issuekey=\'' . $_issueKey . '\'';

						if ($this->Database->Execute($_query) !== FALSE)
							return TRUE;
					}
				}
				else {
					$this->SetErrorMessage($this->Language->Get('jira_error') . ' ' . $this->Language->Get('jira_comment_notposted'));
					return FALSE;
				}
			}
			else {
				$this->SetErrorMessage($this->Language->Get('jira_noissuekey'));
				return FALSE;
			}
		}
		else {
			$this->SetErrorMessage($this->Language->Get('jira_noissuekey'));
			return FALSE;
		}
	}

	/**
	 * Posts a comment to JIRA
	 *
	 * @param SWIFT_JIRAComment $_Comment
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' on success & 'FALSE' otherwise
	 * @throws SWIFT_Exception
	 */
	public function PostComment(SWIFT_JIRAComment $_Comment = NULL)
	{
		if (!$this->GetIsClassLoaded())
			throw new SWIFT_Exception(__CLASS__ . ' - ' . SWIFT_CLASSNOTLOADED);

		if ($_Comment) {
			if (!is_string($_Comment->GetBody())) {
				throw new SWIFT_Exception(SWIFT_INVALIDDATA);
			}

			$_JIRAIssue = $_Comment->GetIssue();

			$_commentBody = $_Comment->GetBody();

			$_visibility = $_Comment->GetVisibility();

			if ($_JIRAIssue) {
				$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_JIRAIssue->GetKey() . '/comment';

				$this->Client->setUri($_apiURL);

				$this->Client->SetParameterPost('body', $_commentBody);

				if ($_visibility && _is_array($_visibility) && array_key_exists('type', $_visibility) && array_key_exists('value', $_visibility)) {
					$this->Client->SetParameterPost('visibility', $_visibility);
				}

				$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);

				if ($_Response && $_Response->isSuccessful()) {
					$_ResponseDecoded = json_decode($_Response->getBody());
					return $_ResponseDecoded->id;
				}
				return FALSE;
			}
			$this->_error = $this->Language->Get('jira_noissuefound');
			return FALSE;
		}
		return FALSE;
	}

	public function GetCreateMeta()
	{
		$_SWIFT = SWIFT::GetInstance();

		$_CreateMetaCached = $_SWIFT->Cache->Get('jiracreatemeta');

		if ($_CreateMetaCached) {
			return $_CreateMetaCached;
		}

		$_queryArray = array(
			'expand'		=> 'projects.issuetypes.fields'
		);

		$_query = http_build_query($_queryArray);

		$_apiURL = $this->_url . 'rest/api/2/issue/createmeta?' . $_query;

		$this->Client->setUri($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_CreateMeta = json_decode($_Response->getBody());
			$_CreateMetaContainer = $_ProjectCreateMeta = array();
			foreach ($_CreateMeta->projects as $_Project) {
				$_issueTypes = array();
				foreach ($_Project->issuetypes as $_issueType) {
					$_ProjectCreateMeta['issuetype'][$_issueType->id] = $_issueType->name;
					if (isset($_issueType->fields->security->allowedValues) && _is_array($_issueType->fields->security->allowedValues)) {
						foreach ($_issueType->fields->security->allowedValues as $_securityLevel){
							$_ProjectCreateMeta['security'][$_securityLevel->id] = $_securityLevel->name;
						}
					}
				}
				$_CreateMetaContainer[$_Project->key] = $_ProjectCreateMeta;
			}

			if (_is_array($_CreateMetaContainer)) {
				$_SWIFT->Cache->Update('jiracreatemeta', $_CreateMetaContainer);
				return $_CreateMetaContainer;
			}
		}
	}

	/**
	 * Fetches the updated Meta from the JIRA API
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool 'TRUE' on success & 'FALSE' otherwise
	 */
	public function GetCreateMetaByProject($_projectKey, $_issueType = '')
	{
		$_SWIFT = SWIFT::GetInstance();

		if (empty($_projectKey)) {
			throw new SWIFT_Exception('Project Key: ' . $_SWIFT->Language->Get('jira_noempty'));
		}

		if (!$this->IsProjectValid($_projectKey)) {
			throw new SWIFT_Exception('Project ' . $_projectKey . ' doesn\'t exist');
		}

//		$_CreateMetaCached = $_SWIFT->Cache->Get('jiracreatemeta');

//		if (!_is_array($_CreateMetaCached) || !array_key_exists($_projectKey, $_CreateMetaCached)) {

			$_queryArray = array(
				'projectKeys'	=> $_projectKey,
				'expand'		=> 'projects.issuetypes.fields'
			);

			if (!empty($_issueType)) {
				$_queryArray['issuetypeIds'] = $_issueType;
			}

			$_query = http_build_query($_queryArray);

			$_apiURL = $this->_url . 'rest/api/2/issue/createmeta?' . $_query;

			$this->Client->setUri($_apiURL);

			$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

			if ($_Response && $_Response->isSuccessful()) {
				return json_decode($_Response->getBody());
//				$_CreateMeta = array(
//					$_projectKey	=> json_decode($_Response->getBody())
//				);
//				if (!$_SWIFT->Cache->Update('jiracreatemeta', $_CreateMeta))
//					return FALSE;
			}
		//}
		return $_SWIFT->Cache->Get('jiracreatemeta');
	}

	/**
	 * Returns the last error
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return string
	 */
	public function GetErrorMessage()
	{
		return $this->_error;
	}

	/**
	 * Sets an error message
	 *
	 * @param string $_error The error message to set
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRABridge
	 */
	public function SetErrorMessage($_error)
	{
		if (is_string($_error) && $_error != '') {
			$this->_error = $_error;
			return $this;
		}
	}

	/**
	 * Fetches all comments by a parameter
	 *
	 * @param string $_param The parameter to filter comemnts by
	 * @param mixed $_value The parameter value
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return \SWIFT_JIRAComment|boolean \SWIFT_JIRAComment on success and 'FALSE' otherwise
	 */
	public function FetchAllCommentsBy($_param = NULL, $_value = NULL)
	{
		if ($_param && $_value) {

			if ($_param == 'issuekey') {

				$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_value . '/comment';

				$this->Client->setUri($_apiURL);

				$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

				if ($_Response && $_Response->isSuccessful()) {
					$_ResponseDecoded = json_decode($_Response->getBody())->comments;

					$_CommentsContainer = array();

					foreach ($_ResponseDecoded as $_Response) {

						$_JIRAComment = new SWIFT_JIRAComment();
						$_JIRAComment->ParseJSON($_Response);

						if ($_JIRAComment) {
							$_CommentsContainer[] = $_JIRAComment;
						}
					}

					return $_CommentsContainer;
				}
				return FALSE;
			}
		}
	}

	/**
	 * Tests connection to JIRA
	 * Can/Should be used before every operation
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'TRUE' if the connection is successful, 'FALSE' otherwise
	 */
	public function Test()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . '  ' . SWIFT_CLASSNOTLOADED);
		}

		$_SWIFT = SWIFT::GetInstance();

		if (!$_SWIFT->Settings->Get('bj_isenabled')) {
			return FALSE;
		}

		$this->Client->setUri($this->_url);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && ($_Response->isSuccessful() || $_Response->isRedirect() )) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Links a ticket with an existing JIRA issue
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_ticketID The kayako ticket id
	 * @param mixed $_issueID The JIRA issue id/key
	 * @return boolean
	 */
	public function LinkIssue($_ticketID, $_JIRAIssueID, $_data = array())
	{
		if ($_JIRAIssueID && $this->IsIssueValid($_JIRAIssueID)
				&& $_ticketID) {

			$_JIRAIssue = $this->Get($_JIRAIssueID);

			if ($_JIRAIssue && $_JIRAIssue instanceof SWIFT_JIRAIssue) {
				if (array_key_exists('description', $_data) && $_data['description'] != '') {

					$_JIRAComment = new SWIFT_JIRAComment();
					$_JIRAComment->SetBody($_data['description']);
					$_JIRAComment->SetIssue($_JIRAIssue);

					$this->PostComment($_JIRAComment);
				}
				//We are almost there . . . time to create a local record for Ticket <->Issue reference
				$this->Load->Library('JIRA:JIRAIssue', FALSE, FALSE, 'jira');

				$_SWIFT = SWIFT::GetInstance();

				$_updated = $_SWIFT->Database
								->AutoExecute(TABLE_PREFIX . self::$_tableName, array(
									'ticketid' => $_SWIFT->Database->Escape($_ticketID),
									'issueid' => $_SWIFT->Database->Escape($_JIRAIssue->GetId()),
									'issuekey' => $_SWIFT->Database->Escape($_JIRAIssueID)), 'INSERT');
				if ($_updated) {
					$_SWIFTTicketObject = SWIFT_Ticket::GetObjectOnID($_ticketID);

					if ($_SWIFTTicketObject && $_SWIFTTicketObject instanceof SWIFT_Ticket && $_SWIFTTicketObject->GetIsClassLoaded()) {
						$_title			= $_SWIFTTicketObject->GetTicketDisplayID();
						$_ticketSummary	= $_SWIFTTicketObject->GetProperty('subject');
					} else {
						$_title			= $this->Language->Get('jira_kayakoticket');
						$_ticketSummary	= '';
					}

					$_ticketURL	= SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;

					if ($_SWIFT->Settings->Get('bj_jiraissuelinking')) {
						$this->PostRemoteLink($_JIRAIssueID, $_ticketURL, $_title, $_ticketSummary);
					}
				}
				} else {
					$this->SetErrorMessage($this->Language->Get('jira_noissuefound'));
					return FALSE;
				}
			} else {
				$this->SetErrorMessage($this->Language->Get('jira_noissuefound'));
				return FALSE;
			}
		return TRUE;
	}

	/**
	 * Posts a remote link to JIRA
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_issueKey The JIRA issue key to the send to the request to
	 * @param string $_ticketURL Kayako ticket URL for backlinking
	 * @param string $_title Remote link title
	 * @param string $_summary Remote link summary
	 * @param int $_status Kayako ticket status
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function PostRemoteLink($_issueKey, $_ticketURL, $_title = '', $_summary = '', $_status = 1)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		if (empty($_issueKey) || empty($_ticketURL)) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		if (!$this->IsIssueValid($_issueKey)) {
			throw new SWIFT_Exception('Invalid Issue');
		}
		$_SWIFT = SWIFT::GetInstance();

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueKey . '/remotelink';

		$this->Client->SetURI($_apiURL);

		$_globalID = 'system=' . $_ticketURL;

		$objectPayload = array(
			'url'			=> $_ticketURL,
			'title'			=> $_title,
			'icon'			=> array(
				'url16x16'	=> SWIFT::Get('swiftpath') . '/favicon.ico',
				'title'		=> SWIFT_PRODUCT
			)
		);

		if ( $_SWIFT->Settings->Get('bj_includesubjectinlink') ) {
			$objectPayload['summary'] = $_summary;
		}

		$_ticketStatusClosed	= false;
		$_ticketStatusCache	= $_SWIFT->Cache->Get('statuscache');

		if (_is_array($_ticketStatusCache)) {
			foreach ($_SWIFT->Cache->Get('statuscache') as $_ticketStatus) {
				if ($_ticketStatus['markasresolved']) {
					$_ticketStatusClosed = $_ticketStatus['ticketstatusid'];
				}
			}
		}

		if ($_ticketStatusClosed && $_status == $_ticketStatusClosed) {
			$objectPayload['status'] = array(
				'resolved'	=> true,
				'icon'		=> array(
					'url16x16'	=> SWIFT::Get('swiftpath') . '__modules/jira/resources/resolved.png',
					'title'		=> $this->Language->Get('jira_ticketclosed'),
					'link'		=> $_ticketURL
				)
			);
		}

		$this->Client->SetParameterPost('globalId',$_globalID);
		$this->Client->SetParameterPost('object',$objectPayload);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::POST, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Removes a remote issue link
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_issueKey The JIRA issue key
	 * @param string | $_ticketID The Kayako ticket ID/Key
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function RemoveRemoteLink($_issueKey, $_ticketID)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		$_ticketURL	= SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;

		$_globalID = 'system=' . $_ticketURL;

		$_apiURL = $this->_url . 'rest/api/latest/issue/' . $_issueKey . '/remotelink';

		$this->Client->SetURI($_apiURL);

		$this->Client->SetParameterGet('globalId', $_globalID);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::DELETE);

		if ($_Response->getResponseCode() == 204) {
			return TRUE;
		} else {
			echo $_Response->getResponseCode(), ' : ', $_Response->getRawData();
			return FALSE;
		}
	}

	public function GetProjectRoles($_projectKey)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' : ' . SWIFT_CLASSNOTLOADED);
		}

		if (empty($_projectKey) || !is_string($_projectKey)) {
			throw new SWIFT_Exception('Project Key can not be empty');
		}

		$_apiURL = $this->_url . 'rest/api/latest/project/' . $_projectKey . '/role';
		$this->Client->SetURI($_apiURL);

		$_Response = $this->Client->Request(SWIFT_HTTPBase::GET, $this->_connectionTimeout);

		if ($_Response && $_Response->isSuccessful()) {
			$_RolesDecoded = json_decode($_Response->getBody());
			return get_object_vars($_RolesDecoded);
		}
		return FALSE;
	}

	/**
	 * Helper Function returns the ticket URL for a given interface
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param string $_ticketID The ticket id/key
	 * @param int The interface
	 * @return bool "true" on Success, "false" otherwise
	 * @throws SWIFT_Exception If the Class is not Loaded
	 */
	public function GetTicketURL($_ticketID, $_interface = SWIFT_Interface::INTERFACE_STAFF)
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);

			return false;
		}

		if ($_interface == SWIFT_Interface::INTERFACE_STAFF) {
			return SWIFT::Get('basename') . '/Tickets/Ticket/View/' . $_ticketID;
		}

		return true;
	}
}