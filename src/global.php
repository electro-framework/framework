<?php
use Impactwave\WebConsole\ConsolePanel;
use Impactwave\WebConsole\WebConsole;
use Selene\Exceptions\FatalException;
use Selene\ForeignKey;
use Selene\ModuleOptions;
use Selene\Routing\DataSourceInfo;
use Selene\Routing\PageRoute;
use Selene\Routing\Route;
use Selene\Routing\RouteGroup;
use Selene\Routing\SubPageRoute;

function ModuleOptions ($path, array $options)
{
  return new ModuleOptions($path, $options);
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

function check_syntax ($code, &$output = 0)
{
  $b = 0;
  foreach (token_get_all ($code) as $token)
    if ('{' == $token)
      ++$b;
    elseif ('}' == $token)
      --$b;
  if ($b)
    return false; // Unbalanced braces would break the eval below
  ob_start (); // Catch potential parse error messages
  $code = eval('if(0){' . $code . '}'); // Put $code in a dead code sandbox to prevent its execution
  if ($output != 0)
    $output = ob_get_clean ();
  else
    ob_end_clean ();
  return false !== $code;
}

function firstNonNull ($a = null, $b = null, $c = null, $d = null)
{
  if (isset($a)) return $a;
  if (isset($b)) return $b;
  if (isset($c)) return $c;
  if (isset($d)) return $d;
  return null;
}

function ifset ($exp, $a, $b = null)
{
  if (isset($exp) && $exp !== '')
    return $a;
  return $b;
}

function iftrue ($exp, $a, $b = null)
{
  return $exp ? $a : $b;
}

function when ($exp, $a, $b = null)
{
  return $exp ? $a : $b;
}

function enum ($delimiter)
{
  $r = [];
  $t = func_num_args ();
  for ($n = 1; $n < $t; ++$n) {
    $v = func_get_arg ($n);
    if (!empty($v))
      $r[] = $v;
  }
  return join ($delimiter, $r);
}

/**
 * Concatenates array
 * @param array $a
 * @param array $b
 */
function concat (array &$a, array $b)
{
  $a = array_merge ($a, $b);
}

function convertToInt (&$variable)
{
  $variable = intval ($variable);
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

function dirnameEx ($path)
{
  $p = strrpos ($path, '/');
  $b = substr ($path, 0, $p);
  if (empty($b))
    return '';
  return $b . '/';
}

function trimText ($text, $maxSize)
{
  if (strlen ($text) <= $maxSize)
    return $text;
  $a = explode (' ', substr ($text, 0, $maxSize));
  array_pop ($a);
  return join (' ', $a) . ' (...)';
}

function trimHTMLText ($text, $maxSize)
{
  if (strlen ($text) <= $maxSize)
    return $text;
  $text = substr ($text, 0, $maxSize);
  $a    = strrpos ($text, '>');
  $b    = strrpos ($text, '<');
  if ($b !== false && ($a === false || $a < $b))
    $text = substr ($text, 0, $b);
  $a = explode (' ', $text);
  array_pop ($a);
  $text = join (' ', $a) . ' (...)';
  $c    = 0;
  $tags = [];
  if (preg_match_all ('#<.*?>#', $text, $matches)) {
    foreach ($matches[0] as $match)
      if (substr ($match, 1, 1) == '/')
        array_pop ($tags);
      else if (substr ($match, -2, 1) != '/')
        array_push ($tags, trim (substr ($match, 1, strlen ($match) - 2)));
    $tags = array_reverse ($tags);
    foreach ($tags as $tag) {
      $a = strpos ($tag, ' ');
      if ($a)
        $tag = substr ($tag, 0, $a);
      $text .= "</$tag>";
    }
  }
  return $text;
}

function extractP ($html)
{
  return preg_match ('#<p>([\s\S]*?)</p>#', $html, $matches) ? $matches[1] : '';
}

/*
  function propertiesToURI(array $props,$trimLeft = false,$glue = '&amp;') {
  $uri = '';
  foreach ($props as $k=>$v) {
  $v = urlencode($v);
  $uri .= "$glue$k=$v";
  }
  return $trimLeft ? substr($uri,strlen($glue)) :  $uri;
  }
 */

function fileExists ($filename)
{
  $r = @fopen ($filename, 'rb', true);
  if ($r === false)
    return false;
  fclose ($r);
  return true;
}

function loadFile ($filename, $useIncludePath = true)
{
  $data = @file_get_contents ($filename, $useIncludePath);
  if ($data)
    return removeBOM ($data);
  return '';
}

function removeBOM ($string)
{
  if (substr ($string, 0, 3) == pack ('CCC', 0xef, 0xbb, 0xbf))
    $string = substr ($string, 3);
  return $string;
}

if (get_magic_quotes_gpc () == 1) {
  $_GET     = array_map ('stripslashes', $_GET);
  $_POST    = array_map ('stripslashes', $_POST);
  $_REQUEST = array_map ('stripslashes', $_REQUEST);
}

function evalPHP ($code)
{
  $code = trim ($code, " \n\r\t");
  if (substr ($code, -2) != '?>')
    $code .= '?>';
  if (substr ($code, 0, 5) == '<?php')
    return eval(substr ($code, 5, strlen ($code) - 7));
  return eval($code);
}

function toURISafeText ($text)
{
  //$text = iconv('UTF-8','ISO-8859-1//TRANSLIT',$text);
  //$text = strtr($text,'áéíóúàèìòùãõâêôçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÔÇ','aeiouaeiouaoaeocAEIOUAEIOUAOAEOC');
  return urlencode ($text);
}

/**
 * Creates a temporary directory.
 *
 * @param        $dir
 * @param string $prefix
 * @param int    $mode
 *
 * @return string
 */
function tempdir ($dir, $prefix = '', $mode = 0700)
{
  if (substr ($dir, -1) != '/') $dir .= '/';
  do {
    $path = $dir . $prefix . mt_rand (0, 9999999);
  } while (!mkdir ($path, $mode));

  return $path;
}

/**
 * Call startProfiling() (native function) before the code being measured.
 * Call this function after the code block to display the ellapsed time.
 */
function stopProfiling ()
{
  ob_clean ();
  endProfiling ();
  exit;
}

/**
 * @return ConsolePanel
 */
function _log () {
  $args = array_merge (['<#log>'], func_get_args());
  return call_user_func_array ([WebConsole::$class, 'log'], $args)->showCallLocation ()->log('</#log>');
}