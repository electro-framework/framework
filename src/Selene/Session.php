<?php
namespace Selene;

use Exception;
use Selene\Contracts\UserInterface;
use Selene\Exceptions\SessionException;

class Session
{
  /** @var string The session cookie name. */
  public $name;
  public $isValid = false;
  public $lang    = null;
  /** @var UserInterface|null The logged-in user or null if not logged-in. */
  public $user;

  public function validate ()
  {
    return $this->isValid = isset($this->user);
  }

  public function login ($defaultLang)
  {
    global $application;
    $username = get ($_POST, 'username');
    $password = get ($_POST, 'password');
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
          $this->isValid = true;
          $this->user    = $user;
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
