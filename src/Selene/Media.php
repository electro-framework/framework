<?php
namespace Selene;

use Selene\Exceptions\FileException;
use Selene\Exceptions\ImageException;

abstract class Media {

  /** Original jpeg images resized to a lower size will use this compression level. */
  const ORIGINAL_IMAGE_QUALITY = 95;
  /** Maximum width/height for original images. */
  const ORIGINAL_IMAGE_MAX_SIZE = 1024;
  /** Timezone for HTTP dates. */
  const MY_TIMEZONE = 'Europe/Lisbon';
  /** How long should a browser cache downloaded images/files, in seconds. Default = 1 month.*/
  const CACHE_DURATION = 2592000;
  /** Don't output cache control headers. */
  const CACHING_UNSPECIFIED = null;
  /** Output headers to disable caching of the output resource. */
  const CACHING_DISABLED = 0;
  /** Output headers to enable caching of the output resource. */
  const CACHING_ENABLED = 1;

  //--------------------------------------------------------------------------
  public static function insertUploadedImage($imageFieldName,$gallery = null,$sort = null,$key = null,$caption = null)
  //--------------------------------------------------------------------------
  {


    $fileFormFieldName = $imageFieldName.'_file';
    if (Media::isFileUploaded($fileFormFieldName)) {

      Media::checkFileIsValidImage($fileFormFieldName);
      $tempFile = Media::getUploadedFilePath($fileFormFieldName);
      $ext = Media::getImageType($tempFile);
      $mime = self::mime_content_type($tempFile);
      $newId = uniqid();

      if ($mime == 'image/x-ms-bmp') {
        $mime = 'image/png';
        $ext = 'png';
      }
      Media::allocateImage($newId,$ext,$gallery,$sort,$key,$caption);
      $image = self::createFromFile($tempFile);
      if ($image !== false) {
        $newImage = self::clampImageSize($image,self::ORIGINAL_IMAGE_MAX_SIZE,self::ORIGINAL_IMAGE_MAX_SIZE);
        if ($newImage === false)
          Media::saveUploadedImage($fileFormFieldName,$newId,$ext);
        else {
          self::encodeAndSaveImage($newImage,$mime,self::ORIGINAL_IMAGE_QUALITY,$newId,$ext);
          imagedestroy($newImage);
        }
        imagedestroy($image);
        return $newId;
      }
    }
    return null;
  }
  //--------------------------------------------------------------------------
  public static function insertImage($filename,$gallery = null,$sort = null,$key = null,$caption = null)
  //--------------------------------------------------------------------------
  {
    Media::checkFileIsValidImage(null,$filename);
    $ext = self::getImageType($filename);
    $mime = self::mime_content_type($filename);
    $newId = uniqid();
    if ($mime == 'image/x-ms-bmp') {
      $mime = 'image/png';
      $ext = 'png';
    }
    Media::allocateImage($newId,$ext,$gallery,$sort,$key,$caption);
    $image = self::createFromFile($filename);
    if ($image !== false) {
      $newImage = self::clampImageSize($image,self::ORIGINAL_IMAGE_MAX_SIZE,self::ORIGINAL_IMAGE_MAX_SIZE);
      if ($newImage === false)
        Media::saveImage($filename,$newId,$ext);
      else {
        self::encodeAndSaveImage($newImage,$mime,self::ORIGINAL_IMAGE_QUALITY,$newId,$ext);
        imagedestroy($newImage);
      }
      imagedestroy($image);
      return $newId;
    }
  }
  //--------------------------------------------------------------------------
  public static function insertUploadedFile($fileFieldName)
  //--------------------------------------------------------------------------
  {
    $fileFormFieldName = $fileFieldName.'_file';
    if (Media::isFileUploaded($fileFormFieldName)) {
      $tempFile = Media::getUploadedFileName($fileFormFieldName);
      $ext = Media::getExt($tempFile);
      $name = Media::getFileName($tempFile);
      $newId = uniqid();
      Media::allocateFile($newId,$ext,$name);
      Media::saveUploadedFile($fileFormFieldName,$newId,$ext);
      return $newId;
    }
    return null;
  }
  //--------------------------------------------------------------------------
  public static function insertFile($filename)
  //--------------------------------------------------------------------------
  {
    $ext = Media::getExt($filename);
    $name = Media::getFileName($filename);
    $newId = uniqid();
    Media::allocateFile($newId,$ext,$name);
    Media::saveFile($filename,$newId,$ext);
    return $newId;
  }
  //--------------------------------------------------------------------------
  /**
   * Returns a copy of an image shrunk to fit the maximum specified dimensions.
   * If the image is not resized, false is returned.
   * @param resource $image A GD2 image resource handle.
   * @param number $maxWidth
   * @param number $maxHeight
   * @return resource|boolean A GD2 image resource or false if the image was not shrunk.
   */
  public static function clampImageSize($image,$maxWidth,$maxHeight)
  //--------------------------------------------------------------------------
  {
    $originalW = imagesx($image);
    $originalH = imagesy($image);
    if ($originalW <= $maxWidth && $originalH <= $maxHeight)
      return false;
    if ($originalW >= $originalH) {
      $w = $maxWidth;
      $h = 0;
    }
    else {
      $w = 0;
      $h = $maxHeight;
    }
    $newImage = self::resize($image,$w,$h,0);
    return $newImage;
  }
  //--------------------------------------------------------------------------
  public static function getImageExt($id)
  //--------------------------------------------------------------------------
  {
    return database_query('SELECT ext FROM Images WHERE id=?',[$id])->fetchColumn();
  }
  //--------------------------------------------------------------------------
  public static function deleteImage($id)
  //--------------------------------------------------------------------------
  {
    if (!empty($id)) {
      $ext = self::getImageExt($id);
      $path = self::getImagePath($id,$ext);
      database_query('DELETE FROM Images WHERE id=?',[$id]);
      if (file_exists($path)) {
        if (!@unlink($path))
          throw new ImageException(ImageException::CAN_NOT_DELETE_FILE,$path);
        foreach (glob(self::getCachePath($id,'_*')) as $filename)
          @unlink($filename);
      }
    }
  }
  //--------------------------------------------------------------------------
  public static function getImagePath($id,$ext = null)
  //--------------------------------------------------------------------------
  {
    global $application;
    if (!isset($ext))
      $ext = self::getImageExt($id);
    $path = $application->toFilePath($application->imageArchivePath);
    return "$path/$id.$ext";
  }
  //--------------------------------------------------------------------------
  public static function getCachePath($id,$suffix)
  //--------------------------------------------------------------------------
  {
    global $application;
    $path = $application->toFilePath($application->imagesCachePath);
    return "$path/$id$suffix.jpg";
  }
  //--------------------------------------------------------------------------
  public static function getImageURI($id)
  //--------------------------------------------------------------------------
  {
    global $application;
    return $application->getImageURI($id.'.'.self::getImageExt($id));
  }
  //--------------------------------------------------------------------------
  public static function saveUploadedImage($fileFieldName,$id,$ext = '')
  //--------------------------------------------------------------------------
  {
    if ($ext == '')
      $ext = self::getUploadedFileExt($fileFieldName);
    $path = self::getImagePath($id,$ext);
    if (!@move_uploaded_file($_FILES[$fileFieldName]['tmp_name'],$path))
      throw new ImageException(ImageException::CAN_NOT_SAVE_FILE,$path);
    chmod($path,0666);
  }
  //--------------------------------------------------------------------------
  public static function saveImage($filename,$id,$ext)
  //--------------------------------------------------------------------------
  {
    $path = self::getImagePath($id,$ext);
    if (!@rename($filename,$path))
      throw new ImageException(ImageException::CAN_NOT_SAVE_FILE,$path);
    chmod($path,0666);
  }
  //--------------------------------------------------------------------------
  public static function encodeAndSaveImage($image,$mime,$quality,$id,$ext)
  //--------------------------------------------------------------------------
  {
    $path = self::getImagePath($id,$ext);
    $data = Media::encode($image,$mime,$quality);
    self::saveData($path,$data);
  }
  //--------------------------------------------------------------------------
  public static function updateImageInfo($id,$gallery = null,$sort = null,$key = null,$caption = null)
  //--------------------------------------------------------------------------
  {
    database_query(
      'UPDATE Images SET gallery=?,sort=?,`key`=?,caption=? WHERE id=?',
      [
        $gallery,
        $sort,
        $key,
        $caption,
        $id
      ]
    );
  }
  //--------------------------------------------------------------------------
  public static function insertImageInfo($id,$ext,$gallery = null,$sort = null,$key = null,$caption = null)
  //--------------------------------------------------------------------------
  {
    if (is_null($sort)) {
      $maxsort = database_get("SELECT IFNULL((SELECT MAX(sort) FROM Images WHERE gallery=? AND `key` IS NULL),-1)+1",[$gallery]);

      if (is_null($key))
        database_query(
          'INSERT INTO Images (id,ext,gallery,sort,`key`,caption)
           VALUES (?,?,?,?,?,?)',
          [
            $id,
            $ext,
            $gallery,
            $maxsort,
            $key,
            $caption
          ]
        );
      else database_query(
          'INSERT INTO Images (id,ext,gallery,sort,`key`,caption)
           VALUES (?,?,?,?,?,?)',
          [
            $id,
            $ext,
            $gallery,
            $maxsort,
            $key,
            $key,
            $caption
          ]
        );
    }
    else database_query(
      'INSERT INTO Images (id,ext,gallery,sort,`key`,caption)
       VALUES (?,?,?,?,?,?)',
      [
        $id,
        $ext,
        $gallery,
        $sort,
        $key,
        $caption
      ]
    );
  }
  //--------------------------------------------------------------------------
  public static function allocateImage($id,$ext,$gallery = null,$sort = null,$key = null,$caption = null)
  //--------------------------------------------------------------------------
  {
    self::insertImageInfo($id,$ext,$gallery,$sort,$key,$caption);
  }
  //--------------------------------------------------------------------------
  public static function isFileUploaded($fileFormFieldName)
  //--------------------------------------------------------------------------
  {
    if (array_key_exists($fileFormFieldName,$_FILES)) {
      if ($_FILES[$fileFormFieldName]['error'] == 1)
        throw new FileException(FileException::FILE_TOO_BIG,ini_get('upload_max_filesize'));
      return $_FILES[$fileFormFieldName]['size'] > 0;
    }
    return false;
  }
  //--------------------------------------------------------------------------
  public static function getUploadedFilePath($fileFormFieldName)
  //--------------------------------------------------------------------------
  {
    if (array_key_exists($fileFormFieldName,$_FILES))
      return $_FILES[$fileFormFieldName]['tmp_name'];
    return NULL;
  }
  //--------------------------------------------------------------------------
  public static function getUploadedFileName($fileFormFieldName)
  //--------------------------------------------------------------------------
  {
    if (array_key_exists($fileFormFieldName,$_FILES))
      return $_FILES[$fileFormFieldName]['name'];
    return NULL;
  }
  //--------------------------------------------------------------------------
  public static function getUploadedFileExt($fileFormFieldName)
  //--------------------------------------------------------------------------
  {
    if (array_key_exists($fileFormFieldName,$_FILES))
      return self::getExt($_FILES[$fileFormFieldName]['name']);
    return NULL;
  }
  //--------------------------------------------------------------------------
  public static function getFileName($filePath)
  //--------------------------------------------------------------------------
  {
    $info = pathinfo($filePath);
    return $info['filename'];
  }
  //--------------------------------------------------------------------------
  public static function getExt($fileName)
  //--------------------------------------------------------------------------
  {
    $info = pathinfo($fileName);
    return strtolower(get($info,'extension',''));
  }
  //--------------------------------------------------------------------------
  public static function checkFileIsValidImage($fileFieldName,$filename = null)
  //--------------------------------------------------------------------------
  {
    /*$mime = $_FILES[$fileFieldName]['type'];
        if ($mime != 'image/jpeg' && $mime != 'image/pjpeg')
            throw new ImageException(ImageException::FILE_IS_INVALID);*/
    $tmp = isset($filename) ? $filename : $_FILES[$fileFieldName]['tmp_name'];
    if (!file_exists($tmp))
      throw new ImageException(ImageException::CAN_NOT_SAVE_TMP_FILE);
    $info = @getimagesize($tmp);
    if (!$info)
      throw new ImageException(ImageException::FILE_IS_INVALID);
    $type = $info[2];
    if ($type != IMAGETYPE_JPEG && $type != IMAGETYPE_GIF && $type != IMAGETYPE_PNG && $type != IMAGETYPE_BMP)
      throw new ImageException(ImageException::FILE_IS_INVALID);
  }
  //--------------------------------------------------------------------------
  public static function checkFileIs($fileFieldName,$ext)
  //--------------------------------------------------------------------------
  {
    $name = $_FILES[$fileFieldName]['name'];
    $p = strrpos($name,'.');
    if ($p !== false)
      if (strtolower($ext) == strtolower(substr($name,$p + 1)))
        return;
    throw new ImageException(ImageException::FILE_IS_INVALID);
  }
  //--------------------------------------------------------------------------
  public static function getImageType($filePath)
  //--------------------------------------------------------------------------
  {
    if (!file_exists($filePath))
      throw new ImageException(ImageException::CAN_NOT_SAVE_TMP_FILE,$filePath);
    $info = @getimagesize($filePath);
    if (!$info)
      throw new ImageException(ImageException::FILE_IS_INVALID);
    switch($info[2]) {
      case IMAGETYPE_JPEG: return 'jpg';
      case IMAGETYPE_GIF:  return 'gif';
      case IMAGETYPE_PNG:  return 'png';
      case IMAGETYPE_BMP:  return 'bmp';
    }
    throw new ImageException(ImageException::FILE_IS_INVALID);
  }
  //--------------------------------------------------------------------------
  public static function getFileExt($id)
  //--------------------------------------------------------------------------
  {
    return database_query('SELECT ext FROM Files WHERE id=?',[$id])->fetchColumn();
  }
  //--------------------------------------------------------------------------
  public static function getFileBaseName($id)
  //--------------------------------------------------------------------------
  {
    return database_query('SELECT name FROM Files WHERE id=?',[$id])->fetchColumn();
  }
  //--------------------------------------------------------------------------
  public static function getFileURI($id)
  //--------------------------------------------------------------------------
  {
    global $application;
    return $application->getFileURI($id.'.'.self::getFileExt($id));
  }
  //--------------------------------------------------------------------------
  public static function getFileDownloadURI($id)
  //--------------------------------------------------------------------------
  {
    global $application,$FRAMEWORK;
    return "$FRAMEWORK/download.php?id=$id";
  }
  //--------------------------------------------------------------------------
  public static function getOriginalFileName($id)
  //--------------------------------------------------------------------------
  {
    //return database_query("SELECT name||'.'||ext FROM Files WHERE id=?",[$id])->fetchColumn();
    return database_query("SELECT CONCAT(name,'.',ext) FROM Files WHERE id=?",[$id])->fetchColumn();
  }
  //--------------------------------------------------------------------------
  public static function deleteFile($id)
  //--------------------------------------------------------------------------
  {
    if (!empty($id)) {
      $ext = self::getFileExt($id);
      $path = self::getFilePath($id,$ext);
      database_query('DELETE FROM Files WHERE id=?',[$id]);
      if (file_exists($path)) {
        if (!unlink($path))
          throw new FileException(FileException::CAN_NOT_DELETE_FILE,$path);
      }
    }
  }
  //--------------------------------------------------------------------------
  public static function getFilePath($id,$ext = null)
  //--------------------------------------------------------------------------
  {
    global $application;
    if (!isset($ext))
      $ext = self::getFileExt($id);
    $path = $application->toFilePath($application->fileArchivePath);
    return "$path/$id.$ext";
  }
  //--------------------------------------------------------------------------
  public static function saveUploadedFile($fileFormFieldName,$id,$ext)
  //--------------------------------------------------------------------------
  {
    $path = self::getFilePath($id,$ext);
    if (!move_uploaded_file($_FILES[$fileFormFieldName]['tmp_name'],$path))
      throw new FileException(FileException::CAN_NOT_SAVE_FILE,$path);
    chmod($path,0666);
  }
  //--------------------------------------------------------------------------
  public static function saveFile($filename,$id,$ext)
  //--------------------------------------------------------------------------
  {
    $path = self::getFilePath($id,$ext);
    if (!move_uploaded_file($filename,$path))
      throw new FileException(FileException::CAN_NOT_SAVE_FILE,$path);
    chmod($path,0666);
  }
  //--------------------------------------------------------------------------
  public static function updateFileInfo($id,$name = null)
  //--------------------------------------------------------------------------
  {
    database_query(
      'UPDATE Files SET name=? WHERE id=?',
      [
        $name,
        $id
      ]
    );
  }
  //--------------------------------------------------------------------------
  public static function insertFileInfo($id,$ext,$name = null)
  //--------------------------------------------------------------------------
  {
    database_query(
      'INSERT INTO Files (id,ext,name) VALUES (?,?,?)',
      [
        $id,
        $ext,
        $name
      ]
    );
  }
  //--------------------------------------------------------------------------
  public static function allocateFile($id,$ext,$name = null)
  //--------------------------------------------------------------------------
  {
    self::insertFileInfo($id,$ext,$name);
  }
  //--------------------------------------------------------------------------
  public static function duplicateImage($id)
  //--------------------------------------------------------------------------
  {
    database_begin();
    try {
      $ext = self::getImageExt($id);
      $srcPath = self::getImagePath($id,$ext);
      $newImgId = uniqid();
      self::allocateImage($newImgId,$ext);
      $dstPath = self::getImagePath($newImgId,$ext);
      copy($srcPath,$dstPath);
      database_commit();
      return $newImgId;
    }
    catch(Exception $e) {
      database_rollback();
      throw $e;
    }
  }
  //--------------------------------------------------------------------------
  public static function duplicateFile($id)
  //--------------------------------------------------------------------------
  {
    database_begin();
    try {
      $ext = self::getFileExt($id);
      $name = self::getFileBaseName($id);
      $srcPath = self::getFilePath($id,$ext);
      $newFileId = uniqid();
      self::allocateFile($newFileId,$ext,$name);
      $dstPath = self::getImagePath($newFileId,$ext);
      copy($srcPath,$dstPath);
      database_commit();
      return $newFileId;
    }
    catch(Exception $e) {
      database_rollback();
      throw $e;
    }
  }
  //--------------------------------------------------------------------------
  /**
   * Resizes an image to the specified dimensions without distortion, optionally
   * croping it to fit.
   * @param resource $image A GD2 image resource.
   * @param number $desiredW The desired width for the resized image.
   * @param number $desiredH The desired height for the resized image.
   * @param string $crop The resizing mode:
   * <br>1 = stretch the image to the exact dimensions specified and crop the excess;
   * <br>0 = resize without cropping;
   * <br>w = rezize to the specified height and create empty space horizontally.
   * @param type $bgColor The color for the generated empty space on the resulting image (if present).
   * @return resource The generated image's GD2 resource.
   */
  public static function resize($image,$desiredW,$desiredH,$crop,$bgColor = 0)      //returns an image
  //--------------------------------------------------------------------------
  {
    $originalW = imagesx($image);
    $originalH = imagesy($image);
    if ($desiredW != 0 || $desiredH != 0) {
      if ($desiredW == 0) $desiredW = $originalW * $desiredH / $originalH;    //calculate proportional width
      else if ($desiredH == 0) $desiredH = $originalH * $desiredW / $originalW;  //calculate proportional height
    }
    else {
      $desiredW = $originalW;
      $desiredH = $originalH;
    }
    $originalRatio = $originalW / $originalH;
    $desiredRatio = $desiredW / $desiredH;
    switch ($crop) {
      case '0':
        if ($originalRatio < $desiredRatio)  //thumb is wider: stretch/shrink height
        {
          $newW = $originalRatio * $desiredH;
          $newH = $desiredH;
        }
        else  //thumb is taller: stretch/shrink width
        {
          $newW = $desiredW;
          $newH = $newW / $originalRatio;
        }
        break;
      case '1':
        if ($originalRatio < $desiredRatio)          //stretch width and crop height
        {
          $newH = $desiredW / $originalRatio;
          $newW = $desiredW;
        }
        else                        //stretch height and crop width
        {
          $newW = $originalRatio * $desiredH;
          $newH = $desiredH;
        }
        break;
      case 'w':
        if ($originalRatio < $desiredRatio)  //stretch height
        {
          $newW = $originalRatio * $desiredH;
          $newH = $desiredH;
          $desiredW = $newW;
        }
        else                        //stretch height and crop width
        {
          $newW = $originalRatio * $desiredH;
          $newH = $desiredH;
        }
        break;
    }
    $output = imagecreatetruecolor($desiredW,$desiredH);
    if ($bgColor) {
      $c = hexdec($bgColor);
      $B = $c & 255;
      $G = ($c >> 8) & 255;
      $R = $c >> 16;
      $bkColor = imagecolorallocate($output,$R,$G,$B);
      imagefilledrectangle($output,0,0,$desiredW,$desiredH,$bkColor);
    }
    $x = ($desiredW - $newW) / 2.0;
    $y = ($desiredH - $newH) / 2.0;
    //if ($x > 0) $x = 0;
    //if ($y > 0) $y = 0;
    $w = $newW;
    $h = $newH;
    //if ($x + $w < $desiredW) $w = $desiredW - $x;
    //if ($y + $h < $desiredH) $h = $desiredH - $y;
    imagecopyresampled($output, $image, $x, $y, 0, 0, $w, $h, $originalW, $originalH);
    return $output;
  }
  //--------------------------------------------------------------------------
  public static function removeTransparency($img,$force = false)              //returns an image or null if unsupported image type. Do not reuse the original image!
  //--------------------------------------------------------------------------
  {
    $w = imagesx($img);
    $h = imagesy($img);
    $trnprt_indx = imagecolortransparent($img);
    if ($trnprt_indx >= 0) {
      $new_img = ImageCreateTrueColor($w,$h);
      $trnprt_color = imagecolorsforindex($img, $trnprt_indx);
      $trnprt_indx = imagecolorallocate($new_img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
      imagefill($new_img, 0, 0, $trnprt_indx);
      imagecolortransparent($new_img, $trnprt_indx);
      imagecopymerge($new_img,$img,0,0,0,0,$w,$h,100);
      imagedestroy($img);
      return $new_img;
    }
    else if ($force) {
      $new_img = ImageCreateTrueColor($w,$h);
      $bkColor = imagecolorallocate($new_img,255,255,255);
      imagefilledrectangle($new_img,0,0,$w,$h,$bkColor);
      imagecopy($new_img,$img,0,0,0,0,$w,$h);
      imagedestroy($img);
      return $new_img;
    }
    return $img;
  }
  //--------------------------------------------------------------------------
  public static function imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity)
  //--------------------------------------------------------------------------
  {
      // getting the watermark width
      $w = imagesx($src_im);
      // getting the watermark height
      $h = imagesy($src_im);
      // creating a cut resource
      $cut = imagecreatetruecolor($src_w, $src_h);
      // copying that section of the background to the cut
      imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
      // placing the watermark now
      imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
      imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
      imagedestroy($cut);
  }
  //--------------------------------------------------------------------------
  /**
   * Creates a GD2 image resource from the specified file.
   * @param string $filename Full path of image file to open.
   * @return resource A GD2 image resource or null if unsupported image type.
   */
  public static function createFromFile($filename)
  //--------------------------------------------------------------------------
  {
    $info = @getimagesize($filename);
    switch ($info[2]) {
      case IMAGETYPE_GIF:
        $img = @imagecreatefromgif($filename);
        return self::removeTransparency($img);
      case IMAGETYPE_JPEG:
        return @imagecreatefromjpeg($filename);
      case IMAGETYPE_PNG:
        $img = @imagecreatefrompng($filename);
        return self::removeTransparency($img,true);
      case IMAGETYPE_BMP:
        return self::imagecreatefrombmp($filename);
    }
    echo 'Unsupported image type. File '.$filename;
    exit;
  }
  //--------------------------------------------------------------------------
  /**
   * Creates a GD2 image resource from the specified encoded image data in jpeg/png/gif format.
   * @param string $filedata The image's encoded data.
   * @return resource A GD2 image resource or null if unsupported image type.
   */
  public static function createFromFileData($filedata)
  //--------------------------------------------------------------------------
  {
    $image = imagecreatefromstring($filedata);
    if ($image === false) return null;
    return $image;
  }
  //--------------------------------------------------------------------------
  /**
   * Creates encoded image data in jpeg/png/gif format from a GD2 image resource.
   * @param resource $image The GD2 image resource handle.
   * @param string $mime The MIME image type of the desired encoding.
   * @param number $quality The jpeg quality percentage (for jpeg encoding only).
   * @return string Image data or null if unsupported image MIME type.
   */
  public static function encode($image,$mime,$quality)
  //--------------------------------------------------------------------------
  {
    ob_start();
    switch (strtolower($mime)) {
      case 'image/jpeg':
        imagejpeg($image, null, $quality);
        break;
      case 'image/gif':
        imagetruecolortopalette($image,false,256);
        imagegif($image);
        break;
      case 'image/png':
        imagepng($image);
        break;
      default:
        ob_end_clean();
        return null;
    }
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
  }
  //--------------------------------------------------------------------------
  private static function imagecreatefrombmp($filename)
  //--------------------------------------------------------------------------
  {
    $tmp_name = tempnam("/tmp","GD");
    if (self::convertBMPtoGD($filename,$tmp_name)) {
      $img = imagecreatefromgd($tmp_name);
      unlink($tmp_name);
      return $img;
    }
    return false;
  }
  //--------------------------------------------------------------------------
  /**
   * Converts a Windows BMP image to a GD image.
   * The color depth of 32 bit is not supported.
   * @param string $src Source filename.
   * @param string $dest Destination filename.
   * @return boolean False if the conversion failed.
   */
  private static function convertBMPtoGD($src, $dest = false)
  //--------------------------------------------------------------------------
  {
    if (! ($src_f = fopen ( $src, "rb" ))) {
      return false;
    }
    if (! ($dest_f = fopen ( $dest, "wb" ))) {
      return false;
    }
    $header = unpack("vtype/Vsize/v2reserved/Voffset",fread($src_f,14));
    $info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant",fread($src_f,40));

    extract ( $info );
    extract ( $header );

    if ($type != 0x4D42) //signature "BM"
      return false;
    if ($bits == 32)
      return false;
    if ($height > 99999) {
      $height = 0x100000000 - $height; //FIX negative height denotes vertically unflipped image
      $flipped = false;
    }
    else $flipped = true;

    $palette_size = $offset - 54;
    $ncolor = $palette_size / 4;
    $gd_header = "";
    // true-color vs. palette
    $gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
    $gd_header .= pack ( "n2", $width, $height );
    $gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
    if ($palette_size) {
      $gd_header .= pack ( "n", $ncolor );
    }
    // no transparency
    $gd_header .= "\xFF\xFF\xFF\xFF";

    fwrite ( $dest_f, $gd_header );

    if ($palette_size) {
      $palette = fread ( $src_f, $palette_size );
      $gd_palette = "";
      $j = 0;
      while ( $j < $palette_size ) {
        $b = $palette {$j ++};
        $g = $palette {$j ++};
        $r = $palette {$j ++};
        $a = $palette {$j ++};
        $gd_palette .= "$r$g$b$a";
      }
      $gd_palette .= str_repeat ( "\x00\x00\x00\x00", 256 - $ncolor );
      fwrite ( $dest_f, $gd_palette );
    }

    $scan_line_size = (($bits * $width) + 7) >> 3;
    $scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size & 0x03) : 0;

    for($i = 0, $l = $height - 1; $i < $height; $i ++, $l --) {
      // BMP stores scan lines starting from bottom (except if $flipped == false)
      fseek ( $src_f, $offset + (($scan_line_size + $scan_line_align) * ($flipped ? $l : $i)) );
      $scan_line = fread ( $src_f, $scan_line_size );
      if ($bits == 24) {
        $gd_scan_line = "";
        $j = 0;
        while ( $j < $scan_line_size ) {
          $b = $scan_line {$j ++};
          $g = $scan_line {$j ++};
          $r = $scan_line {$j ++};
          $gd_scan_line .= "\x00$r$g$b";
        }
      } else if ($bits == 8) {
        $gd_scan_line = $scan_line;
      } else if ($bits == 4) {
        $gd_scan_line = "";
        $j = 0;
        while ( $j < $scan_line_size ) {
          $byte = ord ( $scan_line {$j ++} );
          $p1 = chr ( $byte >> 4 );
          $p2 = chr ( $byte & 0x0F );
          $gd_scan_line .= "$p1$p2";
        }
        $gd_scan_line = substr ( $gd_scan_line, 0, $width );
      } else if ($bits == 1) {
        $gd_scan_line = "";
        $j = 0;
        while ( $j < $scan_line_size ) {
          $byte = ord ( $scan_line {$j ++} );
          $p1 = chr ( ( int ) (($byte & 0x80) != 0) );
          $p2 = chr ( ( int ) (($byte & 0x40) != 0) );
          $p3 = chr ( ( int ) (($byte & 0x20) != 0) );
          $p4 = chr ( ( int ) (($byte & 0x10) != 0) );
          $p5 = chr ( ( int ) (($byte & 0x08) != 0) );
          $p6 = chr ( ( int ) (($byte & 0x04) != 0) );
          $p7 = chr ( ( int ) (($byte & 0x02) != 0) );
          $p8 = chr ( ( int ) (($byte & 0x01) != 0) );
          $gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
        }
        $gd_scan_line = substr ( $gd_scan_line, 0, $width );
      }

      fwrite ( $dest_f, $gd_scan_line );
    }
    fclose ( $src_f );
    fclose ( $dest_f );
    return true;
  }
  //--------------------------------------------------------------------------
  /**
   * Applies a watermark to the specified image.
   * @param resource $image The target image's GD2 resource handle.
   * @param resource $watermark The watermark's GD2 resource handle.
   * @param number $opactiy The watermark opacity percentage. Defaults to 100.
   * @param number $padding Distance of the watermark from the bottom and right edges of the image.
   */
  public static function applyWatermark($image,$watermark,$opacity = 100,$padding = 0) {
  //--------------------------------------------------------------------------
    $img_w = imagesx($image);
    $img_h = imagesy($image);
    $wm_w = imagesx($watermark);
    $wm_h = imagesy($watermark);
    self::imageCopyMergeAlpha($image,$watermark,$img_w - $wm_w - $padding,$img_h - $wm_h - $padding,0,0,$wm_w,$wm_h,$opacity);
  }
  //--------------------------------------------------------------------------
  /**
   * FIX: Because mime_content_type has been deprecated in php.
   * @param $filename
   * @return bool|string MIME type or false if mime type is not known.
   */
  public static function mime_content_type($filename) {
  //--------------------------------------------------------------------------
    $p = explode('.',$filename);
    $ext = array_pop($p);
    switch ($ext) {
      case 'js':
        return 'application/javascript';
        break;
      case 'css':
        return 'text/css';
        break;
    }
    if (function_exists('mime_content_type'))
      return mime_content_type($filename);
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $type = finfo_file($finfo,$filename);
      finfo_close($finfo);
      return $type;
    }
    switch ($ext) {
      case 'png':
        return 'image/png';
        break;
      case 'jpeg':
        return 'image/jpeg';
        break;
      case 'jpg':
        return 'image/jpeg';
        break;
      case 'gif':
        return 'image/gif';
        break;
      case 'bmp':
        return 'image/x-ms-bmp';
        break;
      case 'pdf':
        return 'application/pdf';
        break;
      case 'doc':
        return 'application/msword';
        break;
      case 'docx':
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
      default:
        return false;
    }
  }
  //--------------------------------------------------------------------------
  /**
   * Similar to outputData(), but reads from the source file chunks of 1K bytes
   * and outputs each one to the browser, therefore reducing memory consumption.
   * @param string $filepath The file path where the data will be read from.
   * @param number|null $cachingMode One of the CACHING_xxx constants. Defaults to CACHING_UNSPECIFIED.
   * @param string $filename If specified, causes the browser to save the content to a file with the specified name on the user's hard disk.
   */
public static function streamFile($filepath,$cachingMode = null,$filename = '') {
  //--------------------------------------------------------------------------
    $mime = self::mime_content_type($filepath);
    self::outputData('',$mime,$cachingMode,$filename);
    $f = fopen($filepath,'rb');
    if ($f === false) {
      header("HTTP/1.1 500 Can't read file $filepath");
      exit;
    }
    while (!feof($f))
      echo fread($f,1024);
    fclose($f);
  }
  //--------------------------------------------------------------------------
  /**
   * Outputs the specified file data to the browser.
   * @param string $data Raw binary data.
   * @param string|boolean $mime The data's MIME type. False to not output a header.
   * @param number|null $cachingMode One of the CACHING_xxx constants. Defaults to CACHING_UNSPECIFIED.
   * @param string $filename If specified, causes the browser to save the content to a file with the specified name on the user's hard disk.
   */
  public static function outputData($data,$mime = false,$cachingMode = null,$filename = '') {
  //--------------------------------------------------------------------------
    if ($mime)
      header("Content-type: $mime");
    if ($cachingMode === self::CACHING_ENABLED) {
      date_default_timezone_set(self::MY_TIMEZONE);
      $date = date("D, j M Y H:i:s",time() + self::CACHE_DURATION);
      header("Expires: $date UTC");
      header("Cache-Control: Public");
      header("Pragma: Public");
    }
    elseif ($cachingMode === self::CACHING_DISABLED) {
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      header("Cache-Control: no-cache, must-revalidate");
    }
    if ($filename)
      header("Content-disposition: attachment; filename=\"$filename\"");
    echo $data;
  }
  //--------------------------------------------------------------------------
  /**
   * Saves the specified data to a file.
   * @param string $path The file path where the data will be saved.
   * @param string $data The raw binary data to be saved.
   * @throws ImageException If the file could not be created.
   */
  public static function saveData($path,$data)
  //--------------------------------------------------------------------------
  {
    $fh = fopen($path,'wb');
    if (!$fh)
      throw new ImageException(ImageException::CAN_NOT_SAVE_FILE,$path);
    fwrite($fh,$data);
    fclose($fh);
    chmod($path,0666);
    $z = file_exists($path);
  }

}

