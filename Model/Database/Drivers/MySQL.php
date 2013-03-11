<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */

namespace Bread\Model\Database\Drivers;

use Bread;
use Bread\Configuration\Manager as CM;
use Bread\Promise;
use Bread\Model\Interfaces;
use Bread\Model\Database;
use Bread\L10n\Inflector;
use Exception;
use DateTime;
use mysqli;

class MySQL implements Database\Interfaces\Driver {
  const DEFAULT_PORT = 3306;
  const DATETIME_FORMAT = 'Y-m-d H:i:s';
  const INDEX_TABLE = '_index';
  const MAX_LIMIT = '18446744073709551615';

  protected $database;
  protected $url;
  protected $link;

  public function __construct($url) {
    $conn = array_merge(array(
      'host' => 'localhost',
      'port' => self::DEFAULT_PORT,
      'user' => null,
      'pass' => null,
      'path' => null
    ), parse_url($url));
    $this->database = ltrim($conn['path'], '/');
    if (!$this->link = new mysqli($conn['host'], $conn['user'], $conn['pass'], null, $conn['port'])) {
      throw new Exception('Cannot connect MySQL driver to ' . $conn['host']);
    }
    $this->link->set_charset('utf8');
    while (!$this->link->select_db($this->database)) {
      if (!$this->query("CREATE DATABASE `%s` DEFAULT CHARACTER SET 'utf8' "
        . "COLLATE 'utf8_unicode_ci'", $this->database)) {
        throw new Exception($this->link->error);
      }
    }
    if (!$this->tableExists('_index')) {
      $query = file_get_contents(__DIR__ . DS . 'MySQL' . DS
        . 'create-index-table.sql');
      $this->query($query);
    }
  }

  public function __destruct() {
    if ($this->link) {
      $this->link->close();
    }
  }

  public function store(&$model) {
    $class = get_class($model);
    $tables = $this->tablesFor($class);
    $this->link->autocommit(false);
    try {
      $key = $model->key();
      $attributes = $model->attributes();
      $this->denormalize($key, $class);
      $this->denormalize($attributes, $class);
      foreach ($tables as $i => $table) {
        $update = array();
        $values = array();
        $columns = $this->columns($table);
        $fields = array_intersect_key($attributes, array_flip($columns));
        list($main, $attribute) = explode('_', $table)
          + array(
            null, null
          );
        if ($attribute) {
          $update[] = "`{$attribute}` = VALUES(`{$attribute}`)";
          $attribute =  is_array($fields[$attribute]) ? $fields[$attribute] : array();
          foreach ($attribute as $k => $value) {
            $values[] = implode(', ', array_merge($key, array(
              $k, $value
            )));
          }
          $where = $this->normalizeSearch($class, array(
            $key
          ));
          $this->query("DELETE FROM `{$table}` WHERE $where AND `_` >= %d", count($attribute));
        }
        else {
          $values[] = implode(', ', $fields);
          array_walk($fields, function ($val, $key) use (&$update) {
            $update[] = "`{$key}` = VALUES(`{$key}`)";
          });
        }
        if ($values) {
          $query = "INSERT INTO `{$table}` (`" . implode('`, `', $columns)
            . "`) " . "VALUES (" . implode('), (', $values)
            . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update);
          $this->query($query);
        }
      }
    } catch (Exception $e) {
      $this->link->rollback();
      $this->link->autocommit(true);
      return Promise\When::reject($e);
    }
    $this->link->commit();
    $this->link->autocommit(true);
    return Promise\When::resolve($model);
  }

  public function delete(&$model) {
    $table = $this->tableName($model);
    $where = $this->normalizeSearch($class, array(
      $model->key()
    ));
    $query = "DELETE FROM `{$table}` WHERE {$where}";
    $this->query($query);
    return Promise\When::resolve();
  }

  public function count($class, $search = array(), $options = array()) {
    if (!$select = $this->select($class, $search, $options)) {
      return Promise\When::resolve(0);
    }
    return Promise\When::resolve(count($select));
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    return $this->fetch($class, $search, $options)->then('current');
  }

  public function fetch($class, $search = array(), $options = array()) {
    $models = array();
    try {
      if (!$select = $this->select($class, $search, $options)) {
        return Promise\When::reject();
      }
      $table = $this->tableName($class);
      foreach ($select as $result) {
        $where = $this->normalizeSearch($class, array(
          $result
        ));
        $projection = $this->projection($table);
        $query = "SELECT $projection FROM `{$table}` WHERE {$where}";
        foreach ($this->query($query) as $row) {
          $attributes = array();
          $properties = array_merge((array) $class::get("attributes"), $row);
          foreach ($properties as $attribute => $value) {
            if ($class::get("attributes.$attribute.multiple")) {
              $multiple = array();
              $where = $this->normalizeSearch($class, array(
                $result
              ));
              $_table = "{$table}_{$attribute}";
              $projection = $this->projectionFunction($attribute, $this->type($_table, $attribute));
              foreach ($this->query("SELECT `_`, {$projection} FROM `{$_table}` WHERE {$where}") as $r) {
                $multiple[$r['_']] = $r[$attribute];
              }
              $value = $multiple;
            }
            $attributes[$attribute] = $value;
          }
          $this->normalize($attributes, $class);
          $models[] = new $class($attributes);
        }
      }
    } catch (Exception $e) {
      return Promise\When::reject($e);
    }
    return empty($models) ? Promise\When::reject()
      : Promise\When::resolve($models);
  }

  public function purge($class, $search = array(), $options = array()) {
    $table = $this->tableName($class);
    $this->query("TRUNCATE TABLE `{$table}`");
    return Promise\When::resolve();
  }

  protected function projection($table) {
    $projection = array();
    $describe = $this->describe($table);
    foreach ($describe['fields'] as $field => $description) {
      $projection[] = $this->projectionFunction($field, $description['type']);
    }
    return implode(', ', $projection);
  }

  protected function projectionFunction($field, $type) {
    switch ($type) {
    case 'point':
    case 'polygon':
    case 'geometry':
      return "AsText(`$field`) AS `$field`";
    default:
      return "`$field`";
    }
  }

  protected function select($class, $search = array(), $options = array()) {
    $where = $this->normalizeSearch($class, array(
      $search
    ));
    $options = $this->options($options);
    $tables = $this->tablesFor($class);
    $table = array_shift($tables);
    $projection = $this->projection($table);
    $key = implode('`, `', (array) CM::get($class, 'keys'));
    $joins = $tables ? "LEFT JOIN `"
        . implode("` USING (`$key`) LEFT JOIN `", $tables) . "` USING (`$key`)"
      : '';
    $projection = implode("`, `$table`.`", CM::get($class, 'keys'));
    $query = "SELECT `{$table}`.`{$projection}` FROM `{$table}` {$joins} "
      . "WHERE {$where} GROUP BY `{$table}`.`{$projection}` {$options}";
    return $this->query($query);
  }

  protected function denormalize(&$document, $class) {
    foreach ($document as $field => &$value) {
      if ($class::get("attributes.$field.multiple") && is_array($value)) {
        foreach ($value as &$v) {
          $this->denormalizeValue($v, $field, $class);
        }
      }
      else {
        $this->denormalizeValue($value, $field, $class);
      }
    }
  }

  protected function denormalizeValue(&$value, $field, $class) {
    if (is_null($value)) {
      $value = 'NULL';
      return;
    }
    if ($value instanceof Bread\Model) {
      $value->store();
      $reference = new Database\Reference($value);
      $value = json_encode($reference);
    }
    elseif ($value instanceof DateTime) {
      $value = $value->format(self::DATETIME_FORMAT);
    }
    elseif (strcasecmp($class::get("attributes.$field.type"), 'point') == 0) {
      $value = "GEOMFROMTEXT('POINT(" . implode(" ", $value) . ")')";
      return;
    }
    elseif (strcasecmp($class::get("attributes.$field.type"), 'polygon') == 0) {
      $polygon = [];
      foreach ($value as $point) {
        $polygon[] = implode(" ", $point);
      }
      $polygon[] = $polygon[0];
      $value = "GEOMFROMTEXT('POLYGON((" . implode(",", $polygon) . "))')";
      return;
    }
    if (is_string($value)) {
      $value = "'" . $this->link->real_escape_string($value) . "'";
    }
    elseif (is_array($value)) {
      foreach ($value as &$v) {
        $this->denormalizeValue($v, $field, $class);
      }
    }
  }

  protected function normalize(&$document, $class) {
    foreach ($document as $field => &$value) {
      if (is_array($value)) {
        foreach ($value as &$v) {
          $this->normalizeValue($v, $field, $class);
        }
        continue;
      }
      elseif ($value) {
        $this->normalizeValue($value, $field, $class);
      }
    }
  }

  protected function normalizeValue(&$value, $field, $class) {
    switch ($class::get("attributes.$field.type")) {
    case 'number':
      $step = $class::get("attributes.$field.step");
      $value = (preg_match('/^any$|\./', $step)) ? (float) $value : (int) $value;
      break;
    case 'month':
    case 'week':
    case 'time':
    case 'date':
    case 'datetime':
    case 'datetime-local':
      $value = new DateTime($value);
      break;
    case 'point':
      preg_match('/POINT\((\S+) (\S+)\)/', $value, $matches);
      $value = array(
        (double) $matches[1], (double) $matches[2]
      );
      break;
    case 'polygon':
      preg_match('/^POLYGON\((.+)\)$/', $value, $matches);
      $value = array();
      foreach (preg_split('/\),\(/', $matches[1]) as $linestring) {
        foreach (preg_split('/,/', trim($linestring, '()')) as $point) {
          $value[] = array_map('floatval', preg_split('/ /', $point));
        }
      }
      break;
    }
    if (Database\Reference::is($value)) {
      Database\Reference::fetch($value)->then(function ($model) use (&$value) {
        $value = $model;
      });
    }
  }

  protected function normalizeSearch($class, $conditions = array(),
    $logic = '$and', $op = '=') {
    $where = array();
    foreach ($conditions as $search) {
      $w = array();
      foreach ($search as $attribute => $condition) {
        switch ($attribute) {
        case '$and':
        case '$or':
          $where[] = "("
            . $this->normalizeSearch($class, $condition, $attribute) . ")";
          continue 2;
        case '$nor':
          $where[] = "NOT ("
            . $this->normalizeSearch($class, $condition, '$or') . ")";
          continue 2;
        default:
          $explode = explode('.', $attribute);
          $attribute = array_shift($explode);
          if ($explode) {
            $type = $class::get("attributes.$attribute.type");
            $type::fetch(array(
              implode('.', $explode) => $condition
            ))->then(function ($models) use (&$condition) {
              $condition = array(
                '$in' => $models
              );
            });
          }
          if (is_array($condition)) {
            $c = array();
            foreach ($condition as $k => $v) {
              switch ($k) {
              case '$in':
                $this->denormalizeValue($v, $attribute, $class);
                $c[] = "`$attribute` IN (" . implode(" , ", $v) . ")";
                continue 2;
              case '$nin':
                $this->denormalizeValue($v, $attribute, $class);
                $c[] = "`$attribute` NOT IN (" . implode(" , ", $v) . ")";
                continue 2;
              case '$lt':
                $op = '<';
                break;
              case '$lte':
                $op = '<=';
                break;
              case '$gt':
                $op = '>';
                break;
              case '$gte':
                $op = '>=';
                break;
              case '$ne':
                $op = 'IS NOT';
                break;
              case '$regex':
                $op = 'REGEXP';
                $v = trim($v, $v[0]);
                break;
              case '$all':
                $all = array_map(function ($value) use ($attribute) {
                  return array(
                    $attribute => $value
                  );
                }, $v);
                $c[] = $this->normalizeSearch($class, $all, '$or');
                continue 2;
              case '$not':
                $not = array(
                  $attribute => $v
                );
                $c[] = "NOT "
                  . $this->normalizeSearch($class, array(
                    $not
                  ));
                continue 2;
              case '$maxDistance':
              case '$uniqueDocs':
                continue 2;
              case '$near':
                $maxDistance = $condition['$maxDistance'];
                $pointA = ($v[0] - $maxDistance) . " " . $v[1];
                $pointB = $v[0] . " " . ($v[1] - $maxDistance);
                $pointC = ($v[0] + $maxDistance) . " " . $v[1];
                $pointD = $v[0] . " " . ($v[1] + $maxDistance);
                $polygon = "($pointA,$pointB,$pointC,$pointD,$pointA)";
                $shape = "GeomFromText('Polygon($polygon)')";
                $c[] = "Within($attribute,$shape)";
                continue 2;
              case '$within':
                switch (key($v)) {
                case '$box':
                  $minx = $v[key($v)][0][0];
                  $miny = $v[key($v)][0][1];
                  $maxx = $v[key($v)][1][0];
                  $maxy = $v[key($v)][1][1];
                  $polygon = "($minx $miny,$maxx $miny,$maxx $maxy,$minx $maxy,$minx $miny)";
                  break;
                case '$center':
                  $r = $v[key($v)][1];
                  $pointA = ($v[key($v)][0][0] - $r) . " " . $v[key($v)][0][1];
                  $pointB = $v[key($v)][0][0] . " " . ($v[key($v)][0][1] - $r);
                  $pointC = ($v[key($v)][0][0] + $r) . " " . $v[key($v)][0][1];
                  $pointD = $v[key($v)][0][0] . " " . ($v[key($v)][0][1] + $r);
                  $polygon = "($pointA,$pointB,$pointC,$pointD,$pointA)";
                  break;
                case '$polygon':
                  foreach ($v[key($v)] as $i => $point) {
                    $v[key($v)][$i] = implode(" ", $point);
                  }
                  $v[key($v)][] = $v[key($v)][0];
                  $polygon = "(" . implode("  ", $v[key($v)]) . ")";
                  break;
                }
                $shape = "GeomFromText('Polygon($polygon)')";
                $c[] = "Within($attribute,$shape)";
                continue 2;
              }
              is_null($v) && $op = 'IS';
              $this->denormalizeValue($v, $attribute, $class);
              $c[] = "`$attribute` $op $v";
              $op = '=';
            }
            $w[] = "(" . implode(" AND ", $c) . ")";
            continue 2;
          }
          is_null($condition) && $op = 'IS';
          $this->denormalizeValue($condition, $attribute, $class);
          $w[] = "`$attribute` $op $condition";
          $op = '=';
        }
      }
      empty($w) || $where[] = implode(" AND ", $w);
    }
    switch ($logic) {
    case '$and':
      $logic = 'AND';
      break;
    case '$or':
      $logic = 'OR';
      break;
    }
    $where = implode(" $logic ", $where);
    return $where ? "$where" : '1';
  }

  protected function options($options = array()) {
    $return = array();
    foreach ($options as $option => $value) {
      switch ($option) {
      case 'sort':
        $sort = array();
        if (!is_array($value)) {
          $value = array(
            $value => 1
          );
        }
        foreach ($value as $k => $s) {
          if (is_numeric($k)) {
            $k = $s;
            $s = 1;
          }
          $k = $this->link->real_escape_string($k);
          if (is_array($s)) {
            $sort[] = "FIELD(`$k`, " . implode(', ', $s) . ")";
            continue;
          }
          $d = $s > 0 ? 'ASC' : 'DESC';
          $sort[] = "`$k` $d";
        }
        $return[0] = $sort ? "ORDER BY " . implode(', ', $sort) : '';
        break;
      case 'limit':
        if (!$value) {
          $value = self::MAX_LIMIT;
        }
        $return[1] = "LIMIT " . $this->link->real_escape_string($value);
        break;
      case 'skip':
        isset($options['limit']) || $options['limit'] = self::MAX_LIMIT;
        $return[2] = "OFFSET " . $this->link->real_escape_string($value);
        break;
      }
    }
    ksort($return);
    return implode(' ', $return);
  }

  protected function describe($table) {
    $keys = array(
      'primary' => array(), 'unique' => array(), 'indexes' => array()
    );
    $fields = array();
    foreach ($this->tables($table) as $table) {
      $describe = $this->query("DESCRIBE `%s`", $table);
      foreach ($describe as $field) {
        $fields[$field['Field']] = array(
          'type' => $field['Type'],
          'null' => $field['Null'] === 'YES' ? true : false,
          'default' => $field['Default'],
          'extra' => $field['Extra']
        );
        switch ($field['Key']) {
        case 'PRI':
          $keys['primary'][] = $field['Field'];
          break;
        case 'UNI':
          $keys['unique'][] = $field['Field'];
          break;
        case 'IND':
          $keys['indexes'][] = $field['Field'];
          break;
        }
      }
    }
    return array(
      'fields' => $fields, 'keys' => $keys
    );
  }

  protected function type($table, $column) {
    $describe = $this->describe($table);
    return $describe['fields'][$column]['type'];
  }

  protected function keys($table, $primaryOnly = true) {
    $describe = $this->describe($table);
    if ($primaryOnly) {
      return $describe['keys']['primary'];
    }
    return $describe;
  }

  protected function columns($table) {
    $columns = array();
    foreach ($this->query("SHOW COLUMNS FROM `%s`", $table) as $row) {
      $columns[] = array_shift($row);
    }
    return $columns;
  }

  protected function tables($like = '%', $where = array()) {
    $tables = array();
    foreach ($this->query("SHOW TABLES LIKE '%s'", $like) as $row) {
      $tables[] = array_shift($row);
    }
    return $tables;
  }

  protected function tablesFor($class) {
    $class = is_object($class) ? get_class($class) : $class;
    if (!$table = $this->tableName($class)) {
      $explode = explode(NS, $class);
      array_pop($explode);
      $tableize = array_pop($explode);
      $table = $_table = Inflector::tableize($tableize);
      $i = 1;
      while ($this->tableExists($_table)) {
        $i++;
        $_table = $table . "$i";
      }
      $table = $_table;
      $this->query("INSERT INTO `" . self::INDEX_TABLE
        . "` VALUES ('%s', '%s')", $class, $table);
    }
    if (!$this->tableExists($table)) {
      $key = implode('`, `', CM::get($class, 'keys'));
      $model = new $class();
      $attributes = $model->attributes();
      $queries = array();
      $query = "CREATE TABLE IF NOT EXISTS `$table` (\n\t";
      $multiples = array();
      foreach (array_keys($attributes) as $column) {
        if ($class::get("attributes.$column.multiple")) {
          $multiples[] = $column;
          continue;
        }
        $query .= $this->createQuery($class, $column);
      }
      $query .= "PRIMARY KEY (`$key`)";
      $query .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $queries[] = $query;
      foreach ($multiples as $column) {
        $query = "CREATE TABLE IF NOT EXISTS `{$table}_{$column}` (\n\t";
        foreach ((array) CM::get($class, 'keys') as $k) {
          $query .= $this->createQuery($class, $k);
        }
        $query .= "`_` INT unsigned NOT NULL DEFAULT 0,\n\t";
        $query .= $this->createQuery($class, $column);
        $query .= "PRIMARY KEY (`$key`, `_`),\n\t";
        $query .= "FOREIGN KEY (`$key`) REFERENCES `$table` (`$key`) "
          . "ON DELETE CASCADE ON UPDATE CASCADE\n";
        $query .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $queries[] = $query;
      }
      foreach ($queries as $query) {
        $this->query($query);
      }
    }
    return $this->tables("{$table}%");
  }

  protected function tableExists($table) {
    if ($table && $this->link->query("SHOW TABLES LIKE '$table'")->num_rows) {
      return $table;
    }
    return false;
  }

  protected function tableName($class) {
    $class = is_object($class) ? get_class($class) : $class;
    if ($tableName = CM::get($class, 'database.mysql.table')) {
      return $tableName;
    }
    $query = "SELECT * FROM `" . self::INDEX_TABLE . "` WHERE `class` = '%s'";
    $results = $this->query($query, $class);
    $result = array_shift($results);
    if (isset($result['table'])) {
      return $result['table'];
    }
    return null;
  }

  protected function className($table) {
    $query = "SELECT * FROM `" . self::INDEX_TABLE . "` WHERE `table` = '%s'";
    $results = $this->query($query, $table);
    $result = array_shift($results);
    if (isset($result['class'])) {
      return $result['class'];
    }
    return null;
  }

  protected function createType($class, $column, $options = array()) {
    extract(array_merge(array(
      'type' => null,
      'multiple' => false,
      'step' => 1,
      'min' => null,
      'null' => true,
      'default' => null
    ), $options));
    if (is_array($default)) {
      $default = implode(",", array_map('var_export', $default, array_fill(0, count($default), true)));
    }
    elseif ($default) {
      $default = var_export($this->denormalizeValue($default, $column, $class), true);
    }
    $default = is_null($default) ? ($null ? "DEFAULT NULL" : '')
      : "DEFAULT $default";
    $null = $null ? "" : "NOT NULL";
    if (is_array($type)) {
      $implode = implode(",", array_map('var_export', $type, array_fill(0, count($type), true)));
      return $multiple ? "`$column` SET($implode) $null $default,\n\t"
        : "`$column` ENUM($implode) $null $default,\n\t";
    }
    if ($type === 'number') {
      $sign = null;
      if (is_numeric($min)) {
        $sign = ($min < 0) ? null : 'unsigned';
      }
      $type = (preg_match('/^any$|\./', $step)) ? 'float' : 'int';
    }
    switch ($type) {
    case 'int':
      return "`$column` INT $sign $null $default,\n\t";
    case 'float':
      return "`$column` FLOAT $sign $null $default,\n\t";
    case 'text':
    case 'password':
      return "`$column` VARCHAR(128) $null $default,\n\t";
    case 'textarea':
      return "`$column` TEXT,\n\t";
    case 'date':
    case 'month':
    case 'week':
      return "`$column` DATE $null $default,\n\t";
    case 'time':
      return "`$column` TIME $null $default,\n\t";
    case 'datetime':
    case 'datetime-local':
      return "`$column` DATETIME $null $default,\n\t";
    case 'file':
      return "`$column` LONGBLOB $null $default,\n\t";
    case 'checkbox':
      return "`$column` TINYINT(1) $null $default,\n\t";
    case 'point':
      return "`$column` POINT $null $default,\n\t";
    case 'polygon':
      return "`$column` POLYGON $null $default,\n\t";
    }
    return "`$column` VARCHAR(128) $null $default,\n\t";
  }

  protected function createQuery($class, $attribute, $column = null,
    $null = null) {
    $options = array_merge(array(
      'type' => 'text',
      'multiple' => false,
      'step' => 1,
      'min' => null,
      'null' => true,
      'default' => null
    ), (array) $class::get("attributes.$attribute"));
    if (is_bool($null)) {
      $options['null'] = $null;
    }
    extract($options);
    if (is_subclass_of($type, 'Bread\Model')) {
      $options['type'] = 'text';
    }
    $column = $column ? : $attribute;
    return $this->createType($class, $column, $options);
  }

  protected function query() {
    $args = func_get_args();
    $query = array_shift($args);
    if (is_array(current($args))) {
      $args = array_shift($args);
    }
    foreach ($args as &$arg) {
      $arg = $this->link->real_escape_string($arg);
    }
    $query = vsprintf($query, $args);
    $result = $this->link->query($query);
    if (false === $result) {
      throw new Exception($this->link->error . ": '$query'");
    }
    elseif (is_bool($result)) {
      $cache = $result;
    }
    else {
      $rows = array();
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      $result->free();
      $cache = $rows;
    }
    return $cache;
  }
}
