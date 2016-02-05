<?php
namespace Selenia\Authentication\Lib;

use Selenia\Interfaces\UserInterface;

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

  function active ($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

  public function findByName ($username)
  {
    return false;
  }

  function id ($set = null)
  {
    if (isset($set))
      $this->id = $set;
    return $this->id;
  }

  function lastLogin ($set = null)
  {
    if (isset($set))
      $this->lastLogin = $set;
    return $this->lastLogin;
  }

  function onLogin ()
  {
    $this->lastLogin = date ('Y-m-d H:i:s');
  }

  function password ($set = null)
  {
    if (isset($set))
      $this->password = password_hash ($set, PASSWORD_BCRYPT);
    return $this->password;
  }

  function realName ($set = null)
  {
    if (isset($set))
      return $this->realName = $set;
    return $this->realName;
  }

  function registrationDate ($set = null)
  {
    if (isset($set))
      $this->registrationDate = $set;
    return $this->registrationDate;
  }

  function role ($set = null)
  {
    if (isset($set))
      $this->role = $set;
    return $this->role;
  }

  function token ($set = null)
  {
    if (isset($set))
      $this->token = $set;
    return $this->token;
  }

  function username ($set = null)
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
