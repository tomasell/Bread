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
use Bread\Caching\Cache;
use Bread\Dough\ClassLoader as CL;
use Bread\Configuration\Manager as CM;
use Bread\Promise;
use Bread\Model\Database;
use Bread\L10n\Inflector;
use Exception;
use DateTime;
use PDO, PDOStatement, PDOException;

class ODBC implements Database\Interfaces\Driver {
  const DATETIME_FORMAT = 'YmdHis';
  protected static $configuration = array(
    'debug' => array('read' => false),
    'revisions' => false,
    'type' => 'db2',
    'db2' => array(
      'table' => 'QSYS2.SYSCOLUMNS',
      'schema' => 'table_schema',
      'name' => 'table_name',
      'describe' => array(
        'field' => 'column_name',
        'type' => 'data_type',
        'length' => 'length',
        'null' => 'is_nullable'
      )
    )
  );

  protected $link;

  public function __construct($url) {
    $conn = array_merge(array(
      'host' => 'localhost',
      'port' => null,
      'user' => null,
      'pass' => null,
      'path' => null
    ), parse_url($url));
    try {
      $this->link = new PDO($conn['scheme'] . ':' . $conn['host'], $conn['user'], $conn['pass']);
      $this->link->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    } catch (PDOException $e) {
      throw new Exception('Cannot connect ODBC driver to ' . $conn['host']);
    }
  }

  public function __destruct() {
    if ($this->link) {
      unset($this->link);
    }
  }
  public function store($object) {

  }

  public function delete($object) {

  }

  public function count($class, $search = array(), $options = array()) {
    return $this->select($class, $search, $options)->then('count');
  }

  public function first($class, $search = array(), $options = array()) {
    $options['limit'] = 1;
    return $this->fetch($class, $search, $options)->then('current');
  }

  public function fetch($class, $search = array(), $options = array()) {
    return $this->select($class, $search, $options)->then(function ($select) use (
      $class) {
      $promises = array();
      $table = array_shift(CM::get($class, 'table'));
      foreach ($select as $result) {
        $promises[] = $this->denormalizeSearch($class, array($result))->then(function (
          $where) use ($class, $table) {
          $projection = $this->projection($table);
          $query = "SELECT $projection FROM {$table} WHERE {$where}";
          foreach ($this->query($query) as $row) {
            $attributes = array();
            $properties = array_merge((array) CM::get($class, "attributes"), $row);
            foreach ($properties as $attribute => $value) {
              if (CM::get($class, "attributes.$attribute.multiple")) {
                $multiple = array();
                $_table = "{$table}_{$attribute}";
                $projection = $this->projectionFunction($attribute, $this->type($_table, $attribute));
                foreach ($this->query("SELECT _, {$projection} FROM {$_table} WHERE {$where}") as $r) {
                  $multiple[$r['_']] = $r[$attribute];
                }
                $value = $multiple;
              }
              $attributes[$attribute] = $value;
            }
            return $this->normalize($attributes, $class)->then(function (
              $attributes) use ($class) {
              return new $class($attributes);
            });
          }
        });
      }
      return Promise\When::all($promises);
    });
  }

  public function purge($class, $search = array(), $options = array()) {
  }

  protected function projection($table) {
    //     $projection = array();
    //     $describe = $this->describe($table);
    //     foreach ($describe['fields'] as $field => $description) {
    //       $projection[] = $this->projectionFunction($field, $description['type']);
    //     }
    //     return implode(', ', $projection);
    return '*';
  }

  protected function select($class, $search = array(), $options = array()) {
    return $this->denormalizeSearch($class, array($search))->then(function (
      $where) use ($class, $options) {
      $options = $this->options($options);
      $tables = CM::get($class, 'table');
      $table = array_shift($tables);
      $projection = $this->projection($table);
      $key = implode(', ', (array) CM::get($class, 'keys'));
      $projection = implode(", $table.", CM::get($class, 'keys'));
      $query = "SELECT {$table}.{$projection} FROM {$table} "
        . "WHERE {$where} GROUP BY {$table}.{$projection} {$options}";
      return $this->query($query);
    });
  }
  protected function denormalizeValue(&$value, $field, $class) {
    if (is_null($value)) {
      $value = 'NULL';
      return;
    }
    if ($value instanceof DateTime) {
      $value = $value->format(self::DATETIME_FORMAT);
    }
    elseif (is_object($value)) {
      Database::driver(get_class($value))->store($value);
      $reference = new Database\Reference($value);
      $value = json_encode($reference);
    }
    if (is_string($value)) {
      $this->formatValue($value, $this->type(CM::get($class, 'table'), $field));
    }
    elseif (is_array($value)) {
      foreach ($value as &$v) {
        $this->denormalizeValue($v, $field, $class);
      }
    }
  }
  protected function normalize($document, $class) {
    $promises = array();
    foreach ($document as $field => &$value) {
      if (is_array($value)) {
        $promises[$field] = $this->normalize($value, $class);
        continue;
      }
      switch (CM::get($class, "attributes.$field.type")) {
      case 'number':
        $step = $class::get("attributes.$field.step");
        $value = (preg_match('/^any$|\./', $step)) ? (float) $value
          : (int) $value;
        break;
      case 'month':
      case 'week':
      case 'time':
      case 'date':
      case 'datetime':
      case 'datetime-local':
        $value = new DateTime($value);
        break;
      case 'text':
        break;
      }
      if (Database\Reference::is($value)) {
        $promises[$field] = Database\Reference::fetch($value);
      }
    }
    return Promise\When::all($promises, function ($values) use ($document) {
      foreach ($values as $field => $value) {
        $document[$field] = $value;
      }
      return $document;
    });
  }

  protected function denormalizeSearch($class, $conditions = array(),
    $logic = '$and', $op = '=') {
    $where = array();
    foreach ($conditions as $search) {
      $promises = array();
      foreach ($search as $attribute => $condition) {
        switch ($attribute) {
        case '$and':
        case '$or':
          $where[] = $this->denormalizeSearch($class, $condition, $attribute)->then(function (
            $where) {
            return "({$where})";
          });
          continue 2;
        case '$nor':
          $where[] = $this->denormalizeSearch($class, $condition, '$or')->then(function (
            $where) {
            return "NOT ({$where})";
          });
          continue 2;
        default:
          $explode = explode('.', $attribute);
          $attribute = array_shift($explode);
          if ($explode) {
            $type = CM::get($class, "attributes.$attribute.type");
            if (CL::classExists($type)) {
              $promises[$attribute] = Database::driver($type)->fetch($type, array(
                implode('.', $explode) => $condition
              ))->then(function ($models) {
                return array('$in' => $models);
              });
            }
          }
          else {
            $promises[$attribute] = Promise\When::resolve($condition);
          }
        }
      }
      $where[] = Promise\When::all($promises, function ($conditions) use (
        $class, $op) {
        $w = array();
        foreach ($conditions as $attribute => $condition) {
          if (is_array($condition)) {
            $c = array();
            foreach ($condition as $k => $v) {
              switch ($k) {
              case '$in':
                $this->denormalizeValue($v, $attribute, $class);
                $c[] = Promise\When::resolve("$attribute IN ("
                  . implode(", ", $v) . ")");
                continue 2;
              case '$nin':
                $this->denormalizeValue($v, $attribute, $class);
                $c[] = Promise\When::resolve("$attribute NOT IN ("
                  . implode(", ", $v) . ")");
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
                $op = '!=';
                break;
              case '$regex':
                $op = 'REGEXP';
                break;
              case '$all':
                $all = array_map(function ($value) use ($attribute) {
                  return array($attribute => $value);
                }, $v);
                $c[] = $this->denormalizeSearch($class, $all, '$or');
                continue 2;
              case '$not':
                $not = array($attribute => $v);
                $c[] = $this->denormalizeSearch($class, array($not))->then(function (
                  $c) {
                  return "NOT {$c}";
                });
                continue 2;
              case '$maxDistance':
              case '$uniqueDocs':
              case '$near':
              case '$within':
                continue 2;
              }
              is_null($v) && $op = 'IS';
              $this->denormalizeValue($v, $attribute, $class);
              $c[] = Promise\When::resolve("$attribute $op $v");
              $op = '=';
            }
            $w[] = Promise\When::all($c, function ($c) {
              return "(" . implode(" AND ", $c) . ")";
            });
            continue;
          }
          is_null($condition) && $op = 'IS';
          $this->denormalizeValue($condition, $attribute, $class);
          $w[] = Promise\When::resolve("$attribute $op $condition");
          $op = '=';
        }
        return Promise\When::all($w, function ($w) {
          return implode(" AND ", $w);
        });
      });
    }
    switch ($logic) {
    case '$and':
      $logic = 'AND';
      break;
    case '$or':
      $logic = 'OR';
      break;
    }
    return Promise\When::all($where, function ($where) use ($logic) {
      return implode(" $logic ", array_filter($where)) ? : '1 = 1';
    });
  }

  protected function options($options = array()) {

    $return = array();
    foreach ($options as $option => $value) {
      switch ($option) {
      case 'sort':
        $sort = array();
        if (!is_array($value)) {
          $value = array($value => 1);
        }
        foreach ($value as $k => $s) {
          if (is_numeric($k)) {
            $k = $s;
            $s = 1;
          }
          $k = addslashes($k);
          $d = $s > 0 ? 'ASC' : 'DESC';
          $sort[] = "$k $d";
        }
        $return[0] = $sort ? "ORDER BY " . implode(', ', $sort) : '';
        break;
      case 'limit':
        $return[1] = "FETCH FIRST $value ROWS ONLY";
        break;
      }
    }
    ksort($return);
    return implode(' ', $return);
  }

  protected function describe($table) {
    $keys = array(
      'primary' => array(),
      'unique' => array(),
      'indexes' => array()
    );
    $table = explode(".", array_shift($table));
    $query = "SELECT " . implode(",", self::$configuration['db2']['describe'])
      . " FROM " . self::$configuration['db2']['table'] . " WHERE "
      . self::$configuration['db2']['schema'] . " = '{$table[0]}' AND "
      . self::$configuration['db2']['name'] . " = '{$table[1]}'";
    $describe = $this->query($query);
    $fields = array();
    foreach ($describe as $field) {
      $field = array_map('strtolower', $field);
      $fields[$field['column_name']] = array(
        'type' => trim($field['data_type']),
        'null' => trim($field['is_nullable']) === 'N' ? true : false,
        'length' => trim($field['length'])
      );
    }
    return $fields;
  }

  protected function type($table, $column) {
    $describe = $this->describe($table);
    return $describe[$column]['type'];
  }

  protected function keys($object) {

    $class = get_class($object);
    $keys = array();
    foreach ((array) CM::get($class, 'keys') as $key) {
      $keys[$key] = $object->$key;
    }
    return $keys;
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
      return $multiple ? "$column SET($implode) $null $default,\n\t"
        : "$column ENUM($implode) $null $default,\n\t";
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
      return "$column INT $sign $null $default,\n\t";
    case 'float':
      return "$column FLOAT $sign $null $default,\n\t";
    case 'text':
    case 'password':
      return "$column VARCHAR(128) $null $default,\n\t";
    case 'textarea':
      return "$column TEXT,\n\t";
    case 'date':
    case 'month':
    case 'week':
      return "$column DATE $null $default,\n\t";
    case 'time':
      return "$column TIME $null $default,\n\t";
    case 'datetime':
    case 'datetime-local':
      return "$column DATETIME $null $default,\n\t";
    case 'file':
      return "$column LONGBLOB $null $default,\n\t";
    case 'checkbox':
      return "$column TINYINT(1) $null $default,\n\t";
    }
    return "$column VARCHAR(128) $null $default,\n\t";
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
    $query = vsprintf($query, $args);
    $cache = null;
    Cache::instance()->fetch($query)->then(null, function ($query) {
      echo "$query\n";
      $result = $this->link->query($query);
      if (false === $result) {
        throw new Exception($this->link->error . ": '$query'");
      }
      elseif (is_bool($result)) {
        $cache = $result;
      }
      else {
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);
        $cache = $rows;
      }
      Cache::instance()->store($query, $cache);
      return $cache;
    })->then(function ($result) use (&$cache) {
      $cache = $result;
    });
    return $cache;
  }

  protected function formatValue(&$v, $type) {
    switch ($type) {
    case 'varchar':
      $v = "'{$v}'";
      break;
    case 'date':
    case 'time':
    case 'timestamp':
      $v = $v->format(static::DATETIME_FORMAT);
      break;
    }
  }
}

