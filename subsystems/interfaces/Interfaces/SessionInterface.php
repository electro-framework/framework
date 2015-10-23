<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\UriInterface;
use Selenia\Exceptions\Flash\SessionException;
use Selenia\Exceptions\FlashType;

interface SessionInterface extends \ArrayAccess, AssignableInterface
{
  /**
   * Returns all session data, including flashed data.
   * @return array
   */
  function all ();

  /**
   * Saves a flash value that will be available on the next request only.
   * <p>Note: the saved value will ne be readable on the current request.
   * @param string $key
   * @param mixed  $value
   * @return mixed
   */
  function flash ($key, $value);

  /**
   * Flash an input array to the session.
   * @param array $value
   * @return mixed
   */
  function flashInput (array $value);

  /**
   * Saves a flash message to be displayed on the next request.
   * @param string $message
   * @param int    $type
   * @param string $title
   */
  function flashMessage ($message, $type = FlashType::WARNING, $title = '');

  /**
   * Retrieves the memorized flash message (if any).
   * @return array An array with 'type', 'message' and 'title' keys, or an empty array if no flash message exists.
   */
  function getFlashMessage ();

  /**
   * Retrieves the value for the given key from the flash storage.
   * <p>Use this only if you need to make sure the value really comes from the flashed data, otherwise just read it
   * directly from the session object using array access syntax.
   * @param string $name
   * @param mixed  $default
   * @return mixed
   */
  function getFlashed ($name, $default = null);

  /**
   * Get the requested item from the flashed input array, or get all input data.
   * @param string|null $key     If not specified, an array is returned, otherwise the value of the specified key is
   *                             returned.
   * @param mixed       $default The value to be returned if the given does not exist, or if no input is available.
   * @return array|mixed
   */
  function getOldInput ($key = null, $default = null);

  /**
   * Determine if the session contains old input.
   * @param null|string $key If not specified, the method checks if the session contains any old input.
   *                         Otherwise, it checks if the given key exists on the old input.
   * @return mixed
   */
  function hasOldInput ($key = null);

  /**
   * Determine if the session contains the specified flashed key.
   * @param string $key
   * @return boolean
   */
  function isFlashed ($key);

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
   * Get the previous (intended) URL from the session.
   * @return string|null
   */
  function previousUrl ();

  /**
   * Keep the previous flash data for an additional request.
   * @param array $keys A list of keys to reflash. If not given, all of the session flash data will be kept.
   */
  function reflash (array $keys = null);

  /**
   * Regenerate the CSRF token value.
   */
  function regenerateToken ();

  /**
   * Sets the language code for the currently logged in user.
   * @param string $lang
   */
  function setLang ($lang);

  /**
   * Gets the language code for the currently logged in user.
   * @return string|null <kbd>null</kbd> if no language is enabled.
   */
  function getLang ();

  /**
   * Set the "previous" (intended) URL in the session.
   * @param string|UriInterface $url
   */
  function setPreviousUrl ($url);

  /**
   * Get the CSRF token value.
   * @return string
   */
  function token ();
}
