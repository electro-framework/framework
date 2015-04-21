<?php
/*
 * Provides the display of debugging information on a panel on the bottom
 * of the browser window.
 */
class Console {
  private static $consoleText = '';

  public static function write($msg) {
    self::$consoleText.= $msg;
  }

  public static function outputContent() {
    global $application;
    if (isset($application) && $application->debugMode)
      self::render();
    else error_log(self::$consoleText);
  }

  private static function render() {
    $consoleText = self::$consoleText;
    if ($consoleText != '') {
      @header('Content-type: text/html; charset=UTF-8');
      echo <<<HTML
<div style="position:fixed;z-index:9999;bottom:0;left:0;right:0;font-size:12px">
<div style="margin:-22px 20px 0 0;float:right;background:#D8D8D8;color:#666;font-weight:bold;text-shadow:#FFF 1px 1px;padding:5px 10px;border-radius:5px 5px 0 0;font-family:Arial,sans-serif;cursor:pointer" onclick="document.getElementById('__console').style.height=document.getElementById('__console').style.height=='auto'?0:'auto'">PHP Console</div>
<div id="__console" style="clear:both;height:0;overflow-y:auto;max-height:500px;border-top:2px solid #D8D8D8;background:#F0F0F0;font-family:monospace"><div style="padding:5px 10px">$consoleText</div></div>
</div>
HTML;
    }
  }

  /**
  * Logs detailed information about the specified values or variables to the PHP console.
  * Params: list of one or more values to be displayed.
  * @return void
  */
  public static function debug() {
    global $application;
    foreach (func_get_args() as $val) {
      ob_start();
      var_dump($val);
      $text = ob_get_clean();
      $text = str_replace("=>\n","=>\t",$text); //std vardump
      $text = trim(preg_replace('#^<pre class=\'xdebug-var-dump\'[^>]*>([\s\S]*)</pre>$#','$1',$text)); //x-debug powered vardump
      $stack = debug_backtrace(1);
      $trace = $stack[0];
      $path = isset($trace['file']) ? $trace['file'] : '';
      if (isset($application) && isset($application->baseDirectory))
        $path = substr($path,strlen($application->baseDirectory) + 1);
      $line = isset($trace['line']) ? "(<b>{$trace['line']}</b>)" : '';
      if ($path != '')
        $path = <<<HTML
<div style="color:#999;padding:0 0 10px 5px;float:right"><b>At</b> $path$line</div>
HTML;
      $text = <<<HTML
<div style="white-space:pre;margin:5px 0;padding:10px;border:1px solid #E4E4E4;background:#FFF;overflow-x:auto">$path$text</div>
HTML;
      self::write($text);
    }
  }
  /**
  * Logs detailed information about the specified values or variables to the PHP console.
  * Params: list of one or more values to be displayed.
  * @global string $consoleText
  * @param string $title The logging section title.
  * @return void
  */
  public static function debugSection($title) {
    self::write("<div style='background:#E8E8E8;border:1px solid #DDD;margin-bottom:-6px;padding:5px 10px;color:#666;font-size:14px;font-weight:bold;text-shadow:#FFF 1px 1px'>$title</div>");
    $args = func_get_args();
    array_shift($args);
    call_user_func_array('Console::debug',$args);
  }
}
