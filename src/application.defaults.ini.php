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

    // These paths are relative to the root folder:

    'storagePath'            => 'private/storage',
    'imageArchivePath'       => 'private/storage/images',
    'fileArchivePath'        => 'private/storage/files',
    'cachePath'              => 'private/storage/cache',
    'imagesCachePath'        => 'private/storage/cache/images',
    'modulesPath'            => 'private/modules',
    'defaultModulesPath'     => 'private/packages',
    'configPath'             => 'private/config',

    'langPath'               => 'private/resources/lang',
    'templatesPath'          => 'private/resources/templates',
    'viewPath'               => 'private/resources/views',

    // This path is relative to the kernel's folder:

    'scaffoldsPath'          => 'scaffolds',

    // These paths are relative to a module's folder or they are relative URIs:

    'moduleLangPath'         => 'resources/lang',
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
    'tasksClass'             => 'Tasks',

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
