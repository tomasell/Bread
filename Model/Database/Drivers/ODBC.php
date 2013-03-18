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

CM::defaults('Bread\Model\Database\Drivers\ODBC', array(
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
));

class ODBC implements Database\Interfaces\Driver {
  const DATETIME_FORMAT = 'YmdHis';

  protected $scheme;
  protected $schema;
  protected $link;

  public function __construct($url) {
    $conn = array_merge(array(
      'scheme' => null,
      'host' => 'localhost',
      'port' => null,
      'user' => null,
      'pass' => null,
      'path' => null
    ), parse_url($url));
    try {
      $this->scheme = $conn['scheme'];
      $this->schema = ltrim($conn['path'], '/');
      $this->link = new PDO('odbc:' . $conn['host'], $conn['user'], $conn['pass']);
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
    return Promise\When::reject();
  }

  public function delete($object) {
    return Promise\When::reject();
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
      $table = $this->tableName($class);
      foreach ($select as $result) {
        $promises[] = $this->denormalizeSearch($class, array($result))->then(function (
          $where) use ($class, $table) {
          $projection = $this->projection($table);
          $query = "SELECT $projection FROM {$table} WHERE {$where}";
          foreach ($this->query($query) as $row) {
            $attributes = array();
            $properties = array_merge((array) CM::get($class, "attributes"), $row);
            foreach ($properties as $attribute => $value) {
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
    return Promise\When::reject();
  }

  protected function projection($table) {
    return '*';
  }

  protected function select($class, $search = array(), $options = array()) {
    return $this->denormalizeSearch($class, array($search))->then(function (
      $where) use ($class, $options) {
      $options = $this->options($options);
      $table = $this->tableName($class);
      $projection = $this->projection($table);
      $key = implode(', ', (array) CM::get($class, 'keys'));
      $projection = implode(", {$table}.", CM::get($class, 'keys'));
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
      switch ($this->type(CM::get($class, 'database.table'), $field)) {
      case 'varchar':
        $value = "'{$value}'";
        break;
      case 'date':
      case 'time':
      case 'timestamp':
        $value = $value->format(static::DATETIME_FORMAT);
        break;
      }
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
    $query = "SELECT "
      . implode(",", CM::get(__CLASS__, "{$this->scheme}.describe")) . " FROM "
      . CM::get(__CLASS__, "{$this->scheme}.table") . " WHERE "
      . CM::get(__CLASS__, "{$this->scheme}.schema")
      . " = '{$this->schema}' AND "
      . CM::get(__CLASS__, "{$this->scheme}.name") . " = '{$table}'";
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

  protected function tableName($class) {
    $class = is_object($class) ? get_class($class) : $class;
    if ($tableName = CM::get($class, 'database.table')) {
      return "{$this->schema}.{$tableName}";
    }
    return null;
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
      // TODO Async!
      $cache = $result;
    });
    return $cache;
  }
}

