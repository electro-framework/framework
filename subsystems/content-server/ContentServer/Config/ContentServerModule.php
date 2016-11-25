<?php
namespace Electro\ContentServer\Config;

use Electro\ContentServer\Services\ContentRepository;
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

class ContentServerModule implements ModuleInterface
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
