<?php
use Selenia\Http\Components\PageComponent;
use Selenia\Routing\Lib\FactoryRoutable;

/**
 * Generates a routable that, when invoked, will return a generic PageComponent with the specified template as a view.
 *
 * <p>Use this to define routes for simple pages that have no controller logic.
 *
 * @param string $templateUrl
 * @return FactoryRoutable
 */
function page ($templateUrl)
{
  return new FactoryRoutable (function (PageComponent $page) use ($templateUrl) {
    $page->templateUrl = $templateUrl;
    return $page;
  });
}

/**
 * A shortcut to create a {@see FactoryRoutable}.
 *
 * @param callable $fn
 * @return FactoryRoutable
 */
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

/**
 * Outputs a formatted representation of the given arguments to the browser, clearing any existing output.
 * <p>This is useful for debugging.
 */
function dump ()
{
  echo "<pre>";
  ob_start ();
  call_user_func_array ('var_dump', func_get_args ());
  // Applies formatting if XDEBUG is not installed
  echo preg_replace_callback ('/^(\s*)\["?(.*?)"?\]=>\n\s*/m', function ($m) {
    list (, $space, $prop) = $m;
    return $space . str_pad ("$prop:", 30, ' ');
  }, ob_get_clean ());
}

function trace ()
{
  PhpKit\WebConsole\DebugConsole\DebugConsole::trace ();
}
