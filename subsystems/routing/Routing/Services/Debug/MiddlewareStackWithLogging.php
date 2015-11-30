<?php
namespace Selenia\Routing\Services\Debug;

use Selenia\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Selenia\Routing\Lib\Debug\BaseRouterWithLogging;

class MiddlewareStackWithLogging extends BaseRouterWithLogging
  implements ApplicationMiddlewareInterface /* for call-signature compatibility */
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
