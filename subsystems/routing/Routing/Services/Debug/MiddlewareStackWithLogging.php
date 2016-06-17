<?php
namespace Electro\Routing\Services\Debug;

use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Routing\Lib\Debug\BaseRouterWithLogging;

class MiddlewareStackWithLogging extends BaseRouterWithLogging
  implements ApplicationMiddlewareInterface /* for call-signature compatibility */
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
