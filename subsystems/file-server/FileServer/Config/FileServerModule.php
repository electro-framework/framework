<?php
namespace Electro\FileServer\Config;

use League\Glide\Responses\PsrResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use Electro\Application;
use Electro\FileServer\Services\ContentRepository;
use Electro\FileServer\Services\FileServerMappings;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;

class FileServerModule implements ServiceProviderInterface
{
  function register (InjectorInterface $injector)
  {
    $injector
      ->share ($injector->make (FileServerMappings::class))
      ->delegate (Server::class,
        function (ResponseFactoryInterface $responseFactory, Application $app) {
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
      $urlBuilder = UrlBuilderFactory::create($app->fileBaseUrl);
      return new ContentRepository ($urlBuilder);
    })
    ->share (ContentRepositoryInterface::class);
  }
}
