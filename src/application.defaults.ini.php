<?php
// Preset application configuration

return [
  'main' => [

    'title'                  => '@',
    'appName'                => '',
    'homeURI'                => '',
    'favicon'                => 'data:;base64,iVBORw0KGgo=', // Supress http request
    'subApplications'        => [],
    'modules'                => [],

    'homeIcon'               => '',
    'homeTitle'              => 'Home',

    'storagePath'            => 'storage',
    'imageArchivePath'       => 'storage/images',
    'fileArchivePath'        => 'storage/files',
    'cachePath'              => 'storage/cache',
    'imagesCachePath'        => 'storage/cache/images',
    'langPath'               => 'resources/lang',
    'templatesPath'          => 'resources/templates',
    'modulesPath'            => 'modules',
    'viewPath'               => 'resources/views',

    'configPath'             => 'config',
    'moduleLangPath'         => 'resources/lang',
    'defaultModulesPath'     => 'vendor',
    'moduleViewsPath'        => 'resources/views',
    'moduleTemplatesPath'    => 'resources/templates',
    'modelPath'              => 'models',
    'modulePublicPath'       => 'public',
    'frameworkURI'           => 'framework',
    'addonsPath'             => 'framework/addons',

    'routingMapFile'         => '',
    'modelFile'              => '',
    'dataSourcesFile'        => '',
    'SEOFile'                => '',

    'autoControllerClass'    => 'Selene\Controller',
    'loginView'              => '',
    'translation'            => false,
    'languages'              => null,
    'defaultLang'            => null,
    'pageSize'               => 99999,
    'pageNumberParam'        => 'p',
    'frameworkScripts'       => true,
    'condenseLiterals'       => $_SERVER['APP_DEBUG'] != 'true',
    'compressOutput'         => $_SERVER['APP_DEBUG'] != 'true',
    'debugMode'              => $_SERVER['APP_DEBUG'] == 'true',
    'userModel'              => '',

    'imageRedirection'       => false,
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
