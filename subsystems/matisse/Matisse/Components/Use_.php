<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class UseProperties extends ComponentProperties
{
  /**
   * @var string
   */
  public $as = '';
  /**
   * @var string
   */
  public $service = '';
}

/**
 * A component that injects services from the Dependency Injection container directly into a view's view-model.
 *
 * <p>The `<Use>` component is meant to be used mainly on:
 *
 *  1. Template partials that are shared between pages on your app.
 *     <p>By using components of this type on a template, you are saved from having to create a PHP class for each
 *     template just for setting common/shared data on their view-models.
 *
 *  2. Page templates, for reducing the amount of services that would have to be injected into the
 *     page controller's constructor (when those services are only used on the view), therefore reserving that
 *     constructor for the injection of business-related services only.
 *
 * <p>The benefits of using this approach include the reduction of the amount of boilerplate code that has be
 * written, and the reduction of the coupling between the page's businness logic and the view-related logic, among
 * others.
 *
 * ##### Syntax
 * ```
 * <Use service=Fully\Qualified\ClassName as=yourService/>
 * <!-- or -->
 * <Use service=serviceAlias/>
 *
 * Now you can use {yourService.property.or.getterMethod}, or {serviceAlias.property.or.getterMethod}.
 * ```
 *
 * ##### Using short names (alias) for services
 *
 * Instead of typing the fully qualified service class name (which, by the way, is not refactor-friendly), you may
 * instead refer to a previously defined service alias.
 * <p>As Selenia provides a true dependency injector and not the typical service container from where you could fetch
 * services by short alias names, you'll need to setup a service class alias map using the {@see ServiceAlias} service
 * on a service provider somewhere on your project.
 * <p>Selenia already provides some predefined alias for the most common services.
 */
class Use_ extends Component
{
  const propertiesClass = UseProperties::class;

  /** @var UseProperties */
  public $props;

  protected function render ()
  {
    $prop = $this->props;
    if (exists ($prop->service)) {
      if (!exists ($as = $prop->as))
        $as = str_segmentsLast ($prop->service, '\\');
      $service                                               = $this->context->injector->make ($prop->service);
      $this->context->getDataBinder ()->getViewModel ()->$as = $service;
    }
  }
}
