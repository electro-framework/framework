<?php
namespace Selenia\Interfaces;

use Selenia\Exceptions\FlashType;
use Selenia\FlashExceptions\SessionException;

interface SessionInterface
{
  /**
   * Memorizes a flash message to be displayed on the next request.
   * @param string $message
   * @param int    $type
   * @param string $title
   */
  function flash ($message, $type = FlashType::WARNING, $title = '');

  /**
   * Retrieves and clears the memorized flash message (if any).
   * @return array|false An array with 'type', 'message' and 'title' keys, or `false` if no flash message exists.
   */
  function getFlash ();

  /**
   * Checks if the user is logged in.
   * @return boolean
   */
  function loggedIn ();

  /**
   * Attempts to log in the user with the given credentials.
   * @param string $username
   * @param string $password
   * @throws SessionException If the login fails.
   */
  function login ($username, $password);

  /**
   * Logs out the user amd clears the session's data.
   */
  function logout ();

  /**
   * Sets the language code for the currently logged in user.
   * @param string $lang
   */
  function setLang ($lang);

}
