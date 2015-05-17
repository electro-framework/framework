<?php
namespace Selene\Contracts;

interface UserInterface
{
  /**
   * The administration role.
   *
   * This role acts as a super-user that can access all features.
   * This is usually reserved for the application's developers.
   */
  const USER_ROLE_ADMIN = 'admin';
  /**
   * A standard user role.
   *
   * Users with this role have access to most features.
   * This is the common role for backoffice users, for instance.
   */
  const USER_ROLE_STANDARD = 'standard';
  /**
   * A guest user role.
   *
   * Users with this role have access to a restricted set of features.
   * This is the common role for the end-users of your app (excluding your client).
   */
  const USER_ROLE_GUEST = 'guest';

  /**
   * Finds the user record searching by the username (which may or may not be the primary key).
   * @param string $username
   * @return bool True if the user was found.
   */
  function findByName ($username);

  /**
   * Hahses the given password amd matches it against the user's previously hashed password.
   * @param string $password  The password as written by the user on the login form.
   * @return bool True if the passwords match.
   */
  function verifyPassword ($password);

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
   * Hashes the given argument and sets it as being the login password.
   *
   * @param string $set The original password as written by the user.
   * @return string The hashed password.
   *                <p>This can be useful for checking if a user already as a password.
   *                <p>For checking if a password matches the user's, use {@see verifyPassword()} instead.
   */
  function password ($set = null);

  /**
   * Gets or sets a unique identifier for the user that can be used to confirm its identity in:
   * <li> password reset emails;
   * <li> registration confirmation emails;
   * <li> URL/header parameters for authenticationless access to the application.
   * @param string $set A datetime in <kbd>'YYYY-MM-DD hh:mm:ss'</kbd> format.
   * @return string
   */
  function token ($set = null);

  /**
   * Gets or sets the date and time when the user record was created.
   *
   * @param string $set A datetime in <kbd>'YYYY-MM-DD hh:mm:ss'</kbd> format.
   * @return string
   */
  function registrationDate ($set = null);

  /**
   * Gets or sets the date and time of the user's last login.
   *
   * @param string $set A datetime in <kbd>'YYYY-MM-DD hh:mm:ss'</kbd> format.
   * @return string
   */
  function lastLogin ($set = null);

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
   * > If you're not using this feature on your app (and your database users have no `active` field) you should always
   * return true.
   *
   * @param bool $set A setter value.
   * @return string
   */
  function active ($set = null);

  /**
   * Called whenever the user logs in.
   *
   * You can use this method to update a last access timestamp, for instance.
   * <p>If you throw an exception, the login is aborted and the exception message is displayed.
   */
  function onLogin ();

}