<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\Component;
use Selene\Matisse\Context;
use Selene\Matisse\Exceptions\MatisseException;

class Page extends Component
{
  public $allowsChildren = true;

  /**
   * Array of strings/Parameters containing URLs of CSS stylesheets to be loaded during the page loading process.
   * @var array
   */
  public $stylesheets = [];

  /**
   * Array of strings/Parameters containing URLs of scripts to be loaded during the page loading process.
   * @var array
   */
  public $scripts = [];

  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   * @var array
   */
  public $inlineScripts = [];

  /**
   * Array of strings (or Parameter objects with child content) containing inline css code.
   * @var array
   */
  public $inlineCssStyles = [];

  public $statusMessage    = '';
  public $autoHTML         = true;
  public $doctype          = '<!DOCTYPE HTML>';
  public $charset          = 'UTF-8';
  public $title;
  public $browserIsIE      = false;
  public $browserIsIE6     = false;
  public $browserIsIE7     = false;
  public $browserIsIE8     = false;
  public $browserIsIE9     = false;
  public $browserIsFF      = false;
  public $browserIsSafari  = false;
  public $browserIsChrome  = false;
  public $clientIsWindows  = false;
  public $requestURI;
  public $enableFileUpload = false;
  public $formAutocomplete = false;
  public $description      = '';
  public $keywords         = '';
  public $author           = '';
  public $footer           = '';
  public $extraHeadTags    = '';
  public $targetURL;
  public $defaultAction;
  /** Content to be inserted before the form element. */
  public $preForm = '';
  /**
   * Map of attributes to set on the body tag.
   * @var Array Map of string => mixed
   */
  public $bodyAttrs = null;

  public function __construct (Context $context)
  {
    parent::__construct ($context);
    $this->page = $this;
    $this->setTagName('Page');
    $this->checkBrowser ();
    $this->requestURI = $_SERVER['REQUEST_URI'];
  }

  public function checkBrowser ()
  {
    $b = get ($_SERVER, 'HTTP_USER_AGENT', '');
    if (preg_match ('#MSIE (\d+)#', $b, $match)) {
      $v                  = intval ($match[1]);
      $this->browserIsIE6 = $v == 6;
      $this->browserIsIE7 = $v == 7;
      $this->browserIsIE8 = $v == 8;
      $this->browserIsIE9 = $v >= 9;
      $this->browserIsIE  = true;
    }
    $this->browserIsFF     = strpos ($b, 'Gecko/') !== false;
    $this->browserIsSafari = strpos ($b, 'Safari') !== false && strpos ($b, 'Chrome') === false;
    $this->browserIsChrome = strpos ($b, 'Chrome') !== false;
    $this->clientIsWindows = strpos ($b, 'Windows') !== false;
  }

  public function error ($msg)
  {
    $this->statusMessage =
      '<div class="alert alert-danger" role="alert">' . $msg . '</div>';
  }

  public function warning ($msg)
  {
    $this->statusMessage =
      '<div class="alert alert-warning" role="alert">' . $msg . '</div>';
  }

  public function info ($msg)
  {
    $this->statusMessage =
      '<div class="alert alert-info" role="alert">' . $msg . '</div>';
  }

  public function fatal ($msg)
  {
    @ob_clean ();
    echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></head><body><pre>' . $msg .
         '</pre></body></html>';
    exit;
  }

  public function addStylesheet ($uri)
  {
    if (array_search ($uri, $this->stylesheets) === false)
      $this->stylesheets[] = $uri;
  }

  public function addScript ($uri)
  {
    if (array_search ($uri, $this->scripts) === false)
      $this->scripts[] = $uri;
  }

  /**
   * Adds an inline script to the HEAD section of the page.
   * @param mixed  $code Either a string or a Parameter.
   * @param string $name An identifier for the script, to prevent duplication.
   *                     When multiple scripts with the same name are added, only the last one is considered.
   */
  public function addInlineScript ($code, $name = null)
  {
    if ($code instanceof Component)
      $code->attachTo ($this);
    if (isset($name))
      $this->inlineScripts[$name] = $code;
    else $this->inlineScripts[] = $code;
  }

  /**
   * Similar to addInlineScript(), but the script will only run on the document.ready event.
   * @param mixed  $code Either a string or a Parameter.
   * @param string $name An identifier for the script, to prevent duplication.
   *                     When multiple scripts with the same name are added, only the last one is considered.
   * @see addInlineScript
   */
  public function addInlineDeferredScript ($code, $name = null)
  {
    $code = "$(function(){\n$code\n});";
    $this->addInlineScript ($code, $name);
  }

  /**
   * Adds an inline stylesheet to the HEAD section of the page.
   * @param mixed  $css  Either a string or a Parameter.
   * @param string $name An identifier for the stylesheet, to prevent duplication.
   *                     When multiple stylesheets with the same name are added, only the last one is considered.
   */
  public function addInlineCss ($css, $name = null)
  {
    if ($css instanceof Component)
      $css->attachTo ($this);
    if (isset($name))
      $this->inlineCssStyles[$name] = $css;
    else
      $this->inlineCssStyles[] = $css;
  }

  protected function render ()
  {
    global $application;
    if ($this->autoHTML) {

      ob_start ();
      $this->renderChildren ();
      $pageContent = ob_get_clean ();

      $cacheScriptURI = $application->toURI ('private/selene/cache.php?f=');

      $oldIEWarning = !empty($application->oldIEWarning) && $this->browserIsIE6;
      if ($oldIEWarning)
        $this->stylesheets[] = $application->toThemeURI ('oldIEWarning.css', $application->frameworkTheme);

      echo $this->doctype;
      $this->beginTag ("html");
      $this->addAttribute ('class',
        enum (' ',
          iftrue ($this->browserIsIE, 'IE'),
          iftrue ($this->browserIsIE6, 'IE6'),
          iftrue ($this->browserIsIE7, 'IE7'),
          iftrue ($this->browserIsIE8, 'IE8'),
          iftrue ($this->browserIsIE9, 'IE9'),
          iftrue ($this->browserIsFF, 'FF'),
          iftrue ($this->browserIsSafari, 'SAFARI'),
          iftrue ($this->browserIsChrome, 'CHROME'),
          iftrue ($this->clientIsWindows, 'WIN')
        )
      );
      $this->beginTag ('head');
      $this->addTag ('meta', [
        'charset' => $this->charset
      ]);
      /*$this->addTag('meta',array(
          'http-equiv' => 'X-UA-Compatible',
          'content'    => 'IE=EmulateIE7'
      ));*/
      $this->addTag ('title', null, $this->title);
      $this->addTag ('base', ['href' => "$application->URI/"]);
      /*
                          foreach ($application->CSS_sheets as $URI) {
                            //if ($application->resourceCaching)
                              //$URI = $cacheScriptURI.$URI;
                            $this->addTag('link',array(
                                'rel'   => 'stylesheet',
                                'type'  => 'text/css',
                                'href'  => $URI
                            ));
                          }
      */
      foreach ($this->stylesheets as $URI) {
        if (substr ($URI, 0, 4) != 'http') {
          if (substr ($URI, 0, 1) != '/')
            $URI = $application->toURI ($URI);
          //if ($application->resourceCaching)
          //$URI = $cacheScriptURI.$URI;
        }
        $this->addTag ('link', [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => $URI
        ]);
      }
      if (!empty($this->inlineCssStyles)) {
        $css = '';
        foreach ($this->inlineCssStyles as $item)
          if ($item instanceof Parameter)
            $css .= $item->getContent ();
          else $css .= $item;
        $this->addTag ('style', null, $css);
      }

      if ($application->packScripts)
        $this->packScripts ();
      else foreach ($this->page->scripts as $URI) {
        if (substr ($URI, 0, 4) != 'http') {
          if (substr ($URI, 0, 1) != '/')
            $URI = $application->toURI ($URI);
          if ($application->resourceCaching)
            $URI = $cacheScriptURI . $URI;
        }
        $this->addTag ('script', [
          'type' => 'text/javascript',
          'src'  => $URI
        ]);
      }
      if (!empty($this->inlineScripts)) {
        $code = '';
        foreach ($this->inlineScripts as $item)
          if ($item instanceof Parameter) {
            $code .= $item->getContent ();
          }
          else $code .= $item;
        $this->addTag ('script',
          [
            'type' => 'text/javascript'
          ],
          $code
        );
      }
      if (!empty($this->description))
        $this->addTag ('meta', [
          'name'    => 'description',
          'content' => $this->description
        ]);
      if (!empty($this->keywords))
        $this->addTag ('meta', [
          'name'    => 'keywords',
          'content' => $this->keywords
        ]);
      if (!empty($this->author))
        $this->addTag ('meta', [
          'name'    => 'author',
          'content' => $this->author
        ]);
      if (!empty($application->favicon))
        $this->addTag ('link', [
          'rel'  => 'shortcut icon',
          'href' => $application->favicon
        ]);
      if (isset($this->extraHeadTags))
        $this->setContent ($this->extraHeadTags);
      $this->endTag ();
      $this->beginTag ('body', $this->bodyAttrs);
      if ($oldIEWarning)
        $this->page->preForm .= <<<HTML
<script>document.body.scroll='auto'</script>
<div class="oldIEWarning">$application->oldIEWarning</div>
HTML;
      $this->setContent ($this->preForm);
      $this->beginTag ('form', [
        'class'        => '',
        'action'       => property ($this, 'targetURL', $_SERVER['REQUEST_URI']),
        'method'       => 'post',
        'enctype'      => $this->enableFileUpload ? 'multipart/form-data' : null,
        'autocomplete' => $this->formAutocomplete ? null : 'off',
        'onsubmit'     => 'return Form_onSubmit()'
      ]);
      $this->addTag ('input', [
        'type'  => 'hidden',
        'name'  => '_action',
        'value' => property ($this, 'defaultAction')
      ]);

      echo $pageContent;

      $this->endTag ();

      echo $this->footer;
      $this->endTag ();
      $this->endTag ();
    }
    else $this->renderChildren ();
  }

  private function packScripts ()
  {
    global $application;
    $filenames = '';
    $script    = '';
    foreach ($this->page->scripts as $URI)
      $filenames .= $URI;
    $filename = md5 ($filenames);
    $uri      = "$application->cachePath/$filename.js";
    $path     = "$application->baseDirectory/$uri";
    if (!fileExists ($path)) {
      $script = '';
      foreach ($this->page->scripts as $URI) {
        $filenames .= $URI;
        $url       = (substr ($URI, 0, 4) == 'http') ? $URI : $application->toFilePath ($URI);
        $newScript = file_get_contents ($url);
        if ($newScript === false)
          throw new MatisseException("Can't load javascript at $url");
        $script .= $newScript;
      }
      $packed = $this->compressScript ($script);
      file_put_contents ($path, $packed, FILE_TEXT);
    }
    $this->addTag ('script', [
      'type' => 'text/javascript',
      'src'  => $application->toURI ($uri)
    ]);
  }

  private function compressScript ($script)
  {
    $apiArgs = [
      'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
      'output_format'     => 'text',
      'output_info'       => 'compiled_code'
    ];
    $args    = 'js_code=' . urlencode ($script);
    foreach ($apiArgs as $key => $value)
      $args .= '&' . $key . '=' . urlencode ($value);
    $call = curl_init ();
    curl_setopt_array ($call, [
      CURLOPT_URL            =>
        'http://closure-compiler.appspot.com/compile',
      CURLOPT_POST           => 1,
      CURLOPT_POSTFIELDS     => $args,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_HEADER         => 0,
      CURLOPT_FOLLOWLOCATION => 0
    ]);
    $jscomp = curl_exec ($call);
    curl_close ($call);
    return $jscomp;
  }

}
