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
 * The Issue History View
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class View_IssueHistory extends SWIFT_View
{

	/**
	 * Constructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __construct()
	{
		parent::__construct();
		$this->Language->Load('jira');

		return true;
	}

	/**
	 * Destructor
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
	 * Renders the Issue History Tab
	 *
	 * @param mixed $_issuesContainer array of issues associated with current ticket
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return boolean 'true' on success and 'false' otherwise
	 * @throws SWIFT_Exception if class is not loaded
	 */
	public function RenderHistoryTab($_issuesContainer)
	{
		$_SWIFT = SWIFT::GetInstance();

		$_JIRABridge = SWIFT_JIRABridge::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' ' . SWIFT_CLASSNOTLOADED);
		} else if (!is_array($_issuesContainer)) {
			throw new SWIFT_Exception(SWIFT_INVALIDDATA);
		}

		$this->UserInterface->Start(get_class($this), '', SWIFT_UserInterface::MODE_INSERT, false);

		$_IssueHistoryTabObject = new SWIFT_UserInterfaceTab($this->UserInterface, 'history', '', 1, 'history', false, false, 4, '');

		$_columnContainer = array();

		// Issue ID
		$_columnContainer[0]['value'] = $this->Language->Get('jira_issueid');
		$_columnContainer[0]['align'] = 'left';
		$_columnContainer[0]['width'] = '10';

		// Summary
		$_columnContainer[1]['value'] = $this->Language->Get('jira_summary');
		$_columnContainer[1]['align'] = 'left';
		$_columnContainer[1]['width'] = '100';

		// Updated
		$_columnContainer[3]['value'] = $this->Language->Get('jira_updated');
		$_columnContainer[3]['align'] = 'center';
		$_columnContainer[3]['width'] = '10';

		// Priority
		$_columnContainer[4]['value'] = $this->Language->Get('jira_priority');
		$_columnContainer[4]['align'] = 'center';
		$_columnContainer[4]['width'] = '10';

		// Status
		$_columnContainer[5]['value'] = $this->Language->Get('jira_status');
		$_columnContainer[5]['align'] = 'center';
		$_columnContainer[5]['width'] = '10';

		// Assigned To
		$_columnContainer[6]['value'] = $this->Language->Get('jira_assignedto');
		$_columnContainer[6]['align'] = 'center';
		$_columnContainer[6]['width'] = '10';

		// Action
		$_columnContainer[7]['value'] = $this->Language->Get('jira_action');
		$_columnContainer[7]['align'] = 'center';
		$_columnContainer[7]['width'] = '10';

		$_IssueHistoryTabObject->Row($_columnContainer, 'gridtabletitlerow');

		if (empty($_issuesContainer)) {
			$_columnContainer = array();

			$_columnContainer[0]['value'] = $this->Language->Get('jira_noissuefound');
			$_columnContainer[0]['align'] = 'left';
			$_columnContainer[0]['width'] = '100%';

			$_IssueHistoryTabObject->Row($_columnContainer);

			$_renderHTML = $_IssueHistoryTabObject->GetDisplayHTML(true);

			$_renderHTML .= '<script language="Javascript" type="text/javascript">';
			$_renderHTML .= 'ClearFunctionQueue();';
			$_renderHTML .= '</script>';

			echo $_renderHTML;

			return true;
		}

		foreach ($_issuesContainer as $_Issue) {
			$_columnContainer = array();

			$_issueURL = $_SWIFT->Settings->Get('bj_jiraurl') . 'browse/' . $_Issue->GetKey();

			// Key
			$_columnContainer[0]['value'] = '<a href="' . $_issueURL . '" target="_blank">' . htmlspecialchars($_Issue->GetKey()) . '</a>';
			$_columnContainer[0]['align'] = 'left';
			$_columnContainer[0]['width'] = '10';

			// Summary
			$_columnContainer[1]['value'] = '<a href="' . $_issueURL . '" target="_blank">' . IIF(strlen($_Issue->GetSummary()) > 100, htmlspecialchars(substr($_Issue->GetSummary(), 0, 100)) . '...', htmlspecialchars($_Issue->GetSummary())) . '</a>';
			$_columnContainer[1]['align'] = 'left';
			$_columnContainer[1]['width'] = '100';

			// Updated
			$_columnContainer[3]['value'] = htmlspecialchars(SWIFT_Date::EasyDate($_Issue->GetUpdated()) . ' ago');
			$_columnContainer[3]['align'] = 'center';
			$_columnContainer[3]['width'] = '10';

			// Priority
			$_columnContainer[4]['value'] = htmlspecialchars($_Issue->GetPriority());
			$_columnContainer[4]['align'] = 'center';
			$_columnContainer[4]['width'] = '10';

			// Status
			$_columnContainer[5]['value'] = htmlspecialchars($_Issue->GetStatus());
			$_columnContainer[5]['align'] = 'center';
			$_columnContainer[5]['width'] = '10';

			// Comments
			$_columnContainer[6]['value'] = htmlspecialchars($_Issue->GetAssignee());
			$_columnContainer[6]['align'] = 'center';
			$_columnContainer[6]['width'] = '10';

			// Unlink
			$_columnContainer[7]['value'] = '<a title="' . $this->Language->Get('postJIRAComment') . '" href="#" onclick="postJIRAComment(\'' . $_Issue->GetKey() . '\')">
										<img src="' . SWIFT::Get('swiftpath') . SWIFT_APPSDIRECTORY . '/jira/resources/icon-comment.gif' . '"/>
									  </a>'
					. ' | '
					. '<a title="' . $this->Language->Get('jira_unlinkissue') .'" href="#" onclick="unlinkJIRAIssue(\'' . $_Issue->GetKey() . '\')">
										<img src="' . SWIFT::Get('swiftpath') . SWIFT_APPSDIRECTORY . '/jira/resources/edit_delete.png' . '"/>
									   </a>';
			;
			$_columnContainer[7]['align'] = 'center';
			$_columnContainer[7]['width'] = '10';

			$_IssueHistoryTabObject->Row($_columnContainer, '', $_Issue->GetKey());

			//Fetch the issue comments now

			if ($_JIRABridge) {
				$_commentsContainer = $_JIRABridge->FetchAllCommentsBy('issuekey', $_Issue->GetKey());


				if (isset($_columnContainer) && _is_array($_commentsContainer)) {
					$_renderedNotes = array();
					foreach ($_commentsContainer as $_JIRAComment) {
						$_renderedNotes[] = '<div class="notebackground">
											<div class="notecontainer">
												<div class="note">'
								. nl2br($_JIRAComment->GetRawBody(), TRUE) .
								'</div>
											</div>
											<cite class="tip">
												<strong>
													<img border="0" align="absmiddle" src="' . SWIFT::Get('themepath') . 'images/icon_user2.png"/> '
								. $_JIRAComment->GetAuthor() . ' '
								. htmlspecialchars(SWIFT_Date::EasyDate($_JIRAComment->GetUpdated())) . ' ago
												</strong><div style="float: right; padding-right: 4px;"></div>
											</cite>
										</div>';
					}

					$_renderedNotes = implode('', $_renderedNotes);

					$_commentContainer[0]['value'] = '<div class="allnotes">' . $_renderedNotes . '</div>';
					$_commentContainer[0]['width'] = '100';
					$_commentContainer[0]['align'] = 'left';
					$_commentContainer[0]['colspan'] = '7';
					$_commentContainer[0]['style'] = 'background: none repeat scroll 0pt 0pt #F6F1E7; padding: 0px;';

					$_IssueHistoryTabObject->Row($_commentContainer, '', $_Issue->GetKey());
				}
			}
		}

		$_renderHTML = $_IssueHistoryTabObject->GetDisplayHTML(true);

		echo $_renderHTML;

		return true;
	}

}