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
    if (exists (get ($data, 'password')))
      $this->password = password_hash (get ($data, 'password'), PASSWORD_BCRYPT);

    $this->active   = get ($data, 'active', 0);
    $this->enabled  = get ($data, 'enabled', 1);
    $this->realName = get ($data, 'realName');
    $this->email    = get ($data, 'email');
    $this->token    = get ($data, 'token');
    $this->username = get ($data, 'username');
    $this->role     = get ($data, 'role', UserInterface::USER_ROLE_STANDARD);
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
