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
 * The JIRA Settings Manager class
 *
 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
 */
class Controller_SettingsManager extends Controller_admin
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

		$this->Load->Library('Settings:SettingsManager');

		$this->Language->Load('jira');
		$this->Language->Load('settings');

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
	 * Render the settings
	 *
	 * @author Abhinav Kumar <abhinav.kumar@kayako.com>
	 * @throws SWIFT_Exception If class is not loaded
	 * @return bool "true" on Success, "false" otherwise
	 */
	public function Index()
	{
		$_SWIFT = SWIFT::GetInstance();

		if (!$this->GetIsClassLoaded()) {
			throw new SWIFT_Exception(SWIFT_CLASSNOTLOADED);
			return false;
		}

		$this->UserInterface->Header($this->Language->Get('jira') . ' > ' . $this->Language->Get('settings'), self::MENU_ID, self::NAVIGATION_ID);

		if ($_SWIFT->Staff->GetPermission('admin_canupdatesettings') == '0') {
			$this->UserInterface->DisplayError($this->Language->Get('titlenoperm'), $this->Language->Get('msgnoperm'));
		} else {
			$this->UserInterface->Start(get_class($this), '/JIRA/SettingsManager/Index', SWIFT_UserInterface::MODE_INSERT, false);
			$this->SettingsManager->Render($this->UserInterface, SWIFT_SettingsManager::FILTER_NAME, array('settings_bj'));
			$this->UserInterface->End();
		}

		$this->UserInterface->Footer();

		return true;
	}

}