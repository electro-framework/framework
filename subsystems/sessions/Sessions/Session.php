<?php
namespace Selenia\Sessions;

use Exception;
use Selenia\Contracts\UserInterface;
use Selenia\Exceptions\FlashType;
use Selenia\FlashExceptions\SessionException;
use Selenia\Interfaces\SessionInterface;

class Session implements SessionInterface
{
  public $isValid = false;
  public $lang    = null;
  /** @var string The session cookie name. */
  public $name;
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
    return isset($session) ? $session->user : null;
  }

  function flash ($message, $type = FlashType::WARNING, $title = '')
  {
    $_SESSION['formStatus']  = $type;
    $_SESSION['formMessage'] = $message;
    $_SESSION['formTitle']   = $title;
  }

  function loggedIn ()
  {
    return (bool)$this->user;
  }

  function login ($username, $password)
  {
    global $application;
    if (empty($username))
      throw new SessionException(SessionException::MISSING_INFO);
    else {
      /** @var UserInterface $user */
      $user = new $application->userModel;
      if (!$user->findByName ($username))
        throw new SessionException(SessionException::UNKNOWN_USER);
      else if (!$user->verifyPassword ($password))
        throw new SessionException(SessionException::WRONG_PASSWORD);
      else if (!$user->active ())
        throw new SessionException(SessionException::DISABLED);
      else {
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

  function logout ()
  {
    if (isset($_COOKIE[$this->name]))
      setcookie ($this->name, '', time () - 42000, '/');
    session_destroy ();
    $_SESSION['sessionInfo'] = null;
  }

  function setLang ($lang)
  {
    $this->lang = $lang;
  }

  function getFlash ()
  {
    if (isset($_SESSION['formStatus'])) {
      $r = [
        'type'    => $_SESSION['formStatus'],
        'message' => $_SESSION['formMessage'],
        'title'   => $_SESSION['formTitle'],
      ];
      unset ($_SESSION['formStatus']);
      unset ($_SESSION['formMessage']);
      unset ($_SESSION['formTitle']);
      return $r;
    }
    return false;
  }

  function validate ()
  {
    return $this->isValid = isset($this->user);
  }

}
