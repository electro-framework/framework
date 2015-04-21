<?php
require "../../vendor/autoload.php";
$FRAMEWORK = 'private/selene';
$NO_APPLICATION = true;
$application = new Application();
$application->setup();
ob_end_clean();

$assetsRelativeURI = "../$application->inlineArchivePath";
$galleryRelativeURI = "../$application->galleryPath";
$assetsDir = $application->toFilePath($application->inlineArchivePath);

try {
  $dir = dir($assetsDir);
  $output = array();
  while($file = $dir->read()) {
    if ($file[0] == '.' || !is_file("$assetsDir/$file"))
      continue;
    $p = strrpos($file,'.');
    $thumb = substr($file,0,$p).'.jpg';
    $output[] = array(
      'thumb' => "$galleryRelativeURI/$thumb",
      'image' => "$assetsRelativeURI/$file"
    );
  }
  $dir->close();
  header('Content-Type: application/json');
  echo stripslashes(json_encode($output));
}
catch (Exception $e) {
  error($e->getMessage());
}

function error($msg = '') {
  header('HTTP/1.0 500 Internal Server Error');
  echo '<h1>Error</h1>'.$msg;
}
