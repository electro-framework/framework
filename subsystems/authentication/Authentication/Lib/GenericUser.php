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
      'active', 'id', 'lastLogin', 'username', 'realName', 'registrationDate', 'updatedAt', 'role', 'token', 'email',
      'password',
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
    $pass = get ($data, 'password');
    $data = array_merge ($this->getFields (), $data);
    unset ($data['password']);

    if (exists ($pass))
      $data['password'] = password_hash ($pass, PASSWORD_BCRYPT);

    if (array_key_exists ('active', $data)) $this->active = $data['active'];
    if (array_key_exists ('enabled', $data)) $this->enabled = $data['enabled'];
    if (array_key_exists ('realName', $data)) $this->realName = $data['realName'];
    if (array_key_exists ('email', $data)) $this->email = $data['email'];
    if (array_key_exists ('token', $data)) $this->token = $data['token'];
    if (array_key_exists ('username', $data)) $this->username = $data['username'];
    if (array_key_exists ('role', $data)) $this->role = $data['role'];
    if (array_key_exists ('password', $data)) $this->password = $data['password'];
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
