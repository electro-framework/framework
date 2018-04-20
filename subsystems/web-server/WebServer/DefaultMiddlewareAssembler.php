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
use Electro\Interfaces\ProfileInterface;
use Electro\Kernel\Services\Kernel;
use Electro\Localization\Middleware\LanguageMiddleware;
use Electro\Localization\Middleware\TranslationMiddleware;
use Electro\Navigation\Middleware\NavigationMiddleware;
use Electro\Profiles\WebProfile;
use Electro\Routing\Middleware\PermalinksMiddleware;
use Electro\Sessions\Middleware\SessionMiddleware;

class DefaultMiddlewareAssembler implements MiddlewareAssemblerInterface
{
  /** @var bool */
  private $devEnv;
  /** @var ProfileInterface */
  private $profile;
  /** @var bool */
  private $webConsole;

  public function __construct (DebugSettings $debugSettings, Kernel $kernel)
  {
    $this->devEnv     = $debugSettings->devEnv;
    $this->webConsole = $debugSettings->webConsole;
    $this->profile    = $kernel->getProfile ();
  }

  function assemble (MiddlewareStackInterface $stack)
  {
    // $isWebApp will be false if the profile is ApiProfile.
    $isWebApp = $this->profile instanceof WebProfile;
    $stack
      ->set ([
        0            => !$this->devEnv ? CompressionMiddleware::class : null,
        1            => $this->webConsole ? WebConsoleMiddleware::class : null,
        2            => TranslationMiddleware::class,
        3            => ErrorHandlingMiddleware::class,
        'session'    => SessionMiddleware::class,
        'navigation' => $isWebApp ? NavigationMiddleware::class : null,
        4            => $this->webConsole ? AlternateLogoutMiddleware::class : null,
        5            => CsrfMiddleware::class,
        6            => LanguageMiddleware::class,
        7            => PermalinksMiddleware::class,
        8            => FetchMiddleware::class,
        'router'     => ApplicationRouterInterface::class,
        9            => $isWebApp ? WelcomeMiddleware::class : null,
        'notFound'   => URLNotFoundMiddleware::class,
      ]);
  }

}
