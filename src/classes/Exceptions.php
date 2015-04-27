<?php
class Status {
  const FATAL = 0;
  const ERROR = 1;
  const WARNING = 2;
  const INFO = 3;
}

//--------------------------------------------------------------------------
class BaseException extends Exception {

  protected $status;
  public $title;

  public function __construct($message, $status, $title='') {
    $this->status = $status;
    $this->title = $title;
    parent::__construct($message,0);
  }

  public function getStatus() {
    return $this->status;
  }

  protected function inspect(Component $component, $deep = false) {
    ob_start();
    $component->inspect($deep);
    return ob_get_clean();
  }

}

//--------------------------------------------------------------------------
class FileException extends BaseException {
  const FILE_IS_REQUIRED = 1;
  const FILE_IS_INVALID = 2;
  const CAN_NOT_SAVE_FILE = 3;
  const FIELD_NOT_FOUND = 4;
  const CAN_NOT_DELETE_FILE = 5;
  const CAN_NOT_SAVE_TMP_FILE = 6;
  const FILE_TOO_BIG = 7;
  const FILE_NOT_FOUND = 8;

  public static $messages = array(
      self::FILE_IS_REQUIRED => "Tem de seleccionar um ficheiro.",
      self::FILE_IS_INVALID => "Tipo de ficheiro não suportado.",
      self::CAN_NOT_SAVE_FILE => "Não é possível guardar o ficheiro no servidor. Verifique as permissões da pasta respectiva: ",
      self::FIELD_NOT_FOUND => "O campo especificado não existe no formulário.",
      self::CAN_NOT_DELETE_FILE => "Não é possível remover o ficheiro do servidor. Verifique as permissões da pasta respectiva: ",
      self::CAN_NOT_SAVE_TMP_FILE => "Não é possível criar um ficheiro temporário no servidor. Verifique as permissões da pasta temporária e o espaço em disco disponível.",
      self::FILE_TOO_BIG => "O ficheiro excede o tamanho máximo permitido de ",
      self::FILE_NOT_FOUND => "O ficheiro não foi encontrado."
  );
  public static $statusLookup = array(
      self::FILE_IS_REQUIRED => Status::WARNING,
      self::FILE_IS_INVALID => Status::WARNING,
      self::CAN_NOT_SAVE_FILE => Status::ERROR,
      self::FIELD_NOT_FOUND => Status::FATAL,
      self::CAN_NOT_DELETE_FILE => Status::ERROR,
      self::CAN_NOT_SAVE_TMP_FILE => Status::ERROR,
      self::FILE_TOO_BIG => Status::ERROR,
      self::FILE_NOT_FOUND => Status::ERROR
  );

  public function __construct($code, $extra = '') {
    parent::__construct(self::$messages[$code] . $extra, self::$statusLookup[$code]);
  }

}

//--------------------------------------------------------------------------
class ImageException extends FileException {

  public static $messages = array(
      self::FILE_IS_REQUIRED => "Tem de seleccionar um ficheiro de imagem.",
      self::FILE_IS_INVALID => "Tipo de ficheiro não suportado. Por favor seleccione uma imagem no formato JPEG, GIF, PNG ou BMP.",
      self::CAN_NOT_SAVE_FILE => "Não é possível guardar o ficheiro no servidor. Verifique as permissões da pasta respectiva: ",
      self::FIELD_NOT_FOUND => "O campo especificado não existe no formulário.",
      self::CAN_NOT_DELETE_FILE => "Não é possível remover o ficheiro do servidor. Verifique as permissões da pasta respectiva: ",
      self::CAN_NOT_SAVE_TMP_FILE => "Não é possível criar um ficheiro temporário no servidor. Verifique as permissões da pasta temporária e o espaço em disco disponível.",
      self::FILE_TOO_BIG => "O ficheiro excede o tamanho máximo permitido de "
  );

  public function __construct($code, $extra = '') {
    BaseException::__construct(self::$messages[$code] . $extra, self::$statusLookup[$code]);
  }

}

//--------------------------------------------------------------------------
class FatalException extends BaseException {

  public function __construct($msg,$title='') {
    parent::__construct($msg,Status::FATAL,$title);
  }

}

//--------------------------------------------------------------------------
class ConfigException extends FatalException {

  public function __construct($msg) {
    parent::__construct($msg,"Error on application configuration");
  }

}

//--------------------------------------------------------------------------
class SessionException extends BaseException {
  const MISSING_INFO = 1;
  const UNKNOWN_USER = 2;
  const WRONG_PASSWORD = 3;
  const NO_SESSION = 4;

  public static $messages = array(
    self::MISSING_INFO => "Por favor especifique um utilizador e a respectiva senha.",
    self::UNKNOWN_USER => "Utilizador desconhecido.",
    self::WRONG_PASSWORD => "A senha está incorrecta.",
    self::NO_SESSION => 'Não há qualquer sessão activa.'
  );

  public function __construct($code) {
    $this->code = $code;
    parent::__construct(self::$messages[$code], Status::WARNING);
  }

}

//--------------------------------------------------------------------------
class ValidationException extends BaseException {
  const OTHER = 0;
  const REQUIRED_FIELD = 1;
  const INVALID_NUMBER = 2;
  const INVALID_DATE = 3;
  const PASSWORD_MISMATCH = 4;
  const INVALID_EMAIL = 5;
  const DUPLICATE_RECORD = 6;
  const INVALID_DATETIME = 7;
  const INVALID_VALUE = 8;

  public static $messages = array(
  	  self::OTHER => '',
      self::REQUIRED_FIELD => "Por favor preencha o campo '#'.",
      self::INVALID_NUMBER => "Por favor introduza um número válido no campo #.",
      self::INVALID_DATE => "Por favor introduza uma data válida (aaaa-mm-dd) no campo #.",
      self::PASSWORD_MISMATCH => "As senhas não são iguais.",
      self::INVALID_EMAIL => "Por favor introduza um endereço de e-mail válido no campo #.",
      self::DUPLICATE_RECORD => "Não é permitida a inserção de um registo duplicado.<br/>Rectifique o campo #.",
      self::INVALID_DATETIME => "Por favor introduza uma data e hora válidas (aaaa-mm-dd hh:mm:ss) no campo #.",
      self::INVALID_VALUE => "Valor inválido para o campo #."
  );

  public $fieldName;

  public function __construct($code, $fieldName, $msg = NULL) {
    $this->code = $code;
    $this->fieldName = $fieldName;
    if (is_null($msg))
      parent::__construct(str_replace('#', "<b>$fieldName</b>", self::$messages[$code]), Status::WARNING);
    else
      parent::__construct($msg, Status::WARNING);
  }

}

//--------------------------------------------------------------------------
class FileNotFoundException extends BaseException {

  public function __construct($filename) {
    parent::__construct("File <b>$filename</b> was not found.", Status::FATAL);
  }

}

//--------------------------------------------------------------------------
class DatabaseException extends BaseException {

  public function __construct($message, $code, $query, array $params = NULL) {
    $this->code = $code;
    if (isset($params)) {
      for ($i = 0; $i < count($params); ++$i)
        if (is_null($params[$i]))
          $params[$i] = '<i>NULL</i>';
        else if (is_string($params[$i]))
          $params[$i] = "'" . htmlentities($params[$i]) . "'";
      $p = '';
      for ($i = 1; $i <= count($params); ++$i)
        $p .= "<b>$i:</b> " . $params[$i - 1] . "<br>";
    }
    else
      $p = '';
    parent::__construct("<h3>$message</h3><p><b>Error code</b>: $code</p><b>Query</b>:\n\n<code>$query</code>\n\n<b>Parameters</b>:\n\n$p\n", Status::FATAL,'Database error');
  }

}

//--------------------------------------------------------------------------
class FileWriteException extends BaseException {

  public function __construct($filename) {
    parent::__construct("File <b>$filename</b> can't be written to.\nPlease check the permissions on the file or on the containing folder.", Status::FATAL);
  }

}

//--------------------------------------------------------------------------
class DataModelException extends BaseException {

  public function __construct(DataObject $obj, $msg) {
    $dump = print_r($obj, TRUE);
    parent::__construct("$msg<h4>Instance properties:</h4><blockquote><pre>$dump</pre></blockquote>", Status::FATAL,'Data model error');
  }

}
