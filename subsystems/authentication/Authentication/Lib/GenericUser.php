<?php
namespace Electro\Authentication\Lib;

use Electro\Interfaces\UserInterface;

class GenericUser implements UserInterface
{
  public $active;
  public $id;
  public $lastLogin;
  public $password;
  public $realName;
  public $registrationDate;
  public $role;
  public $token;
  public $username;

  function activeField ($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

  public function findById ($id)
  {
    return false;
  }

  public function findByName ($username)
  {
    return false;
  }

  public function getRecord ()
  {
    return [
      'active'           => $this->activeField (),
      'id'               => $this->idField (),
      'lastLogin'        => $this->lastLoginField (),
      'realName'         => $this->realNameField (),
      'registrationDate' => $this->registrationDateField (),
      'role'             => $this->roleField (),
      'token'            => $this->tokenField (),
      'username'         => $this->usernameField (),
    ];
  }

  function idField ($set = null)
  {
    if (isset($set))
      $this->id = $set;
    return $this->id;
  }

  function lastLoginField ($set = null)
  {
    if (isset($set))
      $this->lastLogin = $set;
    return $this->lastLogin;
  }

  function onLogin ()
  {
    $this->lastLogin = date ('Y-m-d H:i:s');
  }

  function passwordField ($set = null)
  {
    if (isset($set))
      $this->password = password_hash ($set, PASSWORD_BCRYPT);
    return $this->password;
  }

  function realNameField ($set = null)
  {
    if (isset($set))
      return $this->realName = $set;
    return $this->realName;
  }

  function registrationDateField ($set = null)
  {
    if (isset($set))
      $this->registrationDate = $set;
    return $this->registrationDate;
  }

  function roleField ($set = null)
  {
    if (isset($set))
      $this->role = $set;
    return $this->role;
  }

  function tokenField ($set = null)
  {
    if (isset($set))
      $this->token = $set;
    return $this->token;
  }

  function usernameField ($set = null)
  {
    if (isset($set)) {
      $this->username = $set;
      if (is_null ($this->realName))
        $this->realName = ucfirst ($this->username);
    }
    return $this->username;
  }

  function verifyPassword ($password)
  {
    return password_verify ($password, $this->password);
  }
}
