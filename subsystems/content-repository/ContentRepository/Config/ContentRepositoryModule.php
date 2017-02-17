<?php

namespace Electro\ContentRepository\Config;

use Auryn\ConfigException;
use Electro\ContentRepository\Drivers\S3Filesystem;
use Electro\ContentRepository\Lib\GlideResponseFactory;
use Electro\ContentRepository\Services\ContentRepository;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\ConsoleProfile;
use Electro\Profiles\WebProfile;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use Psr\Log\LoggerInterface;

class ContentRepositoryModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class, ConsoleProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
        $injector
          ->delegate (Server::class, function (ContentRepositorySettings $settings) use ($injector) {
            switch ($settings->driver) {
              case 'local':
                $source  = $settings->fileArchivePath;
                $cache   = $settings->imagesCachePath;
                $baseUri = null;
                break;
              case 'S3':
                $s3Settings = $injector->make (S3Settings::class);
                $source     = $injector->make (S3Filesystem::class);
                $cache      = $settings->imagesCachePath;
                $baseUri    = $s3Settings->getBaseUrl ();
                break;
              default:
                throw new ConfigException("Invalid content repository driver: $settings->driver");
            }
            return ServerFactory::create ([
              'source'                     => $source,
              'cache'                      => $cache,
              'base_url'                   => $baseUri,
              'group_cache_in_folders'     => true,
              'cache_with_file_extensions' => true,
              'max_image_size'             => $settings->imageMaxSize,
              'response'                   => new GlideResponseFactory ($injector),
              //'base_url' => '',
            ]);
          })
          ->share (Server::class)
          ->delegate (ContentRepositoryInterface::class,
            function (Server $server, ContentRepositorySettings $settings, LoggerInterface $logger) {
              $urlBuilder = UrlBuilderFactory::create ($settings->fileBaseUrl);
              return new ContentRepository ($server, $urlBuilder, $logger);
            })
          ->share (ContentRepositoryInterface::class)
          ->share (ContentRepositorySettings::class)
          ->share (S3Filesystem::class);
      });
  }

}
