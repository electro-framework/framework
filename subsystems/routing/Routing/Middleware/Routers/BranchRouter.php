<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\MiddlewareInterface;
use Selenia\Routing\RouterBase;

class BranchRouter extends RouterBase implements MiddlewareInterface
{
  public function __construct (array $branches) {
    $this->branches = $branches;
  }


  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next A function with arguments (ServerRequestInterface $request, ResponseInterface $response).
   * @return ResponseInterface
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $path = $request->getUri()->getPath();
    list ($location, $tail) = explode ('/', "$path/", 2);
    if (isset($this->branches [$location]))
      return $this->exec ($this->branches[$location], $request, $response, $next);
    return $next ();
  }
}
