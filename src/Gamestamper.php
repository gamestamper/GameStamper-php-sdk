<?php
/**
 * File modified to comply with Gamestamper requirements by Playgistics DBA Gamestamper, LLC 2011-07-27.
 */

/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require_once "base_gamestamper.php";

/**
 * Extends the BaseGamestamper class with the intent of using
 * PHP sessions to store user ids and access tokens.
 */
class Gamestamper extends BaseGamestamper
{
  /**
   * Identical to the parent constructor, except that
   * we start a PHP session to store the user ID and
   * access token if during the course of execution
   * we discover them.
   *
   * @param Array $config the application configuration.
   * @see BaseGamestamper::__construct in gamestamper.php
   */
  public function __construct($config) {
    if (!session_id()) {
      session_start();
    }
    if (isset($config['access_token'])) {
	$this->setSession(array('access_token' => $config['access_token']), false, false);
	$this->setAccessToken($config['access_token']);
    }
    parent::__construct($config);
  }

  protected static $kSupportedKeys =
    array('state', 'code', 'access_token', 'user_id');

  /**
   * Provides the implementations of the inherited abstract
   * methods.  The implementation uses PHP sessions to maintain
   * a store for authorization codes, user ids, CSRF states, and
   * access tokens.
   */
  protected function setPersistentData($key, $value) {
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to setPersistentData.');
      return;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    $_SESSION[$session_var_name] = $value;
  }

  protected function getPersistentData($key, $default = false) {
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to getPersistentData.');
      return $default;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    return isset($_SESSION[$session_var_name]) ?
      $_SESSION[$session_var_name] : $default;
  }

  protected function clearPersistentData($key) {
    if (!in_array($key, self::$kSupportedKeys)) {
      self::errorLog('Unsupported key passed to clearPersistentData.');
      return;
    }

    $session_var_name = $this->constructSessionVariableName($key);
    unset($_SESSION[$session_var_name]);
  }

  protected function clearAllPersistentData() {
    foreach (self::$kSupportedKeys as $key) {
      $this->clearPersistentData($key);
    }
  }

  protected function constructSessionVariableName($key) {
    return implode('_', array('fb',
                              $this->getAppId(),
                              $key));
  }

  /**
     * Set the Session.
     *
     * @param Array   $session      the session
     * @param Boolean $write_cookie indicate if a cookie should be written. this
     * value is ignored if cookie support has been disabled.
     */
    public function setSession($session=null, $write_cookie=true, $validateSession = true) {
	$session = $validateSession ? $this->validateSessionObject($session) : $session;
	$this->sessionLoaded = true;
	$this->session = $session;
	if ($write_cookie) {
	    $this->setCookieFromSession($session);
	}
	return $this;
    }


    public function doAuthorization($url) {
	    $graph = $this->DOMAIN_MAP['graph'];
	    echo '<html><head><script type="text/javascript">top.location = "'.$url.'&graph='.$graph.'";</script></head></html>';
    }

    public function hasPermissions($perms) {
	if (!is_array($perms)) {
	    $perms = explode(',',$perms);
	}
	$s = $this->getSignedRequest();
	//defaults to false if no session
	$currentScopes = isset($s['scopes']) ? explode(',', $s['scopes']) : array();
	return count(array_diff($perms,$currentScopes))==0;
    }
}
