<?php
// Preset application configuration

return [
  'main' => [

    'title'                  => '@',
    'appName'                => '',
    'defaultURI'             => '',
    'favicon'                => 'data:;base64,iVBORw0KGgo=', // Supress http request
    'subApplications'        => [],
    'modules'                => [],

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

    'configPath'             => 'private/config',
    'langPath'               => 'private/resources/lang',
    'moduleLangPath'         => 'resources/lang',
    'templatesPath'          => 'private/resources/templates',
    'modulesPath'            => 'private/app/modules',
    'defaultModulesPath'     => 'vendor',
    'viewPath'               => 'private/resources/views',
    'moduleViewsPath'        => 'resources/views',
    'moduleTemplatesPath'    => 'resources/templates',
    'modelPath'              => 'models',
    'modulePublicPath'       => 'public',
    'appPublicPath'          => 'public_html',
    'frameworkURI'           => 'framework',
    'addonsPath'             => 'framework/addons',

    'routingMapFile'         => '',
    'modelFile'              => '',
    'dataSourcesFile'        => '',
    'SEOFile'                => '',

    'autoControllerClass'    => 'Controller',
    'loginView'              => '',
    'translation'            => false,
    'languages'              => null,
    'defaultLang'            => null,
    'pageSize'               => 15,
    'pageNumberParam'        => 'p',
    'frameworkScripts'       => true,
    'condenseLiterals'       => true,
    'compressOutput'         => true,
    'debugMode'              => false,
    'userModel'              => '',

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