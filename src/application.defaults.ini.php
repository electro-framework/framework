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

    'storagePath'            => 'private/storage',
    'imageArchivePath'       => 'private/storage/images',
    'fileArchivePath'        => 'private/storage/files',
    'cachePath'              => 'private/storage/cache',
    'imagesCachePath'        => 'private/storage/cache/images',

    'configPath'             => 'private/config',
    'langPath'               => 'private/resources/lang',
    'moduleLangPath'         => 'resources/lang',
    'templatesPath'          => 'private/resources/templates',
    'modulesPath'            => 'private/modules',
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
