<?php
namespace Selenia\Routing\Services;

use Selenia\Interfaces\Http\MiddlewareStackInterface;
use Selenia\Interfaces\Http\Shared\RootMiddlewareStackInterface;
use Selenia\Routing\Lib\BaseRouter;

class MiddlewareStack extends BaseRouter implements MiddlewareStackInterface, RootMiddlewareStackInterface
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
