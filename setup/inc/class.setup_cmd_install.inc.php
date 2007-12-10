<?php
/**
 * eGgroupWare setup - install a new instance
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package setup
 * @copyright (c) 2007 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$ 
 */

/**
 * setup command: install a new instance
 */
class setup_cmd_install extends setup_cmd 
{
	/**
	 * Constructor
	 *
	 * @param string $domain string with domain-name or array with all arguments
	 * @param string $config_user=null user to config the domain (or header_admin_user)
	 * @param string $config_passwd=null pw of above user
	 * @param string $backup=null filename of backup to use instead of new install, default new install
	 * @param string $charset='utf-8' charset for the install, default utf-8 now
	 */
	function __construct($domain,$config_user=null,$config_passwd=null,$backup=null,$charset='utf-8',$verbose=false)
	{
		if (!is_array($domain))
		{
			$domain = array(
				'domain'        => $domain,
				'config_user'   => $config_user,
				'config_passwd' => $config_passwd,
				'backup'        => $backup,
				'charset'       => $charset,
				'verbose'       => $verbose,
			);
		}
		elseif(!$domain['charset'])
		{
			$domain['charset'] = 'utf-8';
		}
		//echo __CLASS__.'::__construct()'; _debug_array($domain);
		admin_cmd::__construct($domain);
	}

	/**
	 * test or create database
	 * 
	 * @param boolean $check_only=false only run the checks (and throw the exceptions), but not the command itself
	 * @return string serialized $GLOBALS defined in the header.inc.php
	 * @throws Exception(lang('Wrong credentials to access the header.inc.php file!'),2);
	 * @throws Exception('header.inc.php not found!');
	 */
	protected function exec($check_only=false)
	{
		global $setup_info;

		// instanciate setup object and check authorisation
		$this->check_setup_auth($this->config_user,$this->config_passwd,$this->domain);
		
		$this->check_installed($this->domain,array(13,14,20),$this->verbose);
		
		// use uploaded backup, instead installing from scratch
		if ($this->backup)
		{
			$db_backup =& new db_backup();
	
			if (!is_resource($f = $db_backup->fopen_backup($this->backup,true)))
			{
				throw new egw_exception_wrong_userinput(lang('Restore failed'),31);
			}
			//echo lang('Restore started, this might take a few minutes ...')."\n";
			$db_backup->restore($f,$this->charset);
			fclose($f);

			return lang('Restore finished');
		}
		// regular (new) install
		if ($GLOBALS['egw_info']['setup']['stage']['db'] != 3)
		{
			throw new egw_exception_wrong_userinput(lang('eGroupWare is already installed!'),30);
		}
		$setup_info = self::$egw_setup->detection->upgrade_exclude($setup_info);

		// Set the DB's client charset if a system-charset is set
		self::$egw_setup->system_charset = strtolower($this->charset);
		self::$egw_setup->db->Link_ID->SetCharSet($this->charset);

		if ($this->verbose) echo lang('Installation started, this might take a few minutes ...')."\n";
		$setup_info = self::$egw_setup->process->pass($setup_info,'new',false,True);
		
		return lang('Installation finished');
	}
}
