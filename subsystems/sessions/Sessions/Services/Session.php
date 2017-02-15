<?php

namespace Electro\Sessions\Services;

use Electro\Authentication\Lib\GenericUser;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Traits\AssignableTrait;

class Session implements SessionInterface
{
  use AssignableTrait;

  public $isValid = false;
  /** @var string The session cookie name. */
  public $name;
  /** @var GenericUser The logged-in user or null if not logged-in. */
  public $user;
  /** @var array */
  private $data;
  /**
   * The language code for the currently enabled language.
   *
   * @var string|null
   */
  private $lang = null;
  /** @var array */
  private $newFlash;
  /** @var array */
  private $prevFlash;

  function __construct ()
  {
    $this->data      = [];
    $this->prevFlash = [];
    $this->newFlash  = [];
  }

  function __debugInfo ()
  {
    return [
      'Session Name'        => $this->name,
      'Language'            => $this->lang,
      'Session Data'        => $this->data,
      'Flash Data'          => $this->newFlash,
      'Previous Flash Data' => $this->prevFlash,
      'Current User'        => $this->user ?: '<raw><i>not logged in</i></raw>',
    ];
  }

  /**
   * serialize() checks if your class has a function with the magic name __sleep.
   * If so, that function is executed prior to any serialization.
   * It can clean up the object and is supposed to return an array with the names of all variables of that object that
   * should be serialized. If the method doesn't return anything then NULL is serialized and E_NOTICE is issued. The
   * intended use of __sleep is to commit pending data or perform similar cleanup tasks. Also, the function is useful
   * if you have very large objects which do not need to be saved completely.
   *
   * @return array|NULL
   * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
   */
  function __sleep ()
  {
    $this->prevFlash = $this->newFlash;
    return ['isValid', 'lang', 'name', 'user', 'data', 'prevFlash'];
  }

  /**
   * unserialize() checks for the presence of a function with the magic name __wakeup.
   * If present, this function can reconstruct any resources that the object may have.
   * The intended use of __wakeup is to reestablish any database connections that may have been lost during
   * serialization and perform other reinitialization tasks.
   *
   * @return void
   * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
   */
  function __wakeup ()
  {
    $this->newFlash = [];
  }

  function all ()
  {
    return array_merge ($this->data, $this->prevFlash);
  }

  function flash ($key, $value)
  {
    $this->newFlash[$key] = $value;
  }

  function flashInput (array $value)
  {
    if (!is_array ($value))
      throw new \InvalidArgumentException("Not an array");
    $this->flash ('#flashInput', $value);
  }

  function flashMessage ($message, $type = FlashType::INFO, $title = '')
  {
    $this->flash ('#flashMessage', compact ('message', 'type', 'title'));
  }

  function getFlashMessage ()
  {
    return $this['#flashMessage'] ?: null;
  }

  function getFlashed ($name, $default = null)
  {
    return get ($this->prevFlash, $name, $default);
  }

  function getLang ()
  {
    return $this->lang;
  }

  function setLang ($lang)
  {
    $this->lang = $lang;
  }

  function getOldInput ($key = null, $default = null)
  {
    $old = $this['#flashInput'];
    return isset($key)
      ? (isset($old[$key]) ? $old[$key] : $default)
      : ($old ?: $default);
  }

  function hasOldInput ($key = null)
  {
    $old = $this['#flashInput'];
    return isset($old) && (is_null ($key) || array_key_exists ($key, $old));
  }

  function isFlashed ($key)
  {
    return array_key_exists ($key, $this->prevFlash);
  }

  function loggedIn ()
  {
    return (bool)$this->user;
  }

  function logout ()
  {
    if (isset($_COOKIE[$this->name]))
      setcookie ($this->name, '', time () - 42000, '/');
    session_destroy ();
    $_SESSION['#data'] = null;
  }

  public function offsetExists ($offset)
  {
    return array_key_exists ($offset, $this->prevFlash) || array_key_exists ($offset, $this->data);
  }

  public function offsetGet ($offset)
  {
    return
      array_key_exists ($offset, $this->prevFlash)
        ? $this->prevFlash[$offset]
        : (
      array_key_exists ($offset, $this->data)
        ? $this->data[$offset]
        : null
      );
  }

  public function offsetSet ($offset, $value)
  {
    if (array_key_exists ($offset, $this->prevFlash))
      $this->prevFlash[$offset] = $value;
    else $this->data[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    if (array_key_exists ($offset, $this->prevFlash))
      unset ($this->prevFlash[$offset]);
    else unset ($this->data[$offset]);
  }

  function previousUrl ()
  {
    return $this->getFlashed ('#previousUrl');
  }

  function reflash (array $keys = null)
  {
    $this->newFlash = array_merge ($this->newFlash, $keys
      ? array_only ($this->prevFlash, $keys)
      : $this->prevFlash
    );
  }

  function reflashPreviousUrl ()
  {
    $this->reflash (['#previousUrl']);
  }

  function regenerateToken ()
  {
    $this['#token'] = bin2hex (openssl_random_pseudo_bytes (16));
  }

  function setPreviousUrl ($url)
  {
    $this->flash ('#previousUrl', strval ($url));
  }

  function setUser (UserInterface $user)
  {
    $this->user = $user;
//    $this->user    = array_toClass ([
//      'id'               => $user->idField (),
//      'active'           => $user->activeField (),
//      'username'         => $user->usernameField (),
//      'password'         => $user->passwordField (),
//      'token'            => $user->tokenField (),
//      'registrationDate' => $user->registrationDateField (),
//      'lastLogin'        => $user->lastLoginField (),
//      'role'             => $user->roleField (),
//      'realName'         => $user->realNameField (),
//    ], GenericUser::class);
    $this->isValid = true;
  }

  function token ()
  {
    return $this['#token'];
  }

  function user ()
  {
    return $this->user;
  }

  function validate ()
  {
    return $this->isValid = isset($this->user);
  }

}
