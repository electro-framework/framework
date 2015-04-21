<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\components\Parameter;
use impactwave\matisse\VisualComponent;

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

    if (!empty($application->siteMap->pages))
      echo html (
        map ($application->siteMap->pages, function ($page) use ($xi) {
          if (!$page->onMenu) return null;
          return [
            h ('li' . ($page->hasSubNav ? '.sub' : ''), [
              h ('a' . ($page->selected ? '.active' : ''), ['href' => $page->URL], [
                when ($page->icon, [h ('i.' . $page->icon), ' ']),
                either ($page->subtitle, $page->title),
                iftrue (isset($xi) && $page->hasSubNav, h ("span.$xi"))
              ]),
              when ($page->hasSubNav, $this->renderMenuItem ($page->pages, $xi))
            ])
          ];
        })
      );

    else echo html (
      map ($application->siteMap->groups, function ($grp) use ($xi) {
        return [
          h ('li.header', [
            h ('a', [
              when ($grp->icon, [h ('i.' . $grp->icon), ' ']),
              $grp->title
            ])
          ]),
          map ($grp->pages, function ($page) use ($xi) {
            if (!$page->onMenu) return null;
            return [
              h ('li.treeview' . ($page->selected ? '.active' : '') . ($page->hasSubNav ? '.sub' : ''), [
                h ('a' . ($page->selected ? '.active' : ''), ['href' => $page->URL], [
                  when ($page->icon, [h ('i.' . $page->icon), ' ']),
                  either ($page->subtitle, $page->title),
                  iftrue (isset($xi) && $page->hasSubNav, h ("span.$xi"))
                ]),
                when ($page->hasSubNav, $this->renderMenuItem ($page->pages, $xi))
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
      map ($pages, function ($page) use ($xi, $depth) {
        if (!$page->onMenu) return null;
        return h ('li' . ($page->hasSubNav ? '.sub' : '') .
                  ($page->matches ? '.current' : ''), [
          h ('a' . ($page->selected ? '.active' : ''), ['href' => $page->URL], [
            when ($page->icon, [h ('i.' . $page->icon), ' ']),
            either ($page->subtitle, $page->title),
            iftrue (isset($xi) && $page->hasSubNav, h ("span.$xi"))
          ]),
          when ($page->hasSubNav, $this->renderMenuItem ($page->pages, $xi, $depth + 1))
        ]);
      })
    ]);
  }

}


