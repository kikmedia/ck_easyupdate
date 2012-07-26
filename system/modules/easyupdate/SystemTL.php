<?php
if (!defined('TL_ROOT'))
	die('You can not access this file directly!');
/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or 
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    System
 * @license    LGPL
 * @filesource
 * 
 * Adapted to Contao > 2.9
 * @copyright 	Copyright kikmedia.webdevelopment 2012
 * @author		Carolina Koehn (ck@kikmedia.de)
 * @package		ck_easyupdate
 * @license		LGPL 
 */
/**
 * Class System
 *
 * Provide default methods that are required in all models and controllers.
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Library
 */
abstract class SystemTL {
	/**
	 * Configuraion object
	 * @var object
	 */
	protected $Config;
	/**
	 * Input object
	 * @var object
	 */
	protected $Input;
	/**
	 * Environment object
	 * @var object
	 */
	protected $Environment;
	/**
	 * Session object
	 * @var object
	 */
	protected $Session;
	/**
	 * Database object
	 * @var object
	 */
	protected $Database;
	/**
	 * Encryption object
	 * @var object
	 */
	protected $Encryption;
	/**
	 * String object
	 * @var object
	 */
	protected $String;
	/**
	 * Files object
	 * @var object
	 */
	protected $Files;
	/**
	 * User object
	 * @var object
	 */
	protected $User;
	/**
	 * Template object
	 * @var object
	 */
	protected $Template;
	/**
	 * Data container object
	 * @var object
	 */
	protected $DataContainer;
	/**
	 * Automator object
	 * @var object
	 */
	protected $Automator;
	/**
	 * Cache array
	 * @var array
	 */
	protected $arrCache = array ();
	/**
	 * Import some default libraries
	 */
	protected function __construct() {
		$this->import('Config');
		$this->import('Input');
		$this->import('Environment');
		$this->import('Session');
	}
	/**
	 * Import a library and make it accessible by its name or an optional key
	 * @param string
	 * @param string
	 * @param boolean
	 * @throws Exception
	 */
	protected function import($strClass, $strKey = false, $blnForce = false) {
		$strKey = $strKey ? $strKey : $strClass;
		if (!is_object($this-> $strKey) || $blnForce) {
			$this-> $strKey = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func(array (
				$strClass,
				'getInstance'
			)) : new $strClass ();
		}
	}
	/**
	 * Add a log entry
	 * @param string
	 * @param string
	 * @param integer
	 */
	protected function log($strText, $strFunction, $strAction) {
		$this->import('Database');
		$strIp = '127.0.0.1';
		if ($this->Environment->remoteAddr) {
			$strIp = $this->Environment->remoteAddr;
		}
		$this->Database->prepare("INSERT INTO tl_log (tstamp, source, action, username, text, func, ip, browser) VALUES(?, ?, ?, ?, ?, ?, ?, ?)")->execute(time(), (TL_MODE == 'FE' ? 'FE' : 'BE'), $strAction, ($GLOBALS['TL_USERNAME'] ? $GLOBALS['TL_USERNAME'] : ''), specialchars($strText), $strFunction, $strIp, $this->Environment->httpUserAgent);
	}
	/**
	 * Add a request string to the current URI string
	 * @param string
	 * @param integer
	 * @return string
	 */
	protected function addToUrl($strRequest) {
		$strRequest = preg_replace('/^&(amp;)?/i', '', $strRequest);
		$queries = preg_split('/&(amp;)?/i', $this->Environment->queryString);
		// Overwrite existing parameters
		foreach ($queries as $k => $v) {
			$explode = explode('=', $v);
			if (preg_match('/' . preg_quote($explode[0], '/') . '=/i', $strRequest)) {
				unset ($queries[$k]);
			}
		}
		$href = '?';
		if (count($queries) > 0) {
			$href .= implode('&amp;', $queries) . '&amp;';
		}
		return $this->Environment->script . $href . str_replace(' ', '%20', $strRequest);
	}
	/**
	 * Reload the current page
	 */
	protected function reload() {
		if (headers_sent()) {
			exit;
		}
		header('Location: ' . $this->Environment->url . $this->Environment->requestUri);
		exit;
	}
	/**
	 * Redirect to another page
	 * @param string
	 * @param false
	 */
	protected function redirect($strLocation, $intStatus = 303) {
		if (headers_sent()) {
			exit;
		}
		// Header
		switch ($intStatus) {
			case 301 :
				header('HTTP/1.1 301 Moved Permanently');
				break;
			case 302 :
				header('HTTP/1.1 302 Found');
				break;
			case 303 :
				header('HTTP/1.1 303 See Other');
				break;
		}
		// Check target address
		if (preg_match('@^https?://@i', $strLocation)) {
			header('Location: ' . str_replace('&amp;', '&', $strLocation));
		} else {
			header('Location: ' . $this->Environment->base . str_replace('&amp;', '&', $strLocation));
		}
		exit;
	}
	/**
	 * Return the current referer URL and optionally encode ampersands
	 * @param boolean
	 * @return string
	 */
	protected function getReferer($blnEncodeAmpersands = false) {
		$session = $this->Session->getData();
		$key = ($this->Environment->script == 'contao/files.php') ? 'fileReferer' : 'referer';
		$return = preg_replace('/(&(amp;)?|\?)tg=[^& ]*/i', '', (($session[$key]['current'] != $this->Environment->requestUri) ? $session[$key]['current'] : $session[$key]['last']));
		$return = preg_replace('/^' . preg_quote(TL_PATH, '/') . '\//i', '', $return);
		if (!strlen($return) && TL_MODE == 'FE') {
			$return = $this->Environment->httpReferer;
		}
		if (!strlen($return)) {
			$return = (TL_MODE == 'BE') ? 'contao/main.php' : $this->Environment->url;
		}
		return ampersand(urldecode($return), $blnEncodeAmpersands);
	}
	/**
	 * Load a set of language files
	 * @param string
	 * @param boolean
	 */
	protected function loadLanguageFile($strName, $strLanguage = false) {
		if (!$strLanguage) {
			$strLanguage = $GLOBALS['TL_LANGUAGE'];
		}
		foreach ($this->Config->getActiveModules() as $strModule) {
			$strFallback = sprintf('%s/system/modules/%s/languages/en/%s.php', TL_ROOT, $strModule, $strName);
			if (file_exists($strFallback)) {
				include ($strFallback);
			}
			if ($strLanguage == 'en') {
				continue;
			}
			$strFile = sprintf('%s/system/modules/%s/languages/%s/%s.php', TL_ROOT, $strModule, $strLanguage, $strName);
			if (file_exists($strFile)) {
				include ($strFile);
			}
		}
		include (TL_ROOT . '/system/config/langconfig.php');
	}
	/**
	 * Parse a date format string and translate textual representations
	 * @param integer
	 * @param string
	 * @return string
	 */
	protected function parseDate($strFormat, $intTstamp = null) {
		$strModified = str_replace(array (
			'l',
			'D',
			'F',
			'M'
		), array (
			'w::1',
			'w::2',
			'n::3',
			'n::4'
		), $strFormat);
		if (is_null($intTstamp)) {
			$strDate = date($strModified);
		} else {
			$strDate = date($strModified, $intTstamp);
		}
		if (strpos($strDate, '::') === false) {
			return $strDate;
		}
		$strReturn = '';
		$chunks = preg_split("/([0-9]{1,2}::[1-4])/", $strDate, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($chunks as $chunk) {
			list ($index, $flag) = explode('::', $chunk);
			switch ($flag) {
				case 1 :
					$strReturn .= $GLOBALS['TL_LANG']['DAYS'][$index];
					break;
				case 2 :
					$strReturn .= substr($GLOBALS['TL_LANG']['DAYS'][$index], 0, 3);
					break;
				case 3 :
					$strReturn .= $GLOBALS['TL_LANG']['MONTHS'][($index -1)];
					break;
				case 4 :
					$strReturn .= substr($GLOBALS['TL_LANG']['MONTHS'][($index -1)], 0, 3);
					break;
				default :
					$strReturn .= $chunk;
					break;
			}
		}
		return $strReturn;
	}
	/**
	 * Return all error, confirmation and info messages as HTML
	 * @return string
	 */
	protected function getMessages() {
		$strMessages = '';
		$arrGroups = array (
			'TL_ERROR',
			'TL_CONFIRM',
			'TL_INFO'
		);
		foreach ($arrGroups as $strGroup) {
			if (!is_array($_SESSION[$strGroup])) {
				continue;
			}
			$strClass = strtolower($strGroup);
			foreach ($_SESSION[$strGroup] as $strMessage) {
				$strMessages .= sprintf('<p class="%s">%s</p>%s', $strClass, $strMessage, "\n");
			}
			if (!$_POST) {
				$_SESSION[$strGroup] = array ();
			}
		}
		$strMessages = trim($strMessages);
		if (strlen($strMessages)) {
			$strMessages = sprintf('%s<div class="tl_message">%s%s%s</div>', "\n\n", "\n", $strMessages, "\n");
		}
		return $strMessages;
	}
	/**
	 * Urlencode an image path preserving slashes
	 * @param string
	 * @return string
	 */
	protected function urlEncode($strPath) {
		return str_replace('%2F', '/', rawurlencode($strPath));
	}
	/**
	 * Set a cookie
	 * @param string
	 * @param mixed
	 * @param integer
	 * @param string
	 * @param string
	 * @param boolean
	 */
	protected function setCookie($strName, $varValue, $intExpires, $strPath = '', $strDomain = null, $blnSecure = null) {
		if (!strlen($strPath)) {
			$strPath = '/';
		}
		setcookie($strName, $varValue, $intExpires, $strPath, $strDomain, $blnSecure);
	}
}
?>