<?php
/**
 * =======================================
 * ###################################
 * SWIFT Framework
 *
 * @package	SWIFT
 * @author	Kayako Infotech Ltd.
 * @copyright	Copyright (c) 2001-2009, Kayako Infotech Ltd.
 * @license	http://www.kayako.com/license
 * @link		http://www.kayako.com
 * @filesource
 * ###################################
 * =======================================
 */

$__LANG = array (
	// ======= General =======
	'settings_bj'			=>	'JIRA Settings',
	'preferences_bj'			=>	'JIRA Preferences',
	'bj_isenabled'			=>	'Enable JIRA integration',
	'bj_ticketSettings'		=>	'Ticket Settings',
	'bj_accessSettings'		=>	'Access Settings',
	'bj_username'			=>	'JIRA username ',
	'd_bj_username'			=>	'Kayako will connect to your JIRA installation as the user account listed here. This means that when the helpdesk updates an issue, this username will be listed in the JIRA activity log. It also means that your staff users will be able to indirectly access all of the projects this user has permissions to. We recommend creating a new \'Helpdesk\' account for this purpose',
	'bj_password'			=>	'Password',
	'd_bj_password'			=>	'Enter the password for the user account specified above.',
	'bj_defaultproject'		=>	'Default JIRA project',
	'd_bj_defaultproject'		=>	'When creating a new JIRA issue from a ticket, the project selected here will be used as the default. Your staff users can still change the project from the default.',
	'bj_timeout'			=>	'Connection timeout',
	'd_bj_timeout'			=>	'The maximum amount of time (in seconds) that the helpdesk will wait for a response from your JIRA installation. Default value of one second recommended.',
	'bj_jiraurl'				=>	'URL to your JIRA installation',
	'd_bj_jiraurl'			=>	'For example, http://www.yourdomain.com:8080/jira/',
	'bj_noproject'			=>	'No projects available',
	'bj_includesubjectinlink'	=>	'Include ticket subject in JIRA\'s linked tickets area?',
	'd_bj_includesubjectinlink'	=>	'You may want to consider who has permission to see linked tickets on your JIRA.',
	'bj_jiraissuelinking'		=>	'JIRA issue linking',
	'd_bj_jiraissuelinking'		=>	'Enabling this option will add a JIRA issue back link to the kayako ticket in all your linked issues'
);
?>