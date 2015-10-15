<?php
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\WebConsole;
use Selenia\ForeignKey;
use Selenia\ModuleOptions;
use Selenia\Routing\DataSourceInfo;
use Selenia\Routing\PageRoute;
use Selenia\Routing\Route;
use Selenia\Routing\RouteGroup;
use Selenia\Routing\SubPageRoute;

function ModuleOptions ($path, array $options = null, callable $initializer = null)
{
  return new ModuleOptions($path, $options, $initializer);
}

function RouteGroup ($init)
{
  return new RouteGroup($init);
}

function PageRoute (array $init)
{
  return new PageRoute($init);
}

function SubPageRoute (array $init)
{
  return new SubPageRoute($init);
}

function Route (array $init)
{
  return new Route($init);
}

function ForeignKey (array $init)
{
  return new ForeignKey($init);
}

function DataSourceInfo (array $init)
{
  return new DataSourceInfo($init);
}

//------------------------------------------------------------------------------------------------------------------------

/**
 * @param string|array $ref Either a 'Class::method' string or a ['Class', 'Method'] array.
 * @return array A ['Class', 'Method'] array.
 */
function parseMethodRef ($ref)
{
  if (empty($ref))
    return [null, null];
  return array_merge (is_array ($ref) ? $ref : explode ('::', $ref), [null]);
}

function param ($name)
{
  return isset($_REQUEST[$name]) && $_REQUEST[$name] != '' ? $_REQUEST[$name] : null;
}

function get_param ($name, $default = null)
{
  return isset($_GET[$name]) && $_GET[$name] != '' ? $_GET[$name] : $default;
}

function post_param ($name, $default = null)
{
  return isset($_POST[$name]) && $_POST[$name] != '' ? $_POST[$name] : $default;
}

function safeParameter ($name)
{
  $v = get ($_REQUEST, $name);
  if (!empty($v))
    $v = addslashes ($v);
  return $v;
}

/**
 * @return ConsolePanel
 */
function _log ()
{
  $args = array_merge (['<#log>'], func_get_args ());
  return call_user_func_array ([WebConsole::$class, 'log'], $args)->showCallLocation ()->log ('</#log>');
}

