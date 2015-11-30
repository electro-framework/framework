<?php
namespace Selenia\Routing\Services;

use Selenia\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Selenia\Routing\Lib\BaseRouter;

class MiddlewareStack extends BaseRouter
  implements ApplicationMiddlewareInterface /* for call-signature compatibility */
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
