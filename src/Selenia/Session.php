<?php
namespace Selenia;

use Exception;
use Selenia\Contracts\UserInterface;
use Selenia\Exceptions\SessionException;

class Session
{
  /** @var string The session cookie name. */
  public $name;
  public $isValid = false;
  public $lang    = null;
  /** @var UserInterface|null The logged-in user or null if not logged-in. */
  public $user;
  /** @var string */
  public $userRealName;

  /**
   * The logged-in user or null if not logged-in.
   * @return null|UserInterface
   */
  static function user ()
  {
    global $session;
    return $session->user;
  }

  /**
   * True if the user islogged-in.
   * @return boolean
   */
  static function loggedIn ()
  {
    global $session;
    return (bool)$session->user;
  }

  public function validate ()
  {
    return $this->isValid = isset($this->user);
  }

  public function login ($defaultLang, $username = '', $password = '')
  {
    global $application;
    $username = $username ?: get ($_POST, 'username');
    $password = $password ?: get ($_POST, 'password');
    if (empty($username))
      throw new SessionException(SessionException::MISSING_INFO);
    else {
      $user = new $application->userModel;
      if (!$user->findByName ($username))
        throw new SessionException(SessionException::UNKNOWN_USER);
      else if (!$user->verifyPassword ($password))
        throw new SessionException(SessionException::WRONG_PASSWORD);
      else if (!$user->active ())
        throw new SessionException(SessionException::DISABLED);
      else {
        $this->lang = $defaultLang;
        try {
          $user->onLogin ();
          $this->isValid      = true;
          $this->user         = $user;
          $this->userRealName = $user->realName ();
        } catch (Exception $e) {
          throw new SessionException($e->getMessage ());
        }
      }
    }
  }

  public function logout ()
  {
    if (isset($_COOKIE[$this->name]))
      setcookie ($this->name, '', time () - 42000, '/');
    session_destroy ();
    $_SESSION['sessionInfo'] = null;
  }

  public function setLang ($lang)
  {
    $this->lang = $lang;
  }

}
