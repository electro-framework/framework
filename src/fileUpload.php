<?php
require "../../vendor/autoload.php";
$FRAMEWORK = 'private/selene';
$NO_APPLICATION = true;
$application = new Application();
$application->setup();
ob_end_clean();

$assetsURI = $application->toURI($application->inlineArchivePath);
$assetsRelativeURI = "$application->inlineArchivePath";
$assetsDir = $application->toFilePath($application->inlineArchivePath);

try {
  if (isset($_FILES['file'])) {
    $srcPath = $_FILES['file']['tmp_name'];
    $srcName = $_FILES['file']['name'];
    $ext = substr($srcName,strrpos($srcName,'.') + 1);
    $filename = md5(date('YmdHis')).'.'.$ext;
    $targetPath = "$assetsDir/$filename";
    if (!@move_uploaded_file($srcPath,$targetPath))
      throw new FileException(FileException::CAN_NOT_SAVE_FILE,$targetPath);
    @chmod($targetPath,0666);

    $output = array(
      'filelink' => "../$FRAMEWORK/download.php?URI=".urlencode("$assetsRelativeURI/$filename")."&name=".urlencode($srcName),
      'filename' => $srcName
    );
    header('Content-Type: application/json');
    echo stripslashes(json_encode($output));
    return;
  }
  else error('No file received');
}
catch (Exception $e) {
  error($e->getMessage());
}

function error($msg = '') {
  header('HTTP/1.0 500 Internal Server Error');
  echo '<h1>Error</h1>'.$msg;
}
