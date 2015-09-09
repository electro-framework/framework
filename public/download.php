<?php
use Selenia\Exceptions\FatalException;
use Selenia\Exceptions\FileException;
use Selenia\Media;

$URI      = get ($_GET, 'URI');
$filename = get ($_GET, 'name');
$id       = get ($_GET, 'id');
$cache    = get ($_GET, 'cache', false);
$mode     = get ($_GET, 'mode', 'attachment'); // inline/attachment

if (empty($URI)) {
  if (is_null ($id))
    throw new FatalException('No id or URI were specified');
  $filename = urlencode (Media::getOriginalFileName ($id));
  $filepath =  $application->toFilePath (Media::getFileURI ($id));
}
else $filepath = $application->toFilePath ($URI);

if (!file_exists ($filepath))
  throw new FileException(FileException::FILE_NOT_FOUND, "<p>File: <b>$filepath</b>");

Media::streamFile ($filepath, $cache ? Media::CACHING_ENABLED : Media::CACHING_DISABLED,
  $mode == 'attachment' ? $filename : '');
