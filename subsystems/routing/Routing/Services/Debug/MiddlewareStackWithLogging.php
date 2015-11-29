<?php
namespace Selenia\Routing\Services\Debug;

use Selenia\Routing\Services\MiddlewareStack;

class MiddlewareStackWithLogging extends MiddlewareStack
{
  use \Selenia\Routing\Services\Debug\RouterLoggingAspect;
}
