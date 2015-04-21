<?php

class Mailer {

  public $smtp;
  public $from;
  public $from_name;
  public $username;
  public $password;
  public $subject;
  public $body;
  public $attachmentPath;
  public $attachmentName;
  public $attachmentType;
  public $contentType = 'text/html';

  public function sendTo($email, $name = '') {
    global $application, $FRAMEWORK;
    $mail = new PHPMailer();
    $mail->Subject = $this->subject;
    $mail->From = $this->from;
    $mail->FromName = $this->from_name;
    $mail->Host = $this->smtp;
    $mail->Mailer = 'smtp';
    $mail->CharSet = 'utf-8';
    $mail->ContentType = $this->contentType;
    $mail->Body = $this->body;
    $mail->SMTPAuth = TRUE;
    $mail->Username = $this->username;
    $mail->Password = $this->password;
    $mail->AddAddress($email, $name);
    $mail->SetLanguage('en', $application->toFilePath("$FRAMEWORK/classes/language/"));
    if (isset($this->attachmentPath))
      $mail->AddAttachment($this->attachmentPath, $this->attachmentName, 'base64', $this->attachmentType);
//echo "<pre>";print_r($mail);exit;

    if (!$mail->Send())
      throw new BaseException($mail->ErrorInfo, Status::ERROR);
  }

}

