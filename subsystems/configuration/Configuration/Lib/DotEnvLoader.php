<?php

namespace Electro\Configuration\Lib;

use Dotenv\Dotenv;

class DotEnvLoader {

  /**
   * Loads the project's project.env and.env files.
   *
   * Note: this method should be used instead of using the Dotenv library directly, to prevent an error to be thrown when
   * one or more of the files are not found. Due to the way the framework handles errors and warnings, the error suppression
   * mechanism used by the library is not sufficient to prevent an error from occurring when trying to read a non-existing file.
   *
   * @param string $path The project's base directory.
   * @return void
   * @throws \ErrorException If an error occurs while loading the files. No error is thrown if the files are not found.
   */
  static function load ($path) {
    $dotenv = Dotenv::createMutable($path, ["project.env", ".env"], false);
    //Note: it would be so much simpler if Dotenv\Store\File\Reader checked whether the file exists or not before trying to load it.
    //As it is, the @ operator is used to suppress the error, which would still cause an ErrorException to be thrown.
    $level = error_reporting ();
    error_reporting (0);
    error_clear_last ();  //just to make sure it's cleared
    try {
      $dotenv->safeLoad ();
    }
    finally {
      error_reporting ($level);
    }
    //If no exception was thrown, but an error occurred, check if it was a file_get_contents warning; if not, throw the error.
    $err = error_get_last ();
    if ($err) {
      if (!str_beginsWith ($err['message'], "file_get_contents"))
        throw new \ErrorException ($err['message'], 1, 1, $err['file'], $err['line']);
      error_clear_last ();
    }
  }
}