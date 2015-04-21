<?php
class SiteMapSearchOptions {
    const INCLUDE_GROUPS = 1;
}

class AbstractSiteMap {
  public $groups;
  public $pages;
  /**
   * A list of references to each named site map node, indexed by name.
   * @var array
   */
  public $namedNodes;

  public function init() {
    //LEGACY
    if (isset($this->groups))
      foreach ($this->groups as $group)
        if (isset($group->pages))
          foreach ($group->pages as $page)
            $page->init($group);
    //NEW
    if (isset($this->pages))
      foreach ($this->pages as $page)
        $page->init($this);
  }

  /**
   * Returns the SitePage object that matches the given URI.
   * @return SitePage or array(SitePage,SiteSubpage)
   */
  public function searchFor($URI,$options = 0) {
    global $application;
    $URI = ltrim($URI,'/');
    preg_match("#\\?\\/?([\\s\\S]*?)(?:&|$)#",$URI,$m);
    if (count($m) == 2)
      $URI = $m[1];
    //LEGACY
    if (isset($this->groups))
      foreach ($this->groups as $group) {
        $result = $group->searchFor($URI,$options);
        if (isset($result))
          return $result;
      }
    else if (isset($this->pages))
      foreach ($this->pages as $page) {
        $result = $page->searchFor($URI,$options);
        if (isset($result))
          return $result;
      }
    return null;
  }

  public static function loadModule($moduleName,$configName = 'sitemap') {
    global $application;
    $path = "$application->modulesPath/$moduleName/config/$configName.php";
    if (!fileExists($path))
      $path = "$application->defaultModulesPath/$moduleName/config/$configName.php";
    $code = file_get_contents($path,FILE_USE_INCLUDE_PATH);
    if ($code === false)
      throw new ConfigException("SiteMap::loadConfigOf can't load <b>$configName.php</b> on module <b>$moduleName</b>.");
    $val = evalPHP($code);
    if ($val === false)
      throw new ConfigException("Error on <b>$moduleName</b>'s sitemap definiton. Please check the PHP code.");
    return $val;
  }
}

