<?php

namespace Electro\WebServer;

use Electro\ContentRepository\Middleware\ContentServerMiddleware;
use Electro\Debugging\Config\DebugSettings;
use Electro\Debugging\Middleware\AlternateLogoutMiddleware;
use Electro\Debugging\Middleware\WebConsoleMiddleware;
use Electro\ErrorHandling\Middleware\ErrorHandlingMiddleware;
use Electro\Http\Middleware\CompressionMiddleware;
use Electro\Http\Middleware\CsrfMiddleware;
use Electro\Http\Middleware\FetchMiddleware;
use Electro\Http\Middleware\URLNotFoundMiddleware;
use Electro\Http\Middleware\WelcomeMiddleware;
use Electro\Interfaces\Http\MiddlewareAssemblerInterface;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Localization\Middleware\LanguageMiddleware;
use Electro\Localization\Middleware\TranslationMiddleware;
use Electro\Routing\Middleware\PermalinksMiddleware;
use Electro\Sessions\Middleware\SessionMiddleware;

class DefaultMiddlewareAssembler implements MiddlewareAssemblerInterface
{
  /** @var bool */
  private $devEnv;
  /** @var bool */
  private $webConsole;

  public function __construct (DebugSettings $debugSettings)
  {
    $this->devEnv     = $debugSettings->devEnv;
    $this->webConsole = $debugSettings->webConsole;
  }

  function assemble (MiddlewareStackInterface $stack)
  {
    $stack
      ->set ([
        0          => ContentServerMiddleware::class,
        1          => !$this->devEnv ? CompressionMiddleware::class : null,
        2          => $this->webConsole ? WebConsoleMiddleware::class : null,
        3          => TranslationMiddleware::class,
        4          => ErrorHandlingMiddleware::class,
        'session'  => SessionMiddleware::class,
        5          => $this->webConsole ? AlternateLogoutMiddleware::class : null,
        6          => CsrfMiddleware::class,
        7          => LanguageMiddleware::class,
        8          => PermalinksMiddleware::class,
        9          => FetchMiddleware::class,
        'router'   => ApplicationRouterInterface::class,
        10         => WelcomeMiddleware::class,
        'notFound' => URLNotFoundMiddleware::class,
      ]);
  }

}
