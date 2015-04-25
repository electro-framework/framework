<?php
use selene\matisse\AttributeType;
use selene\matisse\ComponentAttributes;
use selene\matisse\components\Parameter;
use selene\matisse\VisualComponent;

class MainMenuAttributes extends ComponentAttributes
{
  /** @var  Parameter */
  public $header;
  /** @var  string */
  public $expand_icon;

  protected function typeof_header () { return AttributeType::SRC; }
  protected function typeof_expand_icon () { return AttributeType::TEXT; }
}

class MainMenu extends VisualComponent
{
  protected $containerTag = 'ul';

  protected $depthClass = ['', 'nav-second-level', 'nav-third-level', 'nav-fourth-level'];

  /**
   * Returns the component's attributes.
   * @return MainMenuAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return MainMenuAttributes
   */
  public function newAttributes ()
  {
    return new MainMenuAttributes($this);
  }

  protected function render ()
  {
    global $application;

    $this->beginContent ();
    $this->runSet ($this->getChildren ('header'));
    $xi = $this->attrs ()->get ('expand_icon');

    if (!empty($application->routingMap->routes))
      echo html (
        map ($application->routingMap->routes, function ($route) use ($xi) {
          if (!$route->onMenu) return null;
          $active = $route->selected ? '.active' : '';
          $sub    = $route->hasSubNav ? '.sub' : '';
          return [
            h ("li$active$sub", [
              h ("a$active", ['href' => $route->URL], [
                when ($route->icon, [h ('i.' . $route->icon), ' ']),
                either ($route->subtitle, $route->title),
                iftrue (isset($xi) && $route->hasSubNav, h ("span.$xi"))
              ]),
              when ($route->hasSubNav, $this->renderMenuItem ($route->routes, $xi))
            ])
          ];
        })
      );

    else echo html (
      map ($application->routingMap->groups, function ($grp) use ($xi) {
        return [
          h ('li.header', [
            h ('a', [
              when ($grp->icon, [h ('i.' . $grp->icon), ' ']),
              $grp->title
            ])
          ]),
          map ($grp->routes, function ($route) use ($xi) {
            if (!$route->onMenu) return null;
            $active = $route->selected ? '.active' : '';
            $sub    = $route->hasSubNav ? '.sub' : '';
            return [
              h ("li.treeview$active$sub", [
                h ("a$active", ['href' => $route->URL], [
                  when ($route->icon, [h ('i.' . $route->icon), ' ']),
                  either ($route->subtitle, $route->title),
                  iftrue (isset($xi) && $route->hasSubNav, h ("span.$xi"))
                ]),
                when ($route->hasSubNav, $this->renderMenuItem ($route->routes, $xi))
              ])
            ];
          })
        ];
      })
    );
  }

  private function renderMenuItem ($pages, $xi, $depth = 1)
  {
    return h ('ul.nav.collapse.' . $this->depthClass[$depth], [
      map ($pages, function ($route) use ($xi, $depth) {
        if (!$route->onMenu) return null;
        $active  = $route->selected ? '.active' : '';
        $sub     = $route->hasSubNav ? '.sub' : '';
        $current = $route->matches ? '.current' : '';
        return
          h ("li.$active$sub$current", [
            h ("a$active", ['href' => $route->URL], [
              when ($route->icon, [h ('i.' . $route->icon), ' ']),
              either ($route->subtitle, $route->title),
              iftrue (isset($xi) && $route->hasSubNav, h ("span.$xi"))
            ]),
            when ($route->hasSubNav, $this->renderMenuItem ($route->routes, $xi, $depth + 1))
          ]);
      })
    ]);
  }

}


