<?php
use Electro\Media;

/**
 * Either generates a dinamically resized image and optionally stores it in a disk cache,
 * or returns a previously cached resized image.
 */

$id = get($_REQUEST,'id');
$width = !empty($_REQUEST['w']) ? $_REQUEST['w'] : 0;
$height = !empty($_REQUEST['h']) ? $_REQUEST['h'] : 0;
$quality = !empty($_REQUEST['q']) ? $_REQUEST['q'] : 90;
$crop = isset($_REQUEST['c']) && $_REQUEST['c'] != '' ? $_REQUEST['c'] : ($width != 0 && $height != 0 ? 1 : 0); // c = 0,1,w,h
$cache = empty($_REQUEST['nc']);
$cacheMode = $cache? Media::CACHING_ENABLED : Media::CACHING_DISABLED;
$bgColor = !empty($_REQUEST['bg']) ? $_REQUEST['bg'] : 0;
$watermark = !empty($_REQUEST['wm']) ? $_REQUEST['wm'] : false;
$wmPadding = !empty($_REQUEST['wmp']) ? intval($_REQUEST['wmp']) : 0;
$opacity = !empty($_REQUEST['a']) ? intval($_REQUEST['a']) : 100;

if ($width == 0 && $height == 0 && !isset($_REQUEST['q']) && !isset($_REQUEST['c'])) {
  $ext = Media::getImageExt($id);
  $relativePath = "$application->imageArchivePath/$id.$ext";
  if ($application->imageRedirection)
    header('Location: '.$application->toURI($relativePath));
  else Media::streamFile("$application->baseDirectory/$relativePath",$cacheMode);
  exit;
}
$uri = $id.'_'.$width.'x'.$height.'_'.$quality.'_'.$crop.'.jpg';
$path = "$application->baseDirectory/$application->imagesCachePath/$uri";
$URI = $application->toURI("$application->imagesCachePath/$uri");

if (file_exists($path)) {
  if ($application->imageRedirection) {
    header("Content-type: image/jpeg");
    header("Location: $URI");
  }
  else Media::streamFile($path,$cacheMode);
  exit;
}
$ext = Media::getImageExt($id);
$srcPath = "$application->baseDirectory/$application->imageArchivePath/$id.$ext";
$image = Media::createFromFile($srcPath);
if (!$image) {
  header("HTTP/1.1 500 Can't read file $srcPath");
  echo "Can't read file $srcPath";
  exit;
}
$image2 = Media::resize($image,$width,$height,$crop,$bgColor);
imagedestroy($image);
if ($watermark) {
  $watermarkImage = imagecreatefrompng("$application->baseDirectory/$watermark");
  Media::applyWatermark($image2,$watermarkImage,$opacity,$wmPadding);
}
$data = Media::encode($image2,'image/jpeg',$quality);
imagedestroy($image2);

if ($cache) {
  Media::saveData($path,$data);
  if ($application->imageRedirection)
    header("Location: $URI");
  else Media::outputData($data,'image/jpeg',Media::CACHING_ENABLED);
}
else Media::outputData($data,'image/jpeg',Media::CACHING_DISABLED);

