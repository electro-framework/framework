<?php
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\WebConsole;
use Selenia\Routing\FactoryRoutable;


function factory (callable $fn)
{
  return new FactoryRoutable ($fn);
}

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

function trace ()
{
  echo "<pre>";
  ob_start ();
  call_user_func_array ('var_dump', func_get_args ());
  echo preg_replace_callback ('/^(\s*)\["?"(.*?)"?\]=>\n\s*/m', function ($m) {
    list (, $space, $prop) = $m;
    return $space . str_pad ("$prop:", 30, ' ');
  }, ob_get_clean ());
}
