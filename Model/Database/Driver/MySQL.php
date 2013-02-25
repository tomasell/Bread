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
use Bread\Model\Interfaces;
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
      $this->database, $conn['port'])) {
      throw new Exception('Cannot connect MySQL driver to ' . $conn['host']);
    }
    $this->link->set_charset('utf8');
    while (!$this->link->select_db($this->database)) {
      if (!$this->query("CREATE DATABASE `%s` DEFAULT CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'", $this->database)) {
        throw new Exception($this->link->error);
      }
    }
    //         if (!$this->tableExists('_index')) {
    //           $query = file_get_contents(__DIR__ . DS . 'MySQL' . DS
    //             . 'create-index-table.sql');
    //           $this->query($query);
    //         }
  }

  public function __destruct() {
    if ($this->link) {
      $this->link->close();
    }
  }

  protected function query() {
    $args = func_get_args();
    $query = array_shift($args);
    $rows = array();
    foreach ($args as &$arg) {
      $arg = $this->link->real_escape_string($arg);
    }
    $query = vsprintf($query, $args);
    $result = $this->link->query($query);
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

  public function count($class, $search = array(), $options = array()) {
    ;
  }

  public function store(Bread\Model $model) {
    ;
  }

  public function delete(Bread\Model $model) {
    ;
  }

  public function purge($class) {
    ;
  }

  public function first($class, $search = [], $options = []) {
    ;
  }

  public function fetch($class, $search = [], $options = []) {
    $models = array();
    $where = $this->normalizeSearch($class, array(
      $search
    ));
    $table = $class::$configuration['database']['options']['table'];
    $key = $class::$configuration['database']['options']['key'];
    $query = "SELECT `$key` FROM `$table` WHERE $where GROUP BY `$key`";
    foreach ($this->query($query) as $result) {
      $condition = $result[$key];
      $this->formatValue($condition);
      $models[] = new $class($this->_fetch($class::$attributes, $table, array(), null, null, false));
    }
    return $models;
  }

  protected function _fetch($properties, $table, $keys, $k, $v, $multiple = true,
    $cast = false) {
    $attributes = array();
    if ($k) {
      $keys[$k] = $v;
    }
    foreach ($keys as $field => $value) {
      $conditions[] = "`$field` = $value";
    }
    $condition = implode(' AND ', $conditions);
    $query = "SELECT * FROM `$table` WHERE $condition";
    var_dump($query);
    foreach ($this->query($query) as $i => $row) {
      $attributes[$i] = array();
      foreach ($properties as $attribute => $property) {
        if (!isset($property['multiple'])) {
          $property['multiple'] = false;
        }
        $property = (object) $property;
        if (isset($row[$attribute])) {
          $attributes[$i][$attribute] = $row[$attribute];
        }
        elseif (is_array($property->type)) {
          $attributes[$i][$attribute] = $this->_fetch($property->type, "{$table}_{$attribute}", $keys, substr($attribute, 0, -1), $i, $property->multiple, true);
        }
        elseif ($property->multiple) {
          $attributes[$i][$attribute] = array_map(function ($value) use ($attribute) {
            return $value[$attribute];
          }, $this->_fetch(array(
            $attribute => ['type' => $property->type]
          ), "{$table}_{$attribute}", $keys, substr($attribute, 0, -1), $i, true));
        }
        elseif (is_subclass_of($property->type, 'Bread\Model')) {
          $attributes[$i][$attribute] = $this->_fetch(array(
            '$ref' => ['type' => 'text'], '$id' => ['type' => 'text']
          ), "{$table}_{$attribute}", $keys, substr($attribute, 0, -1), $i, $property->multiple, true);
        }
      }
    }
    if ($cast) {
      $attributes = array_map(function ($array) {
        return (object) $array;
      }, $attributes);
    }
    return $multiple ? $attributes : array_shift($attributes);
  }

  protected function normalizeSearch($class, $conditions = [], $logic = '$and',
    $op = '=') {
    $where = [];
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
        //           $explode = explode('.', $attribute);
        //           $attribute = array_shift($explode);
        //           $type = $class::get("attributes.$attribute.type");
        //           if (is_array($type)) {
        //             // TODO type is array
        //           } elseif (is_subclass_of($type, 'Bread\Model')) {
        //             // TODO type is Model
        //           } elseif ($class::get("attributes.$attribute.multiple")) {
        //             // TODO fai il JOIN
        //           }
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
