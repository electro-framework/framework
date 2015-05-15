<?php
namespace Selene\Contracts;

interface UserInterface
{
  /**
   * The administration role.
   */
  const USER_ROLE_ADMIN = 'admin';
  /**
   * A standard user role.
   */
  const USER_ROLE_STANDARD = 'standard';

  /**
   * Finds the user record searching by the username (which may or may not be the primary key).
   * @param string $username
   * @return bool True if the user was found.
   */
  function findByName ($username);

  /**
   * Gets or sets the user record's primary key.
   *
   * > Note: it may be the same as the username or it may be a numeric id.
   *
   * @param string $set A setter value.
   * @return string
   */
  function id ($set = null);

  /**
   * Gets the user's "real" name, which may be displayed on the application UI.
   *
   * > This may be the same as the username.
   *
   * @return string
   */
  function realName ();

  /**
   * Gets or sets the login username.
   *
   * > This may actually be an email address, for instance.
   *
   * @param string $set A setter value.
   * @return string
   */
  function username ($set = null);

  /**
   * Gets or sets the login password.
   *
   * @param string $set A setter value.
   * @return string
   */
  function password ($set = null);

  /**
   * Gets or sets the user role.
   *
   * > The predefined roles are set as constants on {@see UserInterface}.
   *
   * @param string $set A setter value.
   * @return string
   */
  function role ($set = null);

  /**
   * Gets or sets the active state of the user.
   *
   * > Only active users may log in.
   *
   * @param bool $set A setter value.
   * @return string
   */
  function active ($set = null);

}