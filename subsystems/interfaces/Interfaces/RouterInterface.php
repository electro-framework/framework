<?php
namespace Selenia\Interfaces;

interface RouterInterface
{
  function path ();

  function location ();

  function match ();
}
