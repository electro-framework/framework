<?php
// Preset application configuration

$debug = get ($_SERVER, 'APP_DEBUG') == 'true';

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

    // These paths are relative to the root folder and may be duplicated on the starter-app's config.

    'storagePath'            => 'private/storage',
    'imageArchivePath'       => 'private/storage/images',
    'fileArchivePath'        => 'private/storage/files',
    'cachePath'              => 'private/storage/cache',
    'imagesCachePath'        => 'private/storage/cache/images',
    'modulesPath'            => 'private/modules',
    'pluginModulesPath'      => 'private/plugins',
    'configPath'             => 'private/config',

    // These paths are relative to the root folder:

    'langPath'               => 'private/resources/lang',
    'templatesPath'          => 'private/resources/templates',
    'viewPath'               => 'private/resources/views',

    // These paths are relative to a module's folder or they are relative URIs:

    'moduleLangPath'         => 'resources/lang',
    'moduleViewsPath'        => 'resources/views',
    'moduleTemplatesPath'    => 'resources/templates',
    'modelPath'              => 'models',
    'modulePublicPath'       => 'public',
    'frameworkURI'           => 'framework',
    'addonsPath'             => 'framework/addons',

    'configFilename'         => 'application.ini.php',
    'routingMapFile'         => '',

    'autoControllerClass'    => 'Selene\Controller',

    'loginView'              => '',
    'translation'            => false,
    'languages'              => null,
    'defaultLang'            => null,
    'pageSize'               => 99999,
    'pageNumberParam'        => 'p',
    'frameworkScripts'       => true,
    'condenseLiterals'       => !$debug,
    'compressOutput'         => !$debug,
    'userModel'              => '',
    'originalImageMaxSize'   => 1024,
    'originalImageQuality'   => 95,

    'imageRedirection'       => false,
    'URINotFoundURL'         => false,

    'globalSessions'         => false,
    'autoSession'            => true,
    'requireLogin'           => false,

  ],
];
