<?php
namespace Electro\Routing\Lib\Debug;

use PhpKit\WebConsole\Lib\Debug;

trait RouterLoggingTrait
{
  /**
   * Are we currently unwinding the handler stacks due to a thrown exception?
   *
   * @var bool
   */
  static private $unwinding = false;

  /**
   * Note: '<#indent>' must have been already output before calling this method.
   *
   * @param callable    $middleware
   * @param             $req
   * @param             $res
   * @param callable    $next
   * @param string      $nextStepMessage
   * @param string|null $returnMessage If not set, it will have the same value as $nextStepMessage.
   * @param bool        $forceRoutingMode
   * @return mixed
   */
  protected function logMiddlewareBlock (callable $middleware, $req, $res, callable $next, $nextStepMessage = '',
                                         $returnMessage = null, $forceRoutingMode = false)
  {
    $returnMessage = isset($returnMessage) ? $returnMessage : $nextStepMessage;

    $matched = true;
    // $this->routingLogger->write ("<#indent>"); // This must be done be the caller, so that it has full control of it.

    try {
      $finalResponse =
        $middleware ($req, $res, function (...$args) use ($next, &$matched, $nextStepMessage, $forceRoutingMode) {
          // When running middleware, we will unindent only when returning from rest of the chain.
          if ($this->routingEnabled || $forceRoutingMode) {
            $matched = false;
            $this->routingLogger->write ("</#indent>$nextStepMessage");
          }
          return $next (...$args);
        });
      if ($matched)
        $this->routingLogger->write ("</#indent>$returnMessage");
      return $finalResponse;
    }
    catch (\Throwable $e) {
      $this->routingLogger->write ('</#indent>');
      $this->unwind ($e);
    }
      // For PHP 5.6 only
    catch (\Exception $e) {
      $this->routingLogger->write ('</#indent>');
      $this->unwind ($e);
    }
  }

  private function unwind ($e)
  {
    $this->routingLogger->writef ("<#row>%sUnwinding the stack...</#row>",
      self::$unwinding ? '' : '<span class=__alert>' . Debug::getType ($e) . '</span> ');
    self::$unwinding = true;
    throw $e;
  }

}
