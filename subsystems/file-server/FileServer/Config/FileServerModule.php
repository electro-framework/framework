<?php
namespace Selenia\FileServer\Config;

use League\Glide\Responses\PsrResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use Selenia\Application;
use Selenia\FileServer\Services\ContentRepository;
use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\ContentRepositoryInterface;
use Selenia\Interfaces\Http\ResponseFactoryInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

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
