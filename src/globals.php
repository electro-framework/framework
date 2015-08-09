<?php

//------------------------------
//  Matisse-specific functions
//------------------------------

function normalizeTagName ($name)
{
  return str_replace (' ', '', ucwords (str_replace ('-', ' ', $name)));
}

function classNameToTagName ($name)
{
  return ltrim (strtolower (preg_replace ('/[A-Z]/', '_$0', $name)), '_');
}

function normalizeAttributeName ($name)
{
  return str_replace ('-', '_', strtolower ($name));
}

function denormalizeAttributeName ($name)
{
  return str_replace ('_', '-', $name);
}

function renameAttribute ($name)
{
  return str_replace ('-', '_', $name);
}

