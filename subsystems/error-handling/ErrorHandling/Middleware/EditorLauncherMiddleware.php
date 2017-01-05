<?php
namespace Electro\ErrorHandling\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles a special HTTP request that opens a file for editing on an IDE / texteditor.
 * <p>Requests of this type are usually triggered by the developer clicking on an error location link on the error
 * console.
 *
 * ><p>For security reasons, this is only enabled on the **development environment**.
 */
class EditorLauncherMiddleware implements RequestHandlerInterface
{
  /** @var KernelSettings */
  private $kernelSettings;

  public function __construct (KernelSettings $kernelSettings)
  {
    $this->kernelSettings = $kernelSettings;
  }

  /**
   * @param string $dir The directory to retrieve subdirectories from.
   * @return false|string[]
   */
  static public function getSubdirsOf ($dir)
  {
    return dirList ($dir, DIR_LIST_DIRECTORIES, true);
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if ($request->getAttribute ('virtualUri') != $this->kernelSettings->editorUrl)
      return $next();

    $param        = $request->getQueryParams ();
    $file         = $this->mapPath ($param['file']);
    $line         = $param['line'];
    $feedbackElem = "parent.document.getElementById ('__feedback')";

    $res = $response->getBody ();

    $res->write ("<script>$feedbackElem.style.display = 'none'</script>");

    exec ("/Applications/PhpStorm.app/Contents/MacOS/phpstorm --line $line $file", $out, $status);

//    if ($status)
//      exec ("/usr/local/bin/phpstorm --line $line $file", $out, $status);

    if ($status) {
      $res->write ("
      <script>
        $feedbackElem.innerHTML =
          '<h4>Unable to open the file for editing on PHPStorm</h4><p><b>Hint:</b> this feature is only available on MacOS and PHPStorm must be installed on the default Applications folder.';
      </script>");
      $code = 1;
      goto out;
    }

// Try to activate the IDE application window (MacOS only).
// Note: Apache must be running with the same user as the logged in user for this to work.
// Edit httpd.conf to set the user and group Apache runs on.

    $cmd = "osascript -e 'tell application \"%s\" to activate'";
// Try the following names, in order.
    $names = ['PHPStorm EAP', 'PHPStorm', 'IDEA'];
// If the IDE name is configured via environment variable, try that name first.
    if (isset($_ENV['IDEA']))
      array_unshift ($names, $_ENV['IDEA']);

    foreach ($names as $app) {
      system (sprintf ($cmd, $app), $code);
      if (!$code) break;
    }

// If it was not possible to activate, warn the user.
    out:
    if ($code)
      $res->write ("<script>$feedbackElem.style.display = 'block'</script>");

    return $response;
  }

  public function getSymlinkedPaths ()
  {
    $base = $this->kernelSettings->baseDirectory;
    return array_prune (map (
      array_merge (
        $this->getSubdirsOfDirs ($this->getSubdirsOf ("$base/{$this->kernelSettings->packagesPath}")),
        $this->getSubdirsOfDirs ($this->getSubdirsOf ("$base/{$this->kernelSettings->pluginModulesPath}"))
      ),
      function ($dir, &$k) {
        $r = realpath ($dir);
        if ($r == $dir)
          return null;
        $k = $r;
        return $dir;
      }
    ));
  }

  /**
   * @param string $path
   * @return string
   */
  public function mapPath ($path)
  {
    foreach ($this->getSymlinkedPaths () as $real => $symlinked)
      if (str_beginsWith ($path, $real))
        return $symlinked . substr ($path, strlen ($real));
    return $path;
  }

  /**
   * @param string[] $dir The directories to retrieve subdirectories from.
   * @return false|string[]
   */
  private function getSubdirsOfDirs (array $dir)
  {
    return array_flatten (map ($dir, [__CLASS__, 'getSubdirsOf']));
  }

}
