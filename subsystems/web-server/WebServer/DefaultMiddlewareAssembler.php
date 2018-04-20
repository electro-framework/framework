<?php

namespace Electro\WebServer;

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
        'compress'   => !$this->devEnv ? CompressionMiddleware::class : null,
        'webConsole' => $this->webConsole ? WebConsoleMiddleware::class : null,
        'trans'      => TranslationMiddleware::class,
        'error'      => ErrorHandlingMiddleware::class,
        'session'    => SessionMiddleware::class,
        'altLogout'  => $this->webConsole ? AlternateLogoutMiddleware::class : null,
        'csrf'       => CsrfMiddleware::class,
        'lang'       => LanguageMiddleware::class,
        'permalinks' => PermalinksMiddleware::class,
        'fetch'      => FetchMiddleware::class,
        'router'     => ApplicationRouterInterface::class,
        'welcome'    => WelcomeMiddleware::class,
        'notFound'   => URLNotFoundMiddleware::class,
      ]);
  }

}
