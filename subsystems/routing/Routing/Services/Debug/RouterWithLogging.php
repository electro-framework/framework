<?php
namespace Selenia\Routing\Services\Debug;

use Selenia\Routing\Services\Router;

class RouterWithLogging extends Router
{
  use \Selenia\Routing\Services\Debug\RouterLoggingAspect;
}
