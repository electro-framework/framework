<?php

namespace Electro\ContentRepository\Config;

use Electro\ContentRepository\Services\ContentRepository;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\WebProfile;
use League\Glide\Responses\PsrResponseFactory;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;

class ContentRepositoryModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector
          ->delegate (Server::class, function (ResponseFactoryInterface $responseFactory,
                                               ContentRepositorySettings $settings) {
            return ServerFactory::create ([
              'source'                     => $settings->fileArchivePath,
              'cache'                      => $settings->imagesCachePath,
              'group_cache_in_folders'     => true,
              'cache_with_file_extensions' => true,
              'max_image_size'             => $settings->imageMaxSize,
              'response'                   => new PsrResponseFactory ($responseFactory->make (),
                function ($stream) use ($responseFactory) {
                  return $responseFactory->makeBodyStream ('', $stream);
                }),
              //'base_url' => '',
            ]);
          })
          ->share (Server::class)
          ->delegate (ContentRepositoryInterface::class, function (Server $server,
                                                                   ContentRepositorySettings $settings) {
            $urlBuilder = UrlBuilderFactory::create ($settings->fileBaseUrl);
            return new ContentRepository ($server, $urlBuilder);
          })
          ->share (ContentRepositoryInterface::class)
          ->share (ContentRepositorySettings::class);
      });
  }

}
