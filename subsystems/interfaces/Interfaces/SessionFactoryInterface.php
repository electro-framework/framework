<?php
namespace Selenia\Interfaces;

interface SessionFactoryInterface
{
  /**
   * Retrieves an instance of a SessionInterface-compatible object, either from the session store or by creating a new
   * blank instance if no session instance has been created before for the current HTTP client or if the previous
   * session has expired.
   * @return SessionInterface
   */
  function get ();

}
