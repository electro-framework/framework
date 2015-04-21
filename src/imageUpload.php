<?php
require "../../vendor/autoload.php";
$FRAMEWORK = 'private/selene';
$NO_APPLICATION = true;
$application = new Application();
$application->setup();
ob_end_clean();

$assetsRelativeURI = "../$application->inlineArchivePath";
$assetsDir = $application->toFilePath($application->inlineArchivePath);
$galleryDir = $application->toFilePath($application->galleryPath);

try {
  if (isset($_FILES['file'])) {
    Media::checkFileIsValidImage('file');
    $srcPath = $_FILES['file']['tmp_name'];
    $ext = Media::getImageType($srcPath);
    $basename = md5(date('YmdHis'));
    $filename = "$basename.$ext";
    $targetPath = "$assetsDir/$filename";
    if (!@move_uploaded_file($srcPath,$targetPath))
      throw new ImageException(ImageException::CAN_NOT_SAVE_FILE,$targetPath);
    @chmod($targetPath,0666);
    saveFile($targetPath,"$galleryDir/$basename.jpg");

    $output = array(
      'filelink' => "$assetsRelativeURI/$filename"
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

function saveFile($srcPath,$targetPath) {
  $image = Media::createFromFile($srcPath);
  if (!$image) {
    error("Can't read file $path");
    exit;
  }
  $image2 = Media::resize($image,88,0,0);
  imagedestroy($image);
  $data = Media::encode($image2,'image/jpeg',90);
  imagedestroy($image2);
  Media::saveData($targetPath, $data);
}

function error($msg = '') {
  header('HTTP/1.0 500 Internal Server Error');
  echo '<h1>Error</h1>'.$msg;
}
