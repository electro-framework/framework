<?php
namespace Selenia\Matisse;

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
        self::inspect($component, $deep);
    return $nested ? ob_get_clean () : "<code>" . ob_get_clean () . "</code>";
  }

  /**
   * Returns a textual representation of the component, suitable for debugging purposes.
   * @param Component $component
   * @param bool      $deep
   */
  private static function _inspect (Component $component, $deep = true)
  {
    $tag        = $component->getTagName ();
    $hasContent = false;
    echo "<span style='color:#9ae6ef'>&lt;$tag</span>";
    if (!isset($component->parent))
      echo '&nbsp;<span style="color:#888">(detached)</span>';
    if ($component->supportsAttributes) {
      echo '<table style="color:#CCC;margin:0 0 0 15px"><colgroup><col width=1><col width=1><col></colgroup>';
      $props = $component->attrs()->getAll ();
      if (!empty($props))
        foreach ($props as $k => $v)
          if (isset($v)) {
            $t = $component->attrs()->getTypeOf ($k);
            if (!$deep || ($t != AttributeType::SRC && $t != AttributeType::PARAMS && $t != AttributeType::METADATA)) {
              $tn = $component->attrs()->getTypeNameOf ($k);
              echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i><td>";
              switch ($t) {
                case AttributeType::BOOL:
                  echo '<i>' . ($v ? 'TRUE' : 'FALSE') . '</i>';
                  break;
                case AttributeType::ID:
                  echo "\"$v\"";
                  break;
                case AttributeType::NUM:
                  echo $v;
                  break;
                case AttributeType::TEXT:
                  echo "\"<span style='color:#888;white-space: pre-wrap'>" .
                       str_replace ("\n", '&#8626;', htmlspecialchars ($v)) .
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
            $t = $component->attrs()->getTypeOf ($k);
            if ($t == AttributeType::SRC || $t == AttributeType::PARAMS || $t == AttributeType::METADATA) {
              $tn = $component->attrs()->getTypeNameOf ($k);
              echo "<tr><td style='color:#eee'>$k<td><i style='color:#ffcb69'>$tn</i>" .
                   "<tr><td><td colspan=2>";
              switch ($t) {
                case AttributeType::SRC:
                case AttributeType::METADATA:
                  echo self::inspect($component->attrs()->$k, $deep);
                  break;
                case AttributeType::PARAMS:
                  echo self::inspectSet ($component->attrs()->$k, true, true);
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
      if (!empty($component->children)) {
        $hasContent = true;
        echo '<span style="color:#9ae6ef">&gt;</span><div style="margin:0 0 0 30px">';
        foreach ($component->children as $c)
          self::_inspect($c, true);
        echo '</div>';
      }
    }
    echo "<span style='color:#9ae6ef'>" . ($hasContent ? "&lt;/$tag&gt;<br>" : "/&gt;<br>") . "</span>";
  }

}
