<?php
namespace Selene\Lib;

class Composer
{
  /** @var array */
  protected $config;

  function __construct ()
  {
    $config = file_get_contents ('composer.json');
    if (!$config)
      throw new \RuntimeException("composer.json was not found");
    $this->config = json_decode($config);
    var_dump($this->config);
    exit;
  }

}
