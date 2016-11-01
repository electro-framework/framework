<?php
namespace Electro\FileServer\Config;

use Electro\Application;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\FileServer\Services\ContentRepository;
use Electro\FileServer\Services\FileServerMappings;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\ModuleInterface;
use League\Glide\Responses\PsrResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;

class FileServerModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->share ($injector->make (FileServerMappings::class))
        ->delegate (Server::class, function (ResponseFactoryInterface $responseFactory, Application $app) {
          return ServerFactory::create ([
            'source'   => $app->fileArchivePath,
            'cache'    => $app->imagesCachePath,
            'response' => new PsrResponseFactory ($responseFactory->makeStream (),
              function ($stream) use ($responseFactory) {
                return $responseFactory->makeBody ('', $stream);
              }),
          ]);
        })
        ->share (Server::class)
        ->delegate (ContentRepositoryInterface::class, function (Application $app) {
          $urlBuilder = UrlBuilderFactory::create ($app->fileBaseUrl);
          return new ContentRepository ($urlBuilder);
        })
        ->share (ContentRepositoryInterface::class);
    });
  }

}
