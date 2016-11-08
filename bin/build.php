#!/usr/bin/env php
<?php
/*
 * This script rebuilds this package's composer.json file by merging relevant sections from the bundled subsystems.
 * You should run it whenever you modify one of the subsystem's composer.json and the commit the changes to Git.
 */

define ('DIR_LIST_DIRECTORIES', 2);
chdir (dirname (__DIR__));

$targetConfig = json_load ('composer.template', true);

$requires = $psr4s = $bins = $files = [];

$packages = dirList (getcwd () . '/subsystems', DIR_LIST_DIRECTORIES);
foreach ($packages as $package) {
  $config = json_load ("subsystems/$package/composer.json", true);

  // Merge 'require' section

  if (isset($config['require'])) {
    $require = $config['require'];
    foreach ($require as $name => $version)
      if (substr ($name, 0, 11) != 'subsystems/') { // exclude subsystems
        if (!isset ($requires[$name]))
          $requires[$name] = $version;
        else if ($requires[$name] != $version)
          fail ("subsystem/$package's version of package '$name' conflicts with another subsystem's version of the same package
");
      }
  }

  if (isset($config['autoload'])) {
    $autoload = $config['autoload'];

    // Merge 'autoload.psr-4' section

    if (isset($autoload['psr-4']))
      foreach ($autoload['psr-4'] as $namespace => $dir)
        $psr4s[$namespace] = "subsystems/$package/$dir";

    // Merge 'files' section

    if (isset($autoload['files']))
      foreach ($autoload['files'] as $file)
        $files[] = "subsystems/$package/$file";
  }

  // Merge 'bin' section

  if (isset($config['bin']))
    foreach ($config['bin'] as $file)
      $bins[] = "subsystems/$package/$file";
}

ksort ($requires);
ksort ($psr4s);
ksort ($bins);
// do not sort files.

$targetConfig['require']           = array_merge ($targetConfig['require'], $requires);
$targetConfig['autoload']['psr-4'] = array_merge ($targetConfig['autoload']['psr-4'], $psr4s);
$targetConfig['autoload']['files'] = array_merge ($targetConfig['autoload']['files'], $files);
$targetConfig['bin']               = array_merge ($targetConfig['bin'], $bins);

json_save ('composer.json', $targetConfig);
echo "composer.json has been updated
";
exit;

function fail ($msg)
{
  echo "$msg
";
  exit (1);
}

function dirList ($path, $type = 0, $fullPaths = false, $sortOrder = false)
{
  if (!file_exists ($path))
    return false;
  $d = new DirectoryIterator($path);
  $o = [];
  foreach ($d as $file) {
    /** @var DirectoryIterator $file */
    if ($file->isDot ()) continue;
    if ($type == 1 && !$file->isFile ())
      continue;
    if ($type == 2 && !$file->isDir ())
      continue;
    $o[] = $fullPaths ? $file->getPathname () : $file->getFilename ();
  }
  if ($sortOrder)
    sort ($o, $sortOrder);
  return $o;
}

function json_save ($path, $data, $pretty = true)
{
  $json = json_encode ($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0));
  $json = preg_replace_callback ('/^ +/m', function ($m) {
    return str_repeat (' ', strlen ($m[0]) / 2);
  }, $json);
  file_put_contents ($path, $json);
}

function json_load ($path, $assoc = false)
{
  $src = @file_get_contents ($path);
  if (!$src)
    fail ("File $path not found");
  $v = json_decode ($src, $assoc);
  if (is_null ($v))
    fail ("File $path is not valid JSON");
  return $v;
}
