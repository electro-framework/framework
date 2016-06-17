<?php
namespace Electro\Routing\Services;

use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Routing\Lib\BaseRouter;

class MiddlewareStack extends BaseRouter
  implements ApplicationMiddlewareInterface /* for call-signature compatibility */
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
