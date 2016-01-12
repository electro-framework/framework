<?php
namespace Selenia\Matisse\Debug;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class ComponentInspector
{
  static function inspect (Component $component, $deep = true)
  {
    ob_start (null, 0);
    self::_inspect ($component, $deep);
    return "<code>" . ob_get_clean () . "</code>";
  }

  /**
   * @param Component[] $components
   * @param bool        $deep
   * @param bool        $nested True if no `<code>` block should be output.
   * @return string
   */
  static function inspectSet (array $components = null, $deep = false, $nested = false)
  {
    ob_start (null, 0);
    foreach ($components as $component)
      self::_inspect ($component, $deep);
    return $nested ? ob_get_clean () : "<code>" . ob_get_clean () . "</code>";
  }

  /**
   * Returns a textual representation of the component, suitable for debugging purposes.
   *
   * @param Component $component
   * @param bool      $deep
   * @throws ComponentException
   */
  private static function _inspect (Component $component, $deep = true)
  {
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
    if ($component->supportsProperties) {
      echo "<table style='color:$COLOR_VALUE;margin:0 0 0 15px'><colgroup><col width=1><col width=1><col></colgroup>";
      /** @var ComponentProperties $propsObj */
      $propsObj = $component->props;
      if ($propsObj) $props = $propsObj->getAll ();
      else $props = null;
      if ($props)
        ksort ($props);

      // Display all scalar properties.

      if ($props)
        foreach ($props as $k => $v) {
          $t = $component->props->getTypeOf ($k);
          if ($t != type::content && $t != type::collection && $t != type::metadata) {
            $tn = $component->props->getTypeNameOf ($k);
            echo "<tr><td style='color:$COLOR_PROP'>$k<td><i style='color:$COLOR_TYPE'>$tn</i><td>";

            $exp = self::inspectString (get ($component->bindings, $k, ''));
            if ($exp != '')
              echo "<span style='color:$COLOR_BIND'>$exp</span> = ";

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
                else
                  echo "$Q$v$Q";
            }
          }
        }

      // Display all slot properties.

      if ($props)
        foreach ($props as $k => $v) {
          $t = $component->props->getTypeOf ($k);
          if ($t == type::content || $t == type::collection || $t == type::metadata) {
            $tn = $component->props->getTypeNameOf ($k);
            echo "<tr><td style='color:$COLOR_PROP'>$k<td><i style='color:$COLOR_TYPE'>$tn</i><td>";

            $exp = self::inspectString (get ($component->bindings, $k, ''));
            if ($exp != '')
              echo "<span style='color:$COLOR_BIND'>$exp</span> = ";

            switch ($t) {
              case type::content:
                echo $v ? "<tr><td><td colspan=2>" . self::inspect ($v, $deep)
                  : "<i style='color:$COLOR_INFO'>null</i>";
                break;
              case type::metadata:
                echo $v ? "<tr><td><td colspan=2>" . self::inspect ($v, $deep)
                  : "<i style='color:$COLOR_INFO'>null</i>";
                break;
              case type::collection:
                echo "of <i style='color:$COLOR_TYPE'>", $component->props->getRelatedTypeNameOf ($k), '</i>';
                echo $v ? "<tr><td><td colspan=2>" . self::inspectSet ($v, true, true)
                  : " = <i style='color:$COLOR_INFO'>[]</i>";
                break;
            }
            echo '</tr>';
          }
        }

      echo "</table>";
    }

    // If deep inspection is enabled, recursively inspect all children components.

    if ($deep) {
      if ($component->hasChildren ()) {
        $hasContent = true;
        echo "<span style='color:$COLOR_TAG'>&gt;</span><div style=\"margin:0 0 0 30px\">";
        foreach ($component->getChildren () as $c)
          self::_inspect ($c, true);
        echo '</div>';
      }
    }
    echo "<span style='color:$COLOR_TAG'>" . ($hasContent ? "&lt;/$tag&gt;<br>" : "/&gt;<br>") . "</span>";
  }

  private static function inspectString ($s)
  {
    return str_replace ("\n", '&#8626;', htmlspecialchars ($s));
  }

}
