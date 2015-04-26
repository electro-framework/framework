<?php
// Preset application configuration

return [
  'main' => [

    'title'                  => '@',
    'appName'                => '',
    'defaultURI'             => '',
    'favicon'                => 'data:;base64,iVBORw0KGgo=', // Supress http request

    'homeIcon'               => '',
    'homeTitle'              => 'Home',

    'imageArchivePath'       => 'private/storage/images',
    'fileArchivePath'        => 'private/storage/files',
    'inlineArchivePath'      => 'private/storage/inline_assets',
    'galleryPath'            => 'private/storage/gallery',
    'cachePath'              => 'private/storage/cache',
    'imagesCachePath'        => 'private/storage/cache/images',
    'stylesCachePath'        => 'private/storage/cache',
    'CSS_CachePath'          => 'private/storage/cache',

    'frontendConfig'         => 'app/src/config',
    'config'                 => 'private/config',
    'i18nPath'               => '../private/resources/lang',
    'templatesPath'          => 'private/resources/templates',
    'modulesPath'            => 'private/app/modules',
    'defaultModulesPath'     => 'private/selene/modules',
    'viewPath'               => 'private/resources/views',
    'moduleViewPath'         => '',
    'modelPath'              => 'models',
    'publicPath'             => '../',

    'frameworkPublicPath'    => 'public',
    'frameworkURI'           => 'framework',
    'addonsPath'             => 'framework/addons',

    'routingMapFile'         => '',
    'modelFile'              => '',
    'dataSourcesFile'        => '',
    'SEOFile'                => '',

    'autoControllerClass'    => 'Controller',
    'translation'            => false,
    'languages'              => null,
    'defaultLang'            => null,
    'pageSize'               => 15,
    'pageNumberParam'        => 'p',
    'frameworkScripts'       => true,
    'condenseLiterals'       => true,
    'compressOutput'         => true,
    'debugMode'              => false,

    'imageRedirection'       => true,
    'URINotFoundURL'         => false,
    'oldIEWarning'           => '',
    'productionIP'           => '',
    'googleAnalyticsAccount' => '',

    'packScripts'            => false,
    'packCSS'                => false,
    'resourceCaching'        => false,

    'globalSessions'         => false,
    'autoSession'            => true,
    'isSessionRequired'      => false,

  ]
];