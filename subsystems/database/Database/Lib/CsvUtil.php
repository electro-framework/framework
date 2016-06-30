<?php
namespace Electro\Database\Lib;

use Electro\Exceptions\Fatal\FileNotFoundException;
use ErrorException;

class CsvUtil
{
  /**
   * Loads CSV-formatted data from a file, parses it and returns an array of arrays.
   *
   * Data should be comma-delimited and string may be enclosed on double quotes.
   *
   * @param string            $filename The file path.
   * @param array|string|null $columns  Either:<ul>
   *                                    <li> an array of column names,
   *                                    <li> a string of comma-delimited column names,
   *                                    <li> null (or ommited) to read column names from the first row of data.
   *                                    </ul>
   * @return array The loaded data.
   * @throws FileNotFoundException
   */
  static function loadCSV ($filename, $columns = null)
  {
    $handle = @fopen ($filename, 'rb', true);
    if (!$handle)
      throw new FileNotFoundException($filename);
    return self::loadCsvFromStream ($handle, $columns);
  }

  /**
   * Loads CSV-formatted data from a stream, parses it and returns an array of arrays.
   *
   * It closes the stream before returning.
   * Data should be comma-delimited and string may be enclosed on double quotes.
   *
   * @param resource          $handle  An opened stream.
   * @param array|string|null $columns Either:<ul>
   *                                   <li> an array of column names,
   *                                   <li> a string of comma-delimited column names,
   *                                   <li> null (or ommited) to read column names from the first row of data.
   *                                   </ul>
   * @return array The loaded data.
   */
  static function loadCsvFromStream ($handle, $columns = null)
  {
    if (is_null ($columns))
      $columns = trim (fgets ($handle));

    if (is_string ($columns))
      $columns = explode (',', $columns);

// use fgetcsv which tends to work better than str_getcsv in some cases
    $data = [];
    $i    = 0;
    $row  = '';
    try {
      while ($row = fgetcsv ($handle, null, ',', '"')) {
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

  /**
   * Parses CSV-formatted data from a string and returns it as an array of arrays.
   *
   * Data should be comma-delimited and string may be enclosed on double quotes.
   *
   * @param array|string|null $columns Either:<ul>
   *                                   <li> an array of column names,
   *                                   <li> a string of comma-delimited column names,
   *                                   <li> null (or ommited) to read column names from the first row of data.
   *                                   </ul>
   * @param string            $csv     The CSV data.
   * @return array The loaded data.
   */
  static function parseCSV ($columns, $csv)
  {
// Use an I/O stream instead of an actual file.
    $handle = fopen ('php://temp/myCSV', 'w+b');

// Write all the data to it
    fwrite ($handle, $csv);

// Rewind for reading
    rewind ($handle);

    return self::loadCsvFromStream ($handle, $columns);
  }

}
