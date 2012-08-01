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
 * The Main Installer, Installs the module and performs the initial (db) setup
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class SWIFT_SetupDatabase_Jira extends SWIFT_SetupDatabase
{
	/**
	 *  The default constructor
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function __construct()
	{
		parent::__construct('jira');
		return TRUE;
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
		return TRUE;
	}

	/**
	 * Loads/Creates the table in the SWIFT database
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function LoadTables()
	{
		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(__CLASS__ . ' ' . SWIFT_CLASSNOTLOADED);
		}

		$this->AddTable('jiraissues', new SWIFT_SetupDatabaseTable(TABLE_PREFIX . "jiraissues", "jiraissueid I PRIMARY AUTO NOTNULL,
																			ticketid I NOTNULL,
																			issueid  I NOTNULL,
																			issuekey C(10) DEFAULT '' NOTNULL"
																			));
		return true;
	}


	/**
	 * Function that does the heavy execution
	 * Calls the parent::Install method for the doing the work
	 * Imports module settings to the SWIFT database
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param int $_pageIndex The Page Index
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function Install($_pageIndex)
	{
		parent::Install($_pageIndex);

		$this->ImportSettings();

		return true;
	}

	/**
	 * Uninstalls the module
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function Uninstall()
	{
		parent::Uninstall();

		return TRUE;
	}

	/**
	 * Upgrades the Module
	 * Imports/Updates module settings to the SWIFT database
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @param bool $_isForced
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function Upgrade($_isForced = false)
	{
		parent::Upgrade($_isForced);

		$this->ImportSettings();

		return true;
	}

	/**
	 * Imports the settings into SWIFT's database
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @return bool "true" on Success, "false" otherwise
	 */
	private function ImportSettings()
	{
		if (!$this->GetIsClassLoaded())
		{
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
			return false;
		}

		$this->Load->Library('Settings:SettingsManager');
		$this->SettingsManager->Import('./' . SWIFT_APPSDIRECTORY . '/jira/config/settings.xml');

		return true;
	}
}