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
    $tag        = $component->getTagName ();
    $hasContent = false;
    echo "<span style='color:#9ae6ef'>&lt;$tag</span>";
    if (!isset($component->parent))
      echo '&nbsp;<span style="color:#888">(detached)</span>';
    if ($component->supportsProperties) {
      echo '<table style="color:#CCC;margin:0 0 0 15px"><colgroup><col width=1><col width=1><col></colgroup>';
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
            echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i><td>";

            $exp = htmlspecialchars (get ($component->bindings, $k));
            if (isset($exp))
              echo "<span style='color:#c5a3e6'>$exp</span> = ";

            switch ($t) {
              case type::bool:
                echo '<i>' . ($v ? 'TRUE' : 'FALSE') . '</i>';
                break;
              case type::id:
                echo "\"$v\"";
                break;
              case type::number:
                echo $v;
                break;
              case type::string:
                echo "\"<span style='color:#888;white-space: pre-wrap'>" .
                     str_replace ("\n", '&#8626;', htmlspecialchars (strval ($v))) .
                     '</span>"';
                break;
              default:
                if (is_null ($v))
                  echo 'null';
                elseif (is_object ($v))
                  echo '<i>object</i>';
                elseif (is_array ($v))
                  echo '<i>array</i>';
                else
                  echo "\"$v\"";
            }
          }
        }

      // Display all structured properties.

      if (!empty($props))
        foreach ($props as $k => $v) {
          $t = $component->props->getTypeOf ($k);
          if ($t == type::content || $t == type::collection || $t == type::metadata) {
            $tn = $component->props->getTypeNameOf ($k);
            echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i><td>";

            $exp = htmlspecialchars (get ($component->bindings, $k));
            if (isset($exp))
              echo "<span style='color:#c5a3e6'>$exp</span> = ";

            switch ($t) {
              case type::content:
                echo $v ? "<tr><td><td colspan=2>" . self::inspect ($v, $deep) : '<i style="color:#888">(empty)</i>';
                break;
              case type::metadata:
                echo $v ? "<tr><td><td colspan=2>" . self::inspect ($v, $deep) : '<i style="color:#888">(empty)</i>';
                break;
              case type::collection:
                echo 'of <i style=\'color:#ffcb69\'>', $component->props->getRelatedTypeNameOf ($k), '</i>';
                echo $v ? "<tr><td><td colspan=2>" . self::inspectSet ($v, true, true) : ' <i style="color:#888">(empty)</i>';
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
        echo '<span style="color:#9ae6ef">&gt;</span><div style="margin:0 0 0 30px">';
        foreach ($component->getChildren () as $c)
          self::_inspect ($c, true);
        echo '</div>';
      }
    }
    echo "<span style='color:#9ae6ef'>" . ($hasContent ? "&lt;/$tag&gt;<br>" : "/&gt;<br>") . "</span>";
  }

}
