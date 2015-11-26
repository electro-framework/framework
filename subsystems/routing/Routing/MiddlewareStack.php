<?php
namespace Selenia\Routing;

use Selenia\Interfaces\Http\MiddlewareStackInterface;
use Selenia\Interfaces\Http\Shared\RootMiddlewareStackInterface;

class MiddlewareStack extends BaseRouter implements MiddlewareStackInterface, RootMiddlewareStackInterface
{
  /**
   * Disable the routing capability for routers acting as middleware-only runners.
   */
  public $routingEnabled = false;
}
