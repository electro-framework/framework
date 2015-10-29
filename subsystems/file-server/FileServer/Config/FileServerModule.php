<?php
namespace Selenia\FileServer\Config;

use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class FileServerModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share (FileServerMappings::ref);
  }
}
