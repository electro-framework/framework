<?php
namespace Selenia\Matisse\Debug;

use Selenia\Matisse\Components\Base\Component;
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
    if (is_array ($components))
      foreach ($components as $component)
        self::inspect ($component, $deep);
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
    $COLOR_BIND  = '#c5a3e6';
    $COLOR_CONST = '#ffcb69';
    $COLOR_INFO  = '#888';
    $COLOR_PROP  = '#eee';
    $COLOR_TAG   = '#9ae6ef';
    $COLOR_TYPE  = '#70CC70';
    $COLOR_VALUE = '#CCC';

    $tag        = $component->getTagName ();
    $hasContent = false;
    echo "<span style='color:$COLOR_TAG'>&lt;$tag</span>";
    if (!isset($component->parent))
      echo "&nbsp;<span style='color:$COLOR_INFO'>(detached)</span>";
    if ($component->supportsProperties) {
      echo "<table style='color:$COLOR_VALUE;margin:0 0 0 15px'><colgroup><col width=1><col width=1><col></colgroup>";
      /** @var ComponentProperties $propsObj */
      $propsObj = $component->props;
      if ($propsObj) $props = $propsObj->getAll ();
      else $props = null;

      // Display all scalar properties.

      if (!empty($props))
        foreach ($props as $k => $v) {
          $t = $component->props->getTypeOf ($k);
          if ($t != type::content && $t != type::collection && $t != type::metadata) {
            $tn = $component->props->getTypeNameOf ($k);
            echo "<tr><td style='color:$COLOR_PROP'>$k<td><i style='color:$COLOR_TYPE'>$tn</i><td>";

            $exp = self::inspectString (get ($component->bindings, $k, ''));
            if ($exp != '')
              echo "<span style='color:$COLOR_BIND'>$exp</span> = ";

            if (is_null ($v))
              echo "<i style='color:$COLOR_CONST'>null</i>";

            else switch ($t) {
              case type::bool:
                echo "<i style='color:$COLOR_CONST'>" . ($v ? 'true' : 'false') . '</i>';
                break;
              case type::id:
                echo "\"$v\"";
                break;
              case type::number:
                echo $v;
                break;
              case type::string:
                echo "\"<span style='white-space: pre-wrap'>" .
                     self::inspectString (strval ($v)) .
                     '</span>"';
                break;
              default:
                if (is_object ($v))
                  echo "<i style='color:$COLOR_CONST'>object</i>";
                elseif (is_array ($v))
                  echo "<i style='color:$COLOR_CONST'>array</i>";
                else
                  echo "\"$v\"";
            }
          }
        }

      // Display all slot properties.

      if (!empty($props))
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
                  : "<i style='color:$COLOR_INFO'>(empty)</i>";
                break;
              case type::metadata:
                echo $v ? "<tr><td><td colspan=2>" . self::inspect ($v, $deep)
                  : "<i style='color:$COLOR_INFO'>(empty)</i>";
                break;
              case type::collection:
                echo "of <i style='color:$COLOR_TYPE'>", $component->props->getRelatedTypeNameOf ($k), '</i>';
                echo $v ? "<tr><td><td colspan=2>" . self::inspectSet ($v, true, true)
                  : " <i style='color:$COLOR_INFO'>(empty)</i>";
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
