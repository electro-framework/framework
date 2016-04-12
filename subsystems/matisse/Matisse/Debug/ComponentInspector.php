<?php
namespace Selenia\Matisse\Debug;

use PhpKit\Html5Tools\HtmlSyntaxHighlighter;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Expression;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;
use SplObjectStorage;

class ComponentInspector
{
  private static $inspecting = false;
  /** @var SplObjectStorage */
  private static $recursionMap;

  /**
   * Returns a textual representation of the component, suitable for debugging purposes.
   *
   * @param Component $component
   * @param bool      $deep
   * @return string
   * @throws ComponentException
   */
  static function inspect (Component $component, $deep = false)
  {
    if (self::$inspecting)
      return '';
    self::$inspecting   = true;
    self::$recursionMap = new SplObjectStorage;
    ob_start ();
    self::_inspect ($component, $deep);
    self::$inspecting = false;
    return "<code>" . ob_get_clean () . "</code>";
  }

  /**
   * Inspects the given set of components.
   *
   * @param Component[] $components
   * @param bool        $deep
   * @param bool        $nested True if no `<code>` block should be output.
   * @return string
   */
  static function inspectSet (array $components = null, $deep = false, $nested = false)
  {
    if (!$components || self::$inspecting)
      return '';
    self::$inspecting   = true;
    self::$recursionMap = new SplObjectStorage;
    ob_start ();
    foreach ($components as $component)
      self::_inspect ($component, $deep);
    self::$inspecting = false;
    return $nested ? ob_get_clean () : "<code>" . ob_get_clean () . "</code>";
  }

  /**
   * For internal use.
   *
   * @param Component $component
   * @param bool      $deep
   * @throws ComponentException
   */
  static private function _inspect (Component $component, $deep = true)
  {
    if (self::$recursionMap->contains ($component)) {
      echo "<i>recursion</i>";
      return;
    }
    self::$recursionMap->attach ($component);
    try {
      $component->enter ();

      $COLOR_BIND  = '#5AA';
      $COLOR_CONST = '#5A5';
      $COLOR_INFO  = '#CCC';
      $COLOR_PROP  = '#B00';
      $COLOR_TAG   = '#000;font-weight:bold';
      $COLOR_TYPE  = '#55A';
      $COLOR_VALUE = '#333';
      $Q           = "<i style='color:#CCC'>\"</i>";

      $tag        = $component->getTagName ();
      $hasContent = false;
      echo "<span style='color:$COLOR_TAG'>&lt;$tag</span>";
      if (!isset($component->parent) && !$component instanceof DocumentFragment)
        echo "&nbsp;<span style='color:$COLOR_INFO'>(detached)</span>";

      // Handle text node

      if ($component instanceof Text) {
        echo "<span style='color:$COLOR_TAG'>&gt;</span><div style='margin:0 0 0 15px'>";
        try {
          if ($component->isBound ('value')) {
            /** @var Expression $exp */
            $exp = $component->bindings['value'];
            $exp = self::inspectString ((string)$exp);
            echo "<span style='color:$COLOR_BIND'>$exp</span> = ";

            $v = self::getBindingValue ('value', $component, $error);
            if ($error) {
              echo $v;
              return;
            }
            if (!is_string ($v)) {
              echo typeInfoOf ($v);
              return;
            }
          }
          else $v = $component->props->value;
          $v = strlen (trim ($v)) ? HtmlSyntaxHighlighter::highlight ($v) : "<i>'$v'</i>";
          echo $v;
        }
        finally {
          echo "</div><span style='color:$COLOR_TAG'>&lt;/$tag&gt;<br></span>";
        }
        return;
      }

      // Handle other node types

      elseif ($component->supportsProperties) {
        /** @var ComponentProperties $propsObj */
        $propsObj = $component->props;
        if ($propsObj) $props = $propsObj->getAll ();
        else $props = null;
        if ($props)
          ksort ($props);


        if ($props) {
          echo "<table style='color:$COLOR_VALUE;margin:0 0 0 15px'><colgroup><col width=1><col width=1><col></colgroup>";

          // Display all scalar properties.

          foreach ($props as $k => $v) {
            $t          = $component->props->getTypeOf ($k);
            $isModified = $component->props->isModified ($k);
            $modifStyle = $isModified ? ' style="background:#FFE"' : ' style="opacity:0.5"';
            if ($t != type::content && $t != type::collection && $t != type::metadata) {
              $tn = $component->props->getTypeNameOf ($k);
              echo "<tr$modifStyle><td style='color:$COLOR_PROP'>$k<td><i style='color:$COLOR_TYPE'>$tn</i><td>";

              // Display data-binding
              if (isset($component->bindings[$k])) {
                /** @var Expression $exp */
                $exp = get ($component->bindings, $k);
                $exp = self::inspectString ((string)$exp);
                echo "<span style='color:$COLOR_BIND'>$exp</span> = ";

                $v = self::getBindingValue ($k, $component, $error);
                if ($error)
                  break;
              }

              if (is_null ($v))
                echo "<i style='color:$COLOR_INFO'>null</i>";

              else switch ($t) {
                case type::bool:
                  echo "<i style='color:$COLOR_CONST'>" . ($v ? 'true' : 'false') . '</i>';
                  break;
                case type::id:
                  echo "$Q$v$Q";
                  break;
                case type::number:
                  echo $v;
                  break;
                case type::string:
                  echo "$Q<span style='white-space: pre-wrap'>" .
                       self::inspectString (strval ($v)) .
                       "</span>$Q";
                  break;
                default:
                  if (is_object ($v))
                    echo sprintf ("<i style='color:$COLOR_CONST'>%s</i>", typeInfoOf ($v));
                  elseif (is_array ($v))
                    echo sprintf ("<i style='color:$COLOR_CONST'>array(%d)</i>", count ($v));
                  else {
                    $v = _e ($v);
                    echo "$Q$v$Q";
                  }
              }
            }
          }

          // Display all slot properties.

          foreach ($props as $k => $v) {
            $t = $component->props->getTypeOf ($k);
            if ($t == type::content || $t == type::collection || $t == type::metadata) {
              $tn         = $component->props->getTypeNameOf ($k);
              $isModified = $component->props->isModified ($k);
              $modifStyle = $isModified ? ' style="background:#FFE"' : ' style="opacity:0.5"';
              echo "<tr$modifStyle><td style='color:$COLOR_PROP'>$k<td><i style='color:$COLOR_TYPE'>$tn</i><td>";

              /** @var Expression $exp */
              $exp = get ($component->bindings, $k);
              if (isset($exp)) {
                $exp = self::inspectString ((string)$exp);
                echo "<span style='color:$COLOR_BIND'>$exp</span> = ";
                $v = self::getBindingValue ($k, $component, $error);
                if ($error) {
                  echo $v;
                  break;
                }
              }
              if ($v && ($v instanceof Component || is_array ($v)))
                switch ($t) {
                  case type::content:
                    if ($v) {
                      echo "<tr><td><td colspan=2>";
                      self::_inspect ($v, $deep);
                    }
                    else echo "<i style='color:$COLOR_INFO'>null</i>";
                    break;
                  case type::metadata:
                    if ($v) {
                      echo "<tr><td><td colspan=2>";
                      self::_inspect ($v, $deep);
                    }
                    else echo "<i style='color:$COLOR_INFO'>null</i>";
                    break;
                  case type::collection:
                    echo "of <i style='color:$COLOR_TYPE'>", $component->props->getRelatedTypeNameOf ($k), '</i>';
                    if ($v) {
                      echo "<tr><td><td colspan=2>";
                      self::_inspectSet ($v, true);
                    }
                    else echo " = <i style='color:$COLOR_INFO'>[]</i>";
                    break;
                }
              else if (is_array ($v))
                echo "<i style='color:$COLOR_INFO'>[]</i>";
              else if (isset($v))
                printf ("<b style='color:red'>WRONG TYPE: %s</b>", typeInfoOf ($v));
              else echo "<i style='color:$COLOR_INFO'>null</i>";
              echo '</tr>';
            }
          }

          echo "</table>";
        }
      }

      // If deep inspection is enabled, recursively inspect all children components.

      if ($deep) {
        $content = null;
        if ($component->hasChildren ())
          $content = $component->getChildren ();
        elseif ($component instanceof CompositeComponent && ($skin = $component->getSkin ()))
          $content = [$skin];
        if ($content) {
          echo "<span style='color:$COLOR_TAG'>&gt;</span><div style=\"margin:0 0 0 15px\">";
          self::_inspectSet ($content, $deep);
          echo '</div>';
          $hasContent = true;
        }
      }
      echo "<span style='color:$COLOR_TAG'>" . ($hasContent ? "&lt;/$tag&gt;<br>" : "/&gt;<br>") . "</span>";
    }
    finally {
      $component->leave ();
    }
  }

  /**
   * For internal use.
   *
   * @param Component[] $components
   * @param bool        $deep
   */
  static private function _inspectSet (array $components, $deep)
  {
    foreach ($components as $component)
      self::_inspect ($component, $deep);
  }

  /**
   * @param string    $prop
   * @param Component $component
   * @param bool      $error
   * @return mixed
   */
  private static function getBindingValue ($prop, Component $component, &$error)
  {
    $error = $l = false;
    try {
      $l = ob_get_level ();
      $v = $component->getComputedPropValue ($prop);
    }
    catch (\Exception $e) {
      $error = true;
      while (ob_get_level () > $l)
        ob_end_clean ();
      return "<b style='color:red'>ERROR</b>";
    }
    return $v;
  }

  /**
   * @param string $s
   * @return string
   */
  private static function inspectString ($s)
  {
    return str_replace ("\n", '&#8626;', htmlspecialchars ($s));
  }

}
