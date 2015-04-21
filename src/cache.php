<?php
$k = $_GET['f'];
if ($k[0] == '/') {
  $myPath = $_SERVER['SCRIPT_FILENAME'];
  preg_match('#/([^/]+)/src/#',$myPath,$match);
  $projectName = $match[1];
  $k = preg_replace("#/.*/$projectName/(.*)#",'$1',$k);
}
$p = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))).'/';
$f = $p.$k;
$mime = Media::mime_content_type($f);
if ($mime === false)
  throw new FatalException('Unknown mime type');
$data = @file_get_contents($f);
if ($data === false)
  throw new FatalException('Can\'t read file '.$f);
$x = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
if (substr_count($x,'gzip')) {
  header('Content-Encoding: gzip');
  $data = gzencode($data,1,FORCE_GZIP);
}
header('Content-Length: '.strlen($data));
Media::outputData($data,$mime,Media::CACHING_ENABLED);

