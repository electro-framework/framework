<?php

namespace Electro\ContentRepository\Lib;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\PsrResponseFactory;

/**
 * An adpater Response Factory that delays the injection of a ResponseFactoryInterface instance until it is actually
 * needed (which ill never happen if the Content Repository is being used on a console application).
 */
class GlideResponseFactory extends PsrResponseFactory
{
  /**
   * @var InjectorInterface
   */
  private $injector;

  /* @noinspection PhpMissingParentConstructorInspection */
  /**
   * @param InjectorInterface $injector
   */
  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  public function create(FilesystemOperator $cache, $path)
	{
    /** @var ResponseFactoryInterface $factory */
    $factory              = $this->injector->make (ResponseFactoryInterface::class);
    $this->response       = $factory->make ();
    $this->streamCallback = function ($stream) use ($factory) {
      return $factory->makeBodyStream ('', $stream);
    };
    parent::create ($cache, $path);
  }

}
