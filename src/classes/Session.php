<?php
class Session {

  /** @var string The session cookie name. */
  public $name;
  public $isValid = false;
  public $username;
  public $userFullName;
  public $lang = null;
  public $userTable = 'Users';
  public $userField = 'username';
  public $passField = 'password';

  //--------------------------------------------------------------------------
  public function validate() {
  //--------------------------------------------------------------------------
    return $this->isValid = isset($this->username);
  }

  //--------------------------------------------------------------------------
  public function login($defaultLang) {
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
        $this->lang = $defaultLang;
      }
    }
  }

  //--------------------------------------------------------------------------
  public function logout() {
    //--------------------------------------------------------------------------
    if (isset($_COOKIE[$this->name]))
      setcookie($this->name, '', time() - 42000, '/');
    session_destroy();
    $_SESSION['sessionInfo'] = null;
  }

  //--------------------------------------------------------------------------
  public function setLang($lang) {
    //--------------------------------------------------------------------------
    $this->lang = $lang;
  }

}
