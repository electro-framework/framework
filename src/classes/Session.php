<?php
class Session {

  public $isValid = false;
  public $username;
  public $userFullName;
  public $lang;
  public $userTable = 'Users';
  public $userField = 'username';
  public $passField = 'password';

  //--------------------------------------------------------------------------
  public function validate() {
  //--------------------------------------------------------------------------
    return $this->isValid = isset($this->username);
  }

  //--------------------------------------------------------------------------
  public function login() {
  //--------------------------------------------------------------------------
    $username = get($_POST, 'username');
    $password = get($_POST, 'password');
    if (empty($username))
      throw new SessionException(SessionException::MISSING_INFO);
    else {
      $pass = database_query("SELECT $this->passField FROM $this->userTable WHERE $this->userField=?", array($username))->fetchColumn();
      if (!$pass)
        throw new SessionException(SessionException::UNKNOWN_USER);
      else if ($password != $pass)
        throw new SessionException(SessionException::WRONG_PASSWORD);
      else {
        $this->username = $username;
        $this->userFullName = ucfirst($username);
        $this->isValid = true;
      }
    }
  }

  //--------------------------------------------------------------------------
  public function logout() {
    //--------------------------------------------------------------------------
    if (isset($_COOKIE[session_name()]))
      setcookie(session_name(), '', time() - 42000, session_name() . '/');
    session_destroy();
    $this->isValid = false;
    $this->username = null;
  }

  //--------------------------------------------------------------------------
  public function setLang($lang) {
    //--------------------------------------------------------------------------
    $this->lang = $lang;
  }

}
