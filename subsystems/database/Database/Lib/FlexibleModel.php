<?php
namespace Electro\Database\Lib;

class FlexibleModel
{
  /**
   * The name of the field holding the JSON content on the database table.
   */
  const JSON_FIELD = 'content';
  /**
   * A list of field names that are stored on physical columns on the target table, instead of being stored on the JSON
   * field. The primary key field will always belong to this list, even if not specified.
   */
  const PHYSICAL_FIELDS = [];
  /**
   * The table's primary key field name.
   */
  const PRIMARY_KEY_FIELD = 'id';
  /**
   * The name of the database table where data is stored. It MUST be set on FlexibleModel subclasses.
   */
  const TABLE = '';

}
