<?php

namespace Electro\Configuration\Lib;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidEncodingException;
use Dotenv\Exception\InvalidFileException;

class DotEnvLoader {

  /**
   * Loads the project's project.env and.env files, if they exist.
   *
   * Note: this method should be used instead of using the Dotenv library directly, to prevent an error to be thrown when
   * one or more of the files are not found. Due to the way the framework handles errors and warnings, the error suppression
   * mechanism used by the library is not sufficient to prevent an error from occurring when trying to read a non-existing file.
   *
   * Note: it would be so much simpler if Dotenv\Store\File\Reader checked whether the file exists or not before trying to load it.
   * As it is, the @ operator is used to suppress the error, which would still cause an ErrorException to be thrown.
   * Therefore, we check if each of the files exists and only request Dotenv to load those that do.
   *
   * @param string $path The project's base directory.
   * @return void
   * @throws InvalidEncodingException|InvalidFileException
   */
  static function load ($path)
  {
    $paths = [];
    if (file_exists ("$path/project.env"))
      $paths[] = "project.env";
    if (file_exists ("$path/.env"))
      $paths[] = ".env";
    if (!$paths)
      return;

    $dotenv = Dotenv::createImmutable ($path, $paths, false);
    $dotenv->safeLoad ();
  }

}