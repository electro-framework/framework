<?php
namespace Selenia\Faults;

const N = Faults::class . '\\';

interface Faults
{
  const ARG_NOT_ITERABLE          = N . "ARG_NOT_ITERABLE";
  const LINK_NOT_FOUND            = N . "LINK_NOT_FOUND";
  const MAP_MUST_HAVE_STRING_KEYS = N . "MAP_MUST_HAVE_STRING_KEYS";
}
