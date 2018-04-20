<?php
namespace Electro\Authentication\Lib;

use Electro\Interfaces\UserInterface;

class GenericUser implements UserInterface
{
  public $active;
  public $email;
  public $enabled;
  public $id;
  public $lastLogin;
  public $password;
  public $realName;
  public $registrationDate;
  public $role;
  public $token;
  public $updatedAt;
  public $username;

  function __sleep ()
  {
    return [
      'active', 'id', 'lastLogin', 'realName', 'registrationDate', 'updatedAt', 'role', 'token', 'email', 'password',
      'enabled',
    ];
  }

  function __wakeup ()
  {

  }

  public function findByEmail ($email)
  {
    return false;
  }

  public function findById ($id)
  {
    return false;
  }

  public function findByName ($username)
  {
    return false;
  }

  public function findByToken ($token)
  {
    return false;
  }

  public function getFields ()
  {
    return [
      'active'           => $this->active,
      'id'               => $this->id,
      'lastLogin'        => $this->lastLogin,
      'realName'         => $this->realName,
      'registrationDate' => $this->registrationDate,
      'updatedAt'        => $this->updatedAt,
      'role'             => $this->role,
      'token'            => $this->token,
      'username'         => $this->username,
      'email'            => $this->email,
      'password'         => '',
      'enabled'          => $this->enabled,
    ];
  }

  function getUsers ()
  {
    return [];
  }

  function mergeFields ($data)
  {
  }

  function onLogin ()
  {
    return false;
  }

  function remove ()
  {
  }

  function submit ()
  {
  }

  function verifyPassword ($password)
  {
    return password_verify ($password, $this->password);
  }
}
