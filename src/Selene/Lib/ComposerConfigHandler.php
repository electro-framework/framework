<?php
namespace Selene\Lib;

class ComposerConfigHandler
{
  /** @var array */
  public $data;
  /** @var  string */
  protected $path;

  function __construct ($composerJsonPath = null)
  {
    $this->path = $composerJsonPath ?: 'composer.json';
    $config     = file_get_contents ($this->path);
    if (!$config)
      throw new \RuntimeException("$this->path was not found");
    $this->data = json_decode ($config);
  }

  function psr4 ()
  {
    $psr4 = $this->data->autoload->{'psr-4'};
    if (!$psr4) $this->data->autoload->{'psr-4'} = $psr4 = new \StdClass;

    return $psr4;
  }

  function save ()
  {
    $json = json_encode ($this->data, JSON_PRETTY_PRINT);
    file_put_contents ($this->path, $json);
  }
}
