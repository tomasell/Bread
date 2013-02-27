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

namespace Bread\Model\Database\Driver;

use Bread;
use Bread\Promise;
use Bread\Exception;
use Bread\Model\Interfaces;
use Bread\Model\Database;
use Bread\L10n\Inflector;
use mysqli;
use DateTime;

class MySQL implements Interfaces\Database {
  const DEFAULT_PORT = 3306;
  const DATETIME_FORMAT = 'Y-m-d H:i:s';
  const INDEX_TABLE = '_index';

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
    if (!$this->link = new mysqli($conn['host'], $conn['user'], $conn['pass'],
      null, $conn['port'])) {
      throw new Exception('Cannot connect MySQL driver to ' . $conn['host']);
    }
    $this->link->set_charset('utf8');
    while (!$this->link->select_db($this->database)) {
      if (!$this->query("CREATE DATABASE `%s` DEFAULT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'", $this->database)) {
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
    $rows = array();
    if (is_bool($result)) {
      $cache = $result;
    }
    else {
      while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
      }
      $result->free();
      $cache = $rows;
    }
    return $cache;
  }

  protected function normalize(&$document, $class) {
    foreach ($document as $field => &$value) {
      if (is_array($value)) {
        foreach ($value as &$v) {
          $this->normalizeValue($v, $field, $class);
        }
        continue;
      }
      elseif($value) {
        $this->normalizeValue($value, $field, $class);
      }
    }
  }
  
  protected function normalizeValue(&$value, $field, $class) {
    switch ($class::get("attributes.$field.type")) {
      case 'date':
      case 'datetime':
        $value = new DateTime($value);
        break;
    }
    if (Database\Reference::is($value)) {
      Database\Reference::fetch($value)->then(function ($model) use (&$value) {
        $value = $model;
      });
    }
  }

  protected function denormalize(&$document) {
    foreach ($document as &$field) {
      if ($field instanceof Bread\Model) {
        $field->store();
        $reference = new Database\Reference($field);
        $field = json_encode($reference);
      }
      elseif ($field instanceof Bread\Model\Attribute) {
        $field = $field->__toArray();
        $this->denormalize($field);
      }
      elseif ($field instanceof DateTime) {
        $field = $field->format(self::DATETIME_FORMAT);
      }
      elseif (is_array($field)) {
        $this->denormalize($field);
      }
    }
  }

  protected function placeholders(&$columns, $table) {
    $placeholders = array();
    foreach ($columns as $column => $value) {
      if (is_array($value)) {
        continue;
      }
      if (is_null($value) || trim($value) === '') {
        $placeholders[] = "NULL";
        unset($columns[$column]);
      }
      else {
        switch ($this->type($table, $column)) {
        case 'int':
          $placeholders[] = "%s";
          break;
        default:
          $placeholders[] = "'%s'";
        }
      }
    }
    return $placeholders;
  }

  public function store(Bread\Model &$model) {
    $fields = array();
    $class = get_class($model);
    $tables = $this->tablesFor($class);
    $this->link->autocommit(false);
    try {
      $fields = $model->attributes();
      $this->denormalize($fields);
      foreach ($tables as $table) {
        $key = array_map(function ($value) {
          return "_$value";
        }, $class::$key);
        $values = array_intersect_key($fields, array_flip($this->columns($table)));
        $columns = array_keys($values);
        $placeholders = $this->placeholders($values, $table);
        $queries = array();
        $update = array();
        foreach ($values as $column => &$value) {
          if (is_array($value)) {
            $_columns = array_merge($key, array(
              '_key'
            ), $columns);
            $__columns = array_flip($_columns);
            $_placeholders = $this->placeholders($__columns, $table);
            $_update[] = "`$column` = VALUES(`$column`)";
            $query = "INSERT INTO `$table` (`" . implode('`, `', $_columns)
              . "`) VALUES (" . implode(', ', $_placeholders)
              . ") ON DUPLICATE KEY UPDATE " . implode(', ', $_update);
            foreach ($value as $k => $v) {
              $this->query($query, array_merge($model->key(), array(
                $k, $v
              )));
            }
            $where = $this->normalizeSearch(array(
              $model->key()
            ));
            $query = "DELETE FROM `$table` WHERE $where AND `_key` >= %d";
            $this->query($query, count($value));
            continue 2;
          }
          $update[] = "`$column` = VALUES(`$column`)";
        }
        $query = "INSERT INTO `$table` (`" . implode('`, `', $columns)
          . "`) VALUES ";
        $query .= "(" . implode(', ', $placeholders)
          . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update);
        $this->query($query, $values);
      }
    } catch (Exception $e) {
      $this->link->rollback();
      $this->link->autocommit(true);
      return Promise\When::reject($e);
    }
    $this->link->commit();
    $this->link->autocommit(true);
    return $model;
    return Promise\When::resolve($model);
  }

  public function delete(Bread\Model $model) {
    ;
  }

  public function count($class, $search = array(), $options = array()) {
    ;
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    return $this->fetch($class, $search, $options)->then('current');
  }

  public function fetch($class, $search = array(), $options = array()) {
    $models = array();
    $where = $this->normalizeSearch(array(
      $search
    ));
    $table = $this->tableName($class);
    $key = implode('`, `', $class::$key);
    $query = "SELECT `$key` FROM `$table` WHERE $where GROUP BY `$key`";
    foreach ($this->query($query) as $result) {
      $where = $this->normalizeSearch(array(
        $result
      ));
      $query = "SELECT * FROM `$table` WHERE $where";
      foreach ($this->query($query) as $row) {
        $attributes = array();
        $properties = array_merge($class::get("attributes"), $row);
        foreach ($properties as $attribute => $value) {
          if ($class::get("attributes.$attribute.multiple")) {
            $multiple = array();
            $where = $this->normalizeSearch(array(
              array_combine(array_map(function ($value) {
                return "_$value";
              }, array_keys($result)), $result)
            ));
            foreach ($this->query("SELECT `_key`, `$attribute` FROM `{$table}_{$attribute}` WHERE $where") as $r) {
              $multiple[$r['_key']] = $r[$attribute];
            }
            $value = $multiple;
          }
          $attributes[$attribute] = $value;
        }
        $this->normalize($attributes, $class);
        $models[] = new $class($attributes);
      }
    }
    return Promise\When::resolve($models);
  }

  public function purge($class) {
    ;
  }

  protected function describe($table) {
    $keys = array(
      'primary' => array(), 'unique' => array(), 'indexes' => array()
    );
    $fields = array();
    foreach ($this->tables("$table%") as $table) {
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
    $key = implode('`, `', $class::$key);
    if (!$this->tableExists($table)) {
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
        foreach ($class::$key as $k) {
          $query .= $this->createQuery($class, $k, "_$k");
        }
        $_key = array_map(function ($key) {
          return "_$key";
        }, $class::$key);
        $_key = implode('`, `', $_key);
        $query .= "`_key` INT unsigned NOT NULL DEFAULT 0,\n\t";
        $query .= $this->createQuery($class, $column);
        $query .= "PRIMARY KEY (`$_key`, `_key`),\n\t";
        $query .= "FOREIGN KEY (`$_key`) REFERENCES `$table` (`$key`) ON DELETE CASCADE ON UPDATE CASCADE\n";
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
    return $this->createType($column, $options);
  }

  protected function createType($column, $options = array()) {
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
      $default = var_export($this->denormalizeValue($default, $column), true);
    }
    $default = is_null($default) ? ($null ? "DEFAULT NULL" : '')
      : "DEFAULT $default";
    $null = $null ? "" : "NOT NULL";
    if (is_array($type)) {
      $implode = implode(",", array_map('var_export', $type, array_fill(0, count($type), true)));
      return $multiple ? "`$column` SET($implode) $null $default,\n\t"
        : "`$column` ENUM($implode) $null $default,\n\t";
    }
    if ($column === 'id') {
      $default = 'AUTO_INCREMENT';
    }
    if ($type === 'number') {
      if (is_numeric($min)) {
        $min = ($min < 0) ? null : 'unsigned';
      }
      $type = (preg_match('/^any$|\./', $step)) ? 'float' : 'int';
    }
    switch ($type) {
    case 'int':
      return "`$column` INT $min $null $default,\n\t";
    case 'float':
      return "`$column` FLOAT $min $null $default,\n\t";
    case 'text':
      return "`$column` VARCHAR(128) $null $default,\n\t";
    case 'textarea':
      return "`$column` TEXT,\n\t";
    case 'date':
      return "`$column` DATE $null $default,\n\t";
    case 'time':
      return "`$column` TIME $null $default,\n\t";
    case 'datetime':
      return "`$column` DATETIME $null $default,\n\t";
    case 'file':
    case 'data':
    case 'blob':
      return "`$column` MEDIUMBLOB $null $default,\n\t";
    case 'boolean':
    case 'bool':
      return "`$column` TINYINT(1) $null $default,\n\t";
    }
    return "`$column` VARCHAR(128) $null $default,\n\t";
  }

  protected function normalizeSearch($conditions = [], $logic = '$and',
    $op = '=') {
    $where = [];
    foreach ($conditions as $search) {
      $w = array();
      foreach ($search as $attribute => $condition) {
        switch ($attribute) {
        case '$and':
        case '$or':
          $where[] = "(" . $this->normalizeSearch($condition, $attribute) . ")";
          continue 2;
        case '$nor':
          $where[] = "NOT (" . $this->normalizeSearch($condition, '$or') . ")";
          continue 2;
        default:
          if (is_array($condition)) {
            $c = array();
            foreach ($condition as $k => $v) {
              switch ($k) {
              case '$in':
                array_walk($v, array(
                  $this, 'formatValue'
                ));
                $c[] = "`$attribute` IN (" . implode(" , ", $v) . ")";
                continue 2;
              case '$nin':
                array_walk($v, array(
                  $this, 'formatValue'
                ));
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
              case '$all':
                $all = array_map(function ($value) use ($attribute) {
                  return array(
                    $attribute => $value
                  );
                }, $v);
                $c[] = $this->normalizeSearch($all, '$or');
                continue 2;
              case '$not':
                $not = array(
                  $attribute => $v
                );
                $c[] = "NOT "
                  . $this->normalizeSearch(array(
                    $not
                  ));
                continue 2;
              }
              $this->formatValue($v, $op);
              $c[] = "`$attribute` $op $v";
              $op = '=';
            }
            $w[] = "(" . implode(" AND ", $c) . ")";
            continue 2;
          }
          $this->formatValue($condition, $op);
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

  protected function formatValue(&$v, &$op = '=') {
    if ($v instanceof Bread\Model) {
      $v = json_encode(new Database\Reference($v));
    }
    if (is_string($v)) {
      $v = "'" . $this->link->real_escape_string($v) . "'";
    }
    elseif (is_null($v)) {
      $op = "IS";
      $v = "NULL";
    }
    elseif ($v instanceof DateTime) {
      $v = $v->format(static::DATETIME_FORMAT);
    }
  }
}
