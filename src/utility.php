<?php
/*****************************************************************************************
 * A set of global utility functions.
 *****************************************************************************************/

//----------------------------------------------------------------------------------------
// Values
//----------------------------------------------------------------------------------------
use Selene\Util\Flow;

/**
 * Use this function to evaluate any expression in a string interpolator.
 *
 * Ex:
 * > global $_; // if code is inside a function
 * > `$x = "Your {$_(Static::call(1,$arg))} is ready";`
 *
 * @param mixed $v
 * @return mixed
 */
global $_;
$_ = function ($v) {
  return $v;
};

/**
 * Checks if the specified value is not null or an empty string.
 *
 * @param mixed $exp
 *
 * @return bool True if the value is empty.
 */
function exists ($exp)
{
  return isset($exp) && $exp !== '';
}

/**
 * Returns either $a or $default, whichever is not empty.
 * <br><br>
 * Returns $a if it is not empty (null or empty string), otherwise returns the $default value.
 *
 * @param mixed $a
 * @param mixed $default
 *
 * @return mixed
 */
function either ($a = null, $default = null)
{
  return isset($a) && $a !== '' ? $a : $default;
}

//----------------------------------------------------------------------------------------
// Conversions
//----------------------------------------------------------------------------------------

/**
 * Converts a boolean value to its string representation.
 *
 * @param bool $val
 *
 * @return string
 */
function boolToStr ($val)
{
  return (bool)$val ? 'true' : 'false';
}

/**
 * Converts a number or a textual description of a boolean value into a true boolean.
 *
 * @param string|int $val 'true', 'yes', 'on' and '1' evaluate to true. All other values evaluate to false.
 *
 * @return bool
 */
function strToBool ($val)
{
  return $val == 'true' || $val == '1' || $val == 'yes' || $val == 'on';
}

/**
 * Formats the given currency value into a string compatible with the pt_PT locale format.
 *
 * @param float $value
 *
 * @return string
 */
function formatMoney ($value)
{
  return number_format ($value, 2, ',', ' ');
}

/**
 * Converts a number in string format into a float value, taking into consideration the PT-PT locale.
 *
 * @param string|null $val
 *
 * @return float|null
 */
function toFloat ($val)
{
  if (is_null ($val) || $val === '')
    return null;
  $val = str_replace (str_replace (',', '.', $val), ' ', '');
  return floatval ($val);
}

//----------------------------------------------------------------------------------------
// Objects
//----------------------------------------------------------------------------------------

if (!function_exists ('property')) {
  /**
   * Reads a value from the given object at the specified key.
   * <br><br>
   * Unlike the usual object access operator ->, this function does not generate warnings when
   * the key is not present on the object; instead, it returns null or the specified default value.
   *
   * @param object        $obj The target object.
   * @param number|string $key The property name.
   * @param mixed         $def An optional default value.
   *
   * @return mixed
   */
  function property ($obj, $key, $def = null)
  {
    return isset ($obj->$key) ? $obj->$key : $def;
  }
}

/**
 * Converts a PHP object to an instance of the specified class.
 *
 * @param mixed  $instance
 * @param string $className Fully-qualified class name.
 *
 * @return mixed
 */
function object_toClass ($instance, $className)
{
  return unserialize (sprintf (
    'O:%d:"%s"%s',
    strlen ($className),
    $className,
    strstr (strstr (serialize ($instance), '"'), ':')
  ));
}

/**
 * Merges the values provided on the specified array into the given object.
 * Validates each field and discards it if it doesn't exist in the object.
 * <br><br>
 * Amongst other uses, this is useful for merging values provided by POST or PUT request into a model object.
 * <br><br>
 * Note: empty values are converted to NULL.
 *
 * Note: boolean values 'true' and 'false' are automatically typecast to boolean.
 * All other field types are not typecast.
 *
 * @param mixed $obj A model instance.
 * @param array $src The source data to be merged.
 *
 * @return mixed The input object.
 */
function object_mergeArray ($obj, array $src)
{
  foreach ($src as $k => $v)
    if (property_exists ($obj, $k)) {
      switch ($v) {
        case '':
          $v = null;
          break;
        case 'true':
          $v = true;
          break;
        case 'false':
          $v = false;
          break;
      }
      $obj->$k = $v;
    }
  return $obj;
}

/**
 * Copies properties from a source object (or array) into a given object.
 *
 * @param object       $target
 * @param object|array $src
 *
 * @throws Exception
 */
function extend ($target, $src)
{
  if (isset($src)) {
    if (is_object ($target)) {
      foreach ($src as $k => $v)
        $target->$k = $v;
    }
    else throw new InvalidArgumentException('Invalid target for ' . __FUNCTION__);
  }
}

/**
 * Copies non-empty properties from a source object (or array) into a given object.
 * Note: empty properties are those containing null or an empty string.
 *
 * @param object       $target
 * @param object|array $src
 *
 * @throws Exception
 */
function extendNonEmpty ($target, $src)
{
  if (isset($src)) {
    if (is_object ($target)) {
      foreach ($src as $k => $v)
        if (isset($v) && $v !== '')
          $target->$k = $v;
    }
    else throw new InvalidArgumentException('Invalid target for ' . __FUNCTION__);
  }
}

//----------------------------------------------------------------------------------------
// Arrays
//----------------------------------------------------------------------------------------

if (!function_exists ('get')) {
  /**
   * Reads a value from the given array at the specified index/key.
   * <br><br>
   * Unlike the usual array access operator [], this function does not generate warnings when
   * the key is not present on the array; instead, it returns null or a default value.
   *
   * @param array         $array The target array.
   * @param number|string $key   The list index or map key.
   * @param mixed         $def   An optional default value.
   *
   * @return mixed
   */
  function get (array $array = null, $key, $def = null)
  {
    if (!is_array ($array))
      return null;

    return isset ($array[$key]) ? $array[$key] : $def;
  }
}

/**
 * Checks if either the specified key is missing from the given array or it's corresponding value in the array is empty.
 *
 * @param array      $array An array reference.
 * @param string|int $key   An array key / offset.
 *
 * @return bool True if the key is missing or the corresponding value in the array is empty (null or empty string).
 * @see is_empty()
 */
function missing (array &$array, $key)
{
  return !array_key_exists ($key, $array) || is_null ($x = $array[$key]) || $x === '';
}

/**
 * Sorts an array by one or more field values.
 * Ex: array_orderBy ($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
 *
 * @return array
 */
function array_orderBy ()
{
  $args = func_get_args ();
  $data = array_shift ($args);
  foreach ($args as $n => $field) {
    if (is_string ($field)) {
      $tmp = [];
      foreach ($data as $key => $row)
        $tmp[$key] = $row[$field];
      $args[$n] = $tmp;
    }
  }
  $args[] = &$data;
  call_user_func_array ('array_multisort', $args);
  return array_pop ($args);
}

/**
 * Extracts from an array all elements where the specified field matches the given value.
 * Supports arrays of objects or arrays of arrays.
 *
 * @param array  $arr
 * @param string $fld
 * @param mixed  $val
 * @param bool   $strict TRUE to perform strict equality testing.
 *
 * @return array A list of matching elements.
 */
function array_findAll (array $arr, $fld, $val, $strict = false)
{
  $out = [];
  if (count ($arr)) {
    if (is_object ($arr[0])) {
      if ($strict) {
        foreach ($arr as $v)
          if ($v->$fld === $val)
            $out[] = $v;
      }
      else foreach ($arr as $v)
        if ($v->$fld == $val)
          $out[] = $v;
    }
    if (is_array ($arr[0])) {
      if ($strict) {
        foreach ($arr as $v)
          if ($v[$fld] === $val)
            $out[] = $v;
      }
      else foreach ($arr as $v)
        if ($v[$fld] == $val)
          $out[] = $v;
    }
  }
  return $out;
}

/**
 * Searches an array for the first element where the specified field matches the given value.
 * Supports arrays of objects or arrays of arrays.
 *
 * @param array  $arr
 * @param string $fld
 * @param mixed  $val
 * @param bool   $strict TRUE to perform strict equality testing.
 *
 * @return array(value,index) The index and value of the first matching element or
 * array (null, false) if none found.
 * <p>Use <code>list ($v,$i) = array_find()</code> to immediately split the return value into separate variables.
 */
function array_find (array $arr, $fld, $val, $strict = false)
{
  if (count ($arr)) {
    if (is_object ($arr[0])) {
      if ($strict) {
        foreach ($arr as $i => $v)
          if ($v->$fld === $val)
            return [$v, $i];
      }
      else foreach ($arr as $i => $v)
        if ($v->$fld == $val)
          return [$v, $i];
    }
    if (is_array ($arr[0])) {
      if ($strict) {
        foreach ($arr as $i => $v)
          if ($v[$fld] === $val)
            return [$v, $i];
      }
      else foreach ($arr as $i => $v)
        if ($v[$fld] == $val)
          return [$v, $i];
    }
  }
  return [null, false];
}

/**
 * Returns the values from a single column of the array, identified by the column key.
 * This is a simplified implementation of the native array_column function for PHP < 5.5 but it
 * additionally allows fetching properties from an array of objects.
 * Array elements can be objects or arrays.
 * The first element in the array is used to determine the element type for the whole array.
 *
 * @param array      $array
 * @param int|string $key Null value is not supported.
 *
 * @return array
 */
function array_getColumn (array $array, $key)
{
  return empty($array)
    ? []
    :
    (is_array ($array[0])
      ? array_map (function ($e) use ($key) {
        return $e[$key];
      }, $array)
      : array_map (function ($e) use ($key) {
        return $e->$key;
      }, $array)
    );
}

/**
 * Converts a PHP array map to an instance of the specified class.
 *
 * @param array  $array
 * @param string $className
 *
 * @return mixed
 */
function array_toClass (array $array, $className)
{
  return unserialize (sprintf (
    'O:%d:"%s"%s',
    strlen ($className),
    $className,
    strstr (serialize ($array), ':')
  ));
}

//----------------------------------------------------------------------------------------
// Functions
//----------------------------------------------------------------------------------------

/**
 * Transforms a callable reference into a closure, with optional pre-bound and/or post-bound arguments.
 *
 * The closure can be used to call the original reference via `$x()` syntax.
 *
 * @param callable $fn       A function reference, in the form of:
 *                           <ul>
 *                           <li> a Closure instance,
 *                           <li> a function name string,
 *                           <li> a "class::method" string, or
 *                           <li> an array of (className,methodName).
 *                           <li> an array of (classInstance,methodName).
 *                           </ul>
 * @param array    $append   If specified, these arguments will be appended to the target function's arguments on each
 *                           call.
 *                           <p>Note: `$append` precedes `$prepend` because this is the most common case.
 * @param array    $prepend  If specified, these arguments will be prepended to the target function's arguments on each
 *                           call.
 * @return Closure
 */
function fn (callable $fn, array $append = [], array $prepend = [])
{
  if (func_num_args () == 1)
    return function () use ($fn) {
      return call_user_func_array ($fn, func_get_args ());
    };
  return function () use ($fn, $prepend, $append) {
    return call_user_func_array ($fn, array_merge ($prepend, func_get_args (), $append));
  };
}

/**
 * Compiles and returns a lambda function defined by the given string expression.
 *
 * The expression is compiled only once, further calls to this function with the same argument will return a cached
 * instance.
 * @param string $exp An expression with the syntax: "$arg1,$arg2,... => php_expression".
 *                    <p>The string must be delimited with single quotes.
 *                    <p>Ex:
 *                    <code>  f ('$x => $x+1')</code>
 *                    <code>  f ('$a, callable $b => $a + $b()')</code>
 * @return Closure
 */
function f ($exp)
{
  static $cache = [];
  if (isset($cache[$exp]))
    return $cache[$exp];
  list ($a, $f) = explode ('=>', $exp, 2);

  return $cache[$exp] = create_function ($a, "return $f;");
}

/**
 * Returns a function that, when invoked, returns the given value.
 *
 * @param mixed $i
 *
 * @return callable
 */
function wrap ($i)
{
  return function () use ($i) {
    return $i;
  };
}

/**
 * Wraps the given function with a caching decorator.
 * The original function will be invoked only once, on the first call.
 * Subsequent calls return the cached value.
 *
 * @param callable $fn
 *
 * @return callable
 */
function cache ($fn)
{
  $v = null;
  return function () use ($fn, &$v) {
    return isset ($v) ? $v : $v = call_user_func ($fn);
  };
}

/**
 * Returns a function that returns the input argument unmodified.
 * @return Closure
 */
function identity () {
  return function ($a) { return $a; };
}

//----------------------------------------------------------------------------------------
// Strings
//----------------------------------------------------------------------------------------

/**
 * Truncates a string to a certain length and appends ellipsis to it.
 *
 * @param string $text
 * @param int    $limit
 * @param string $ending
 *
 * @return string
 */
function str_truncate ($text, $limit, $ending = '...')
{
  if (strlen ($text) > $limit) {
    $text = strip_tags ($text);
    $text = substr ($text, 0, $limit);
    $text = substr ($text, 0, -(strlen (strrchr ($text, ' '))));
    $text = $text . $ending;
  }
  return $text;
}

/**
 * Limits a string to a certain length by imploding the middle part of it.
 *
 * @param string $text
 * @param int    $limit
 * @param string $more Symbol that represents the removed part of the original string.
 *
 * @return string
 */
function str_cut ($text, $limit, $more = '...')
{
  if (strlen ($text) > $limit) {
    $chars = floor (($limit - strlen ($more)) / 2);
    $p     = strpos ($text, ' ', $chars) + 1;
    $d     = $p < 1 ? 0 : $p - $chars;
    return substr ($text, 0, $chars + $d) . $more . substr ($text, -$chars + $d);
  }
  return $text;
}

/**
 * Pads an unicode string to a certain length with another string.
 * Note: this provides the mb_str_pad that is missing from the mbstring module.
 *
 * @param string $str
 * @param int    $pad_len
 * @param string $pad_str
 * @param int    $dir
 * @param string $encoding
 *
 * @return null|string
 */
function mb_str_pad ($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = 'UTF-8')
{
  mb_internal_encoding ($encoding);
  $str_len     = mb_strlen ($str);
  $pad_str_len = mb_strlen ($pad_str);
  if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
    $str_len = 1; // @debug
  }
  if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
    return $str;
  }

  $result = null;
  if ($dir == STR_PAD_BOTH) {
    $length = ($pad_len - $str_len) / 2;
    $repeat = ceil ($length / $pad_str_len);
    $result = mb_substr (str_repeat ($pad_str, $repeat), 0, floor ($length))
              . $str
              . mb_substr (str_repeat ($pad_str, $repeat), 0, ceil ($length));
  }
  else {
    $repeat = ceil ($str_len - $pad_str_len + $pad_len);
    if ($dir == STR_PAD_RIGHT) {
      $result = $str . str_repeat ($pad_str, $repeat);
      $result = mb_substr ($result, 0, $pad_len);
    }
    else if ($dir == STR_PAD_LEFT) {
      $result = str_repeat ($pad_str, $repeat);
      $result =
        mb_substr ($result, 0, $pad_len - (($str_len - $pad_str_len) + $pad_str_len)) . $str;
    }
  }

  return $result;
}

/**
 * Encodes a string to be outputted to a javascript block as a delimited string.
 * Newlines and quotes that match the delimiters are escaped.
 *
 * @param string $str   The string to be encoded.
 * @param string $delim The delimiter used to enclose the javascript string (either " or ').
 *
 * @return string
 */
function str_encodeJavasciptStr ($str, $delim = '"')
{
  return $delim . str_replace ($delim, '\\' . $delim, str_replace ("\n", '\\n', $str)) . $delim;
}

/**
 * Converts an hyphenated compound word into a camel-cased form.
 *
 * Ex: `my-long-name => myLongName`
 * @param string $name
 * @return string
 */
function dehyphenate ($name)
{
  return str_replace (' ', '', ucwords (str_replace ('-', ' ', $name)));
}

//----------------------------------------------------------------------------------------
// Date/time
//----------------------------------------------------------------------------------------

/**
 * Human-friendly textual descriptions for some dates.
 * For use by humanizeDate().
 */
$HUMANIZE_DATE_STR = [
  'today'     => 'Hoje, às',
  'yesterday' => 'Ontem, às',
];
/**
 * For the specified date, if its today or yesterday, it replaces it with a textual description.
 *
 * @param string $date
 *
 * @return string
 */
function humanizeDate ($date)
{
  global $HUMANIZE_DATE_STR;
  $today     = Date ('Y-m-d');
  $yesterday = Date ('Y-m-d', strtotime ("-1 days"));
  return str_replace ($yesterday, $HUMANIZE_DATE_STR['yesterday'],
    str_replace ($today, $HUMANIZE_DATE_STR['today'], $date));
}

//----------------------------------------------------------------------------------------
// Operating System
//----------------------------------------------------------------------------------------

/**
 * Runs the specified external command with the specified input data and returns the resulting output.
 *
 * @param string $cmd       Command line to be executed by the shell.
 * @param string $input     Data for STDIN.
 * @param string $extraPath Additional search path folders to append to the shell's PATH.
 * @param array  $extraEnv  Additional environment variables to append to the shell's environment.
 *
 * @throws RuntimeException STDERR (or STDOUT if STDERR is empty) is available via getMessage().
 * Status code -1 = command not found; other status codes = status returned by command execution.
 * @return string Data from the command's STDOUT.
 */
function runExternalCommand ($cmd, $input = '', $extraPath = '', array $extraEnv = null)
{

  $descriptorSpec = [
    0 => ["pipe", "r"], // stdin is a pipe that the child will read from
    1 => ["pipe", "w"], // stdout is a pipe that the child will write to
    2 => ["pipe", "w"] // stderr is a pipe that the child will write to
  ];

  if ($extraPath) {
    $path = $extraPath . PATH_SEPARATOR . $_SERVER['PATH'];
    if (!isset($extraEnv))
      $extraEnv = [];
    $extraEnv['PATH'] = $path;
  }

  if (isset($extraEnv)) {
    $env = $_SERVER;
    unset($env['argv']);
    $env = array_merge ($env, $extraEnv);
  }
  else $env = null;

  $process = proc_open ($cmd, $descriptorSpec, $pipes, null, $env);

  if (is_resource ($process)) {
    fwrite ($pipes[0], $input);
    fclose ($pipes[0]);

    $output = stream_get_contents ($pipes[1]);
    fclose ($pipes[1]);

    $error = stream_get_contents ($pipes[2]);
    fclose ($pipes[2]);

    $return_value = proc_close ($process);
    if ($return_value)
      throw new RuntimeException ($error ?: $output, $return_value);

    return $output;
  }
  throw new RuntimeException ($cmd, -1);
}

/**
 * Removes a directory recursively.
 *
 * @param string $dir path.
 */
function rrmdir ($dir)
{
  if (is_dir ($dir)) {
    $objects = scandir ($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype ($dir . "/" . $object) == "dir")
          rrmdir ($dir . "/" . $object);
        else unlink ($dir . "/" . $object);
      }
    }
    reset ($objects);
    rmdir ($dir);
  }
}

define ('DIR_LIST_ALL', 0);
define ('DIR_LIST_FILES', 1);
define ('DIR_LIST_DIRECTORIES', 2);

/**
 * List files and/or directories inside the specified path.
 *
 * Note: the `.` and `..` directories are not returned.
 * @param string   $path
 * @param int      $type      One of the DIR_LIST_XXX constants.
 * @param bool     $fullPaths When `true` returns the full path name of each file, otherwise returns the file name only.
 * @param int|bool $sortOrder Either `false` (no sort), SORT_ASC or SORT_DESC.
 * @return false|string[] `false` if not a valid directory.
 */
function dirList ($path, $type = 0, $fullPaths = false, $sortOrder = false)
{
  try {
    $d = new DirectoryIterator($path);
  } catch (Exception $e) {
    return false;
  }
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

/**
 * A blend of map() and filter() for extracting information about the contents of a directory.
 * @param string   $path Directory path.
 * @param callable $fn   Callback invoked for each file/dir that receives a SplFileInfo object and should return the
 *                       desired information. If no value is returned (or `null` is returned) the value will no be added
 *                       to the result set.
 * @return Flow|false   `false` if `$path` is not a valid directory.
 */
function dirMap ($path, callable $fn)
{
  try {
    return Flow::from(new FilesystemIterator($path))->filterAndMap($fn);
  } catch (Exception $e) {
    return false;
  }
}

//----------------------------------------------------------------------------------------
// Iterators
//----------------------------------------------------------------------------------------
/*
function map ($src, callable $fn)
{
  if (is_array ($src)) {
    $o = [];
    foreach ($src as $k => $v)
      $o[] = $fn ($v, $k);
    return $o;
  }
  return Query::from($src)->map($fn)->all();
}

function filter ($src, callable $fn)
{
  if (is_array ($src)) {
    $o = [];
    foreach ($src as $k => $v)
      if ($fn ($v, $k))
        $o[$k] = $v;
    return $o;
  }
  return Query::from($src)->map($fn)->all();
}
*/

//----------------------------------------------------------------------------------------
// PHP Code Execution
//----------------------------------------------------------------------------------------

function startProfiling ()
{
  global $profStart;
  $profStart = microtime (true);
}

function endProfiling ()
{
  global $profStart;
  $profEnd = microtime (true);
  $diff    = round (($profEnd - $profStart) * 1000, 2) - 0.01;
  echo "Elapsed: $diff miliseconds.";
  exit;
}

/**
 * Run the provided code intercepting PHP errors.
 *
 * If error-handling code is not supplied, an ErrorException will be thrown in the caller's context.
 *
 * @param callable $wrappedCode  Code to be executed wrapped by error catching code.
 * @param callable $errorHandler Optional error-handling code.
 * @param bool     $reset        True if the error status should be cleared so that Laravel does not intercept the
 *                               previous error.
 * @return mixed   The return value from the callable argument.
 *
 * @throws ErrorException
 * @throws Exception
 */
function catchErrorsOn ($wrappedCode, $errorHandler = null, $reset = true)
{
  $prevHandler = set_error_handler (function ($errno, $errstr, $errfile, $errline) {
    if (!error_reporting ())
      return false;
    throw new ErrorException ($errstr, $errno, 0, $errfile, $errline);
  });

  try {
    // Run the caller-supplied code.
    $r = $wrappedCode();
    // Restore the previous error handler.
    set_error_handler ($prevHandler);

    return $r;
  } catch (Exception $e) {
    // Intercept the error that will be triggered below.
    set_error_handler (function () {
      // Force error_get_last() to be set.
      return false;
    });
    // Clear the current error message so that the framework will not intercept the previous error.
    if ($reset)
      trigger_error ("");
    // Restore the previous error handler.
    set_error_handler ($prevHandler);

    // Handle the error.
    if (isset($errorHandler))
      return $errorHandler($e);

    throw $e;
  }
}

