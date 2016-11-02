<?php
namespace Electro\ContentServer\Config;

use Electro\ContentServer\Services\ContentRepository;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\ModuleInterface;
use League\Glide\Responses\PsrResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class ContentServerModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->delegate (Server::class,
          function (ResponseFactoryInterface $responseFactory, ContentServerSettings $settings) {
            return ServerFactory::create ([
              'source'   => $settings->fileArchivePath (),
              'cache'    => $settings->imagesCachePath (),
              'response' => new PsrResponseFactory ($responseFactory->makeStream (),
                function ($stream) use ($responseFactory) {
                  return $responseFactory->makeBody ('', $stream);
                }),
            ]);
          })
        ->share (Server::class)
        ->delegate (ContentRepositoryInterface::class, function (ContentServerSettings $settings) {
          $urlBuilder = UrlBuilderFactory::create ($settings->fileBaseUrl ());
          return new ContentRepository ($urlBuilder);
        })
        ->share (ContentRepositoryInterface::class)
        ->share (ContentServerSettings::class);
    });
  }

}
