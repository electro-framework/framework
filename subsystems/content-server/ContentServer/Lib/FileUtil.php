<?php
namespace Electro\ContentServer\Lib;

use Psr\Http\Message\UploadedFileInterface;

class FileUtil
{
  /**
   * Returns the MIME type for the given file.
   *
   * @param string $filename
   * @return string
   */
  static function getMimeType ($filename)
  {
    if (function_exists ('finfo_open')) {
      $finfo = finfo_open (FILEINFO_MIME_TYPE);
      $mime  = finfo_file ($finfo, $filename);
      finfo_close ($finfo);
      // When FILEINFO fails to detect the MIME type it returns a wrong text type or the generic octet-stream type.
      // In those cases, we must use the file extension to determine the MIME type.
      if (!str_beginsWith ($mime, 'text/') && $mime != 'application/octet-stream')
        return $mime;
    }
    $ext = pathinfo ($filename, PATHINFO_EXTENSION);
    if (array_key_exists ($ext, MIME_TYPES))
      return MIME_TYPES[$ext];
    return 'application/octet-stream';
  }

  /**
   * Determines the true MIME type of an uploaded file by examining the file (and not by just checking the extension or
   * the MIME type sent by the HTTP client).
   *
   * @param UploadedFileInterface $file
   * @return string
   */
  static function getUploadedFileMimeType (UploadedFileInterface $file)
  {
    $ext = pathinfo ($file->getClientFilename (), PATHINFO_EXTENSION);
    if (array_key_exists ($ext, MIME_TYPES))
      return MIME_TYPES[$ext];
    return self::getMimeType (self::getUploadedFilePath ($file));
  }

  /**
   * Gets the path of an uploaded file, which usually points to a system temporary folder.
   *
   * @param UploadedFileInterface $file
   * @return string
   */
  static function getUploadedFilePath (UploadedFileInterface $file)
  {
    return $file->getStream ()->getMetadata ('uri');
  }

  /**
   * Checks if the specified file is an image.
   *
   * @param string $filename
   * @return bool
   */
  static function isImageFile ($filename)
  {
    return self::isImageType (self::getMimeType ($filename));
  }

  /**
   * Checks if the specified MIME is of image type.
   *
   * @param string $type The file's MIME type.
   * @return bool
   */
  static function isImageType ($type)
  {
    return str_beginsWith ($type, 'image/');
  }

}

// private
const MIME_TYPES = [
  // text
  'txt'  => 'text/plain',
  'htm'  => 'text/html',
  'html' => 'text/html',
  'php'  => 'text/html',
  'css'  => 'text/css',
  'js'   => 'application/javascript',
  'json' => 'application/json',
  'xml'  => 'application/xml',

  // images
  'png'  => 'image/png',
  'jpe'  => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'jpg'  => 'image/jpeg',
  'gif'  => 'image/gif',
  'bmp'  => 'image/bmp',
  'ico'  => 'image/vnd.microsoft.icon',
  'tiff' => 'image/tiff',
  'tif'  => 'image/tiff',
  'svg'  => 'image/svg+xml',
  'svgz' => 'image/svg+xml',

  // archives
  'zip'  => 'application/zip',
  'rar'  => 'application/x-rar-compressed',
  'exe'  => 'application/x-msdownload',
  'msi'  => 'application/x-msdownload',
  'cab'  => 'application/vnd.ms-cab-compressed',

  // audio/video
  'mp3'  => 'audio/mpeg',
  'qt'   => 'video/quicktime',
  'mov'  => 'video/quicktime',

  // adobe
  'pdf'  => 'application/pdf',
  'psd'  => 'image/vnd.adobe.photoshop',
  'ai'   => 'application/postscript',
  'eps'  => 'application/postscript',
  'ps'   => 'application/postscript',

  // ms office
  'doc'  => 'application/msword',
  'rtf'  => 'application/rtf',
  'xls'  => 'application/vnd.ms-excel',
  'ppt'  => 'application/vnd.ms-powerpoint',

  // open office
  'odt'  => 'application/vnd.oasis.opendocument.text',
  'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',

  // Flash
  'swf'  => 'application/x-shockwave-flash',
  'flv'  => 'video/x-flv',
];
