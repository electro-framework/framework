<?php
namespace Electro\Database\Lib;

class DbUtil
{
  /**
   * Parses CSV-formatted data from a string and returns it as an array of arrays.
   *
   * @param array|string $columns An array of column names, or a string of comma-delimited column names.
   * @param string       $csv     The CSV data.
   * @return array The loaded data.
   */
  static function loadCSV ($columns, $csv)
  {
    if (is_string ($columns)) $columns = explode (',', $columns);
// Use an I/O stream instead of an actual file.
    $handle = fopen ('php://temp/myCSV', 'w+b');

// Write all the data to it
    fwrite ($handle, $csv);

// Rewind for reading
    rewind ($handle);

// use fgetcsv which tends to work better than str_getcsv in some cases
    $data = [];
    $i    = 0;
    try {
      while ($row = fgetcsv ($handle, null, ',', "'")) {
        ++$i;
        $data[] = array_combine ($columns, $row);
      }
      fclose ($handle);
    }
    catch (ErrorException $e) {
      echo "\nInvalid row #$i\n\nColumns:\n";
      var_export ($columns);
      echo "\n\nRow:\n";
      var_export ($row);
      echo "\n";
      exit (1);
    }
    return $data;
  }

}
