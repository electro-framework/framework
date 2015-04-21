<?php
ob_start ();

/**
 * Displays debugging information when errors occur in dev.mode, or logs it when
 * in production mode.
 */
class ErrorHandler
{

  public static function init ()
  {
    set_error_handler (['ErrorHandler', 'globalErrorHandler']);
    set_exception_handler (['ErrorHandler', 'globalExceptionHandler']);
    register_shutdown_function (['ErrorHandler', 'onShutDown']);
  }

  private static function errorLink ($file, $line = 1, $col = 1)
  {
    global $application;
    if (empty($file))
      return '';
    $label = self::shortFileName ($file);
    if (!isset($application)) return $label;
    if (substr($file, 0, strlen($application->rootPath)) != $application->rootPath)
      $file = $application->rootPath . $file;
    $file = urlencode ($file);
    --$line;
    --$col;
    return "<a href='$application->baseURI/open-in-IDE.php?file=$file&line=$line&col=$col'>$label</a>";
  }

  public static function globalErrorHandler ($errno, $errstr, $errfile, $errline)
  {
    if (ini_get ('error_reporting') == 0)
      return false;
    self::globalExceptionHandler (new PHPError($errno, $errstr, $errfile, $errline));
  }

  public static function shortFileName ($fileName)
  {
    global $application;
    if (isset($application) && isset($application->rootPath)) {
      if (strpos ($fileName, $application->rootPath) === 0)
        return substr ($fileName, strlen ($application->rootPath));
    }
    return $fileName;
  }

  public static function onShutDown ()
  {
    if (class_exists ('Console', false))
      Console::outputContent ();
    //Catch fatal errors, which do not trigger globalErrorHandler()
    $error = error_get_last ();
    if (isset($error) && ($error['type'] == E_ERROR || $error['type'] == E_PARSE)) {
      //remove error output
      $buffer = @ob_get_clean ();
      $buffer = preg_replace ('#<table class=\'xdebug-error\'[\s\S]*?</table>#i', '', $buffer);
      echo $buffer;

      self::globalExceptionHandler (new PHPError(1, $error['message'], $error['file'], $error['line']));
    }
  }

  public static function globalExceptionHandler (Exception $exception)
  {
    global $application;
    ?>
    <table id="__error"
           style="position:fixed;z-index:9998;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.1)">
    <tr>
    <td valign="center" align="center">
    <div style='display:inline-block;position:relative;min-width:256px;max-width:600px;min-height:128px;text-align:left;border:1px solid #CCC;border-radius:5px;background:#F5F5F5;font-family:sans-serif;font-size:12px;box-shadow:2px 2px 10px rgba(0,0,0,0.1)'>
    <div style="background:#ECECEC;background-image:-moz-linear-gradient(90deg,#ECECEC,#F9F9F9);background-image:-webkit-linear-gradient(90deg,#ECECEC,#F9F9F9);border-bottom:1px solid #DFDFDF;border-radius:5px 5px 0 0;padding:5px;color:#888;text-shadow:#FFF 1px 1px;text-align:center;font-size:14px;position:relative">
      Selene Framework<span style="position:absolute;right:10px;cursor:pointer"
                            onclick="document.getElementById('__error').style.display='none'">&#xD7;</span></div>
    <div style="border-top:1px solid #FFF;padding-top:10px">
    <?php if (isset($application)) { ?>
    <img style="float:left;left:12px;margin:0 20px"
         src="../framework/assets/icon-error.png">
  <?php } ?>
    <div style="white-space:pre-wrap;padding:13px 20px 5px 72px;font-family:menlo,monospace"><?php
    $title = isset($exception->title) ? $exception->title : get_class ($exception);
    if ($title)
      echo "<div style='font-weight:bold;font-size:14px;color:#800;margin-bottom:20px'>$title</div>";
    echo ucfirst ($exception->getMessage ());
    if ($_SERVER['HTTP_HOST'] == 'localhost' || (isset($application) && $application->debugMode)) {
      //$file = self::shortFileName($exception->getFile());
      ?></div>
      <a style="display:block;color:#008;margin:10px 20px 10px 0;text-align:right;font-size:10px;text-decoration:none"
         href="javascript:void(document.getElementById('__trace').style.display='block')"
         onclick="this.style.display='none'"><u>Stack trace</u> <span style="font-size:16px">&blacktriangledown;</span></a>
      </div>
      <div id="__trace"
           style="display:none;margin-top:15px;border-top:1px solid #DFDFDF;font-family:menlo,monospace;font-size:12px">
        <div style="border-top:1px solid #FFF">
          <div style="margin:10px 10px 10px 20px;color:#555;overflow-y:auto;max-height:220px"><?php
      $link = self::errorLink ($exception->getFile (), $exception->getLine (), 1);
      if ($link)
        echo "Thrown from $link, line <b>{$exception->getLine()}</b><br/><br/>";
      $first = true;
      $trace = function_exists ('xdebug_get_function_stack')
        ? array_reverse (xdebug_get_function_stack ()) : ($exception instanceof PHPError ? debug_backtrace ()
          : $exception->getTrace ());
      foreach ($trace as $k => $v) {
        $fn    = isset($v['function']) ? $v['function'] : 'global scope';
        $class = isset($v['class']) ? $v['class'] : '';
        if ($class == 'ErrorHandler')
          continue;
        if (isset($v['function'])) {
          $args = [];
          if (isset($v['args'])) {
            foreach ($v['args'] as $arg) {
              switch (gettype ($arg)) {
                case 'boolean':
                  $arg = $arg ? 'true' : 'false';
                  break;
                case 'string':
                  $arg = "'$arg'";
                  break;
                case 'integer':
                case 'double':
                  break;
                default:
                  $arg = ucfirst (gettype ($arg));
              }
              $args[] = $arg;
            }
          }
          $args = implode (", ", $args);
          $args = "($args)";
        } else $args = '';
        $class .= $class !== '' ? '.' : '';
        $file = isset($v['file']) ? $v['file'] : '';
        $line = isset($v['line']) ? $v['line'] : '';
        $lineStr = $line ? ", line $line" : '';
        $link = $file ? self::errorLink ($file, $line, 1) : '&lt;unknown location&gt;';
        if ($first) {
          $first = false;
          echo 'Stack trace:<ol>';
        }
        echo <<<HTML
<li style="margin-bottom:5px;line-height:18px"><b>$class$fn $args</b>
HTML;
        if ($file == '')
          continue;
        echo <<<HTML
<div style="color:#999">At $link$lineStr</div></li>
HTML;
      }
      echo "</ol></div></div></div></td></tr></table>";
    }
    echo "</div></div>";
    if (function_exists ('database_rollback'))
      database_rollback ();
    if (class_exists ('BaseException', false) && $exception instanceof BaseException &&
        $exception->getStatus () != Status::FATAL
    )
      return;
    exit;
  }
}

class PHPError extends Exception
{
  public $title;

  public function __construct ($errno, $errstr, $errfile, $errline)
  {
    global $application;
    switch ($errno) {
      case E_ERROR:
        $type = "ERROR";
        break;
      case E_NOTICE:
        $type = 'NOTICE';
        break;
      case E_STRICT:
        $type = 'ADVICE';
        break;
      case E_WARNING:
        $type = 'WARNING';
        break;
      case E_DEPRECATED:
        $type = 'DEPRECATION WARNING';
        break;
      default:
        $type = "error type $errno";
    }
    $msg = "<b>$errstr</b>";
    if (isset($application) && $application->debugMode) {
      $errfile = ErrorHandler::shortFileName ($errfile);
      $msg .= "<p style='color:#999;font-family:monospace'>At $errfile, line $errline</p>";
    }
    $this->code    = $errno;
    $this->message = $errstr;
    $this->file    = $errfile;
    $this->line    = $errline;
    $this->title   = "PHP $type";
  }

}
