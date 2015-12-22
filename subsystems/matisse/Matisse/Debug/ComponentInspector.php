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
      $propsObj = $component->props ();
      if ($propsObj) $props = $propsObj->getAll ();
      else $props = null;
      if (!empty($props))
        foreach ($props as $k => $v)
          if (isset($v)) {
            $t = $component->props ()->getTypeOf ($k);
            if (!$deep || ($t != type::content && $t != type::collection && $t != type::metadata)) {
              $tn = $component->props ()->getTypeNameOf ($k);
              echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i><td>";
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
                  if (is_object ($v))
                    echo '<i>object</i>';
                  else if (is_array ($v))
                    echo '<i>array</i>';
                  else
                    echo "\"$v\"";
              }
            }
          }
      if (!empty($props))
        foreach ($props as $k => $v)
          if (isset($v)) {
            $t = $component->props ()->getTypeOf ($k);
            if ($t == type::content || $t == type::collection || $t == type::metadata) {
              $tn = $component->props ()->getTypeNameOf ($k);
              echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i>" .
                   "<tr><td><td colspan=2>";
              switch ($t) {
                case type::content:
                case type::metadata:
                  echo self::inspect ($component->props ()->$k, $deep);
                  break;
                case type::collection:
                  echo self::inspectSet ($component->props ()->$k, true, true);
                  break;
              }
              echo '</tr>';
            }
          }
      if (isset($component->bindings)) {
        echo "<tr><td colspan=3><div style='border-top: 1px solid #666;margin:5px 0'></div>";
        foreach ($component->bindings as $k => $v)
          echo "<tr><td style='color:#7ae17a'>$k<td style='color:#ffcb69'>binding<td style='color:#c5a3e6'>" .
               htmlspecialchars ($v);
      }
      echo "</table>";
    }
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
