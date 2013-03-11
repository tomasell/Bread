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

use Bread\Configuration\Manager as CM;
use Bread\Model\Database;
use Bread\Promise;
use ArrayObject;

class LDAP implements Database\Interfaces\Driver {
  const DEFAULT_PORT = 389;
  const DATETIME_FORMAT = 'U';
  const FILTER_ALL = '(objectClass=*)';

  protected $link;
  protected $base;

  public function __construct($url) {
    $conn = array_merge(array(
      'host' => 'localhost',
      'port' => self::DEFAULT_PORT
    ), parse_url($url));
    if (!$this->link = ldap_connect($conn['host'], $conn['port'])) {
      throw new Exception("Cannot connect to LDAP server {$conn['host']}");
    }
    ldap_set_option($this->link, LDAP_OPT_PROTOCOL_VERSION, 3);
    if (isset($conn['user']) && isset($conn['pass'])) {
      ldap_bind($this->link, $conn['user'], $conn['pass']);
    }
    $this->base = ltrim($conn['path'], '/');
  }

  public function __destruct() {
    ldap_close($this->link);
  }

  public function store(&$object) {
    $class = get_class($object);
    $dn = $this->dn($model);
    $entry = (array) $object;
    $this->denormalizeEntry($entry);
    if (ldap_search($this->link, $dn, self::FILTER_ALL)) {
      if (!ldap_modify($this->link, $dn, $entry)) {
        throw new Exception(ldap_error($this->link));
      }
    }
    else {
      if (!ldap_add($this->link, $dn, $entry)) {
        throw new Exception(ldap_error($this->link));
      }
    }
    return Promise\When::resolve($object);
  }

  public function delete(&$object) {
    $class = get_class($object);
    $dn = $this->dn($model);
    if (!ldap_delete($this->link, $dn)) {
      throw new Exception(ldap_error($this->link));
    }
    return Promise\When::resolve();
  }

  public function count($class, $search = array(), $options = array()) {
    return $this->search($class, $search, $options)->then(function ($search) {
      return ldap_count_entries($this->link, $search);
    });
  }

  public function first($class, $search = array(), $options = array()) {
    return $this->search($class, $search, $options)->then(function ($search) use (
      $class) {
      if (!$result = ldap_first_entry($this->link, $search)) {
        return null;
      }
      $entry = ldap_get_attributes($this->link, $result);
      return $this->normalizeEntry($class, $entry)->then(function ($attributes) use (
        $class) {
        return new $class($attributes);
      });
    });
  }

  public function fetch($class, $search = array(), $options = array()) {
    return $this->search($class, $search, $options)->then(function ($search) use (
      $class) {
      $normalized = array();
      if (!$result = ldap_first_entry($this->link, $search)) {
        return $normalized;
      }
      do {
        if ($entry = ldap_get_attributes($this->link, $result)) {
          $normalized[] = $this->normalizeEntry($class, $entry);
        }
      } while ($result = ldap_next_entry($this->link, $result));
      return Promise\When::all($normalized, function ($entries) use ($class) {
        $objects = array();
        foreach ($entries as $attributes) {
          $objects[] = new $class($attributes);
        }
        return new ArrayObject($objects);
      });
    });
  }

  public function purge($class, $search = array(), $options = array()) {
    return $this->search($class, $search, $options)->then(function ($search) use (
      $class) {
      if (!$result = ldap_first_entry($this->link, $search)) {
        return null;
      }
      do {
        if ($entry = ldap_get_attributes($this->link, $result)) {
          ldap_delete($this->link, $entry['dn']);
        }
      } while ($result = ldap_next_entry($this->link, $result));
    });
  }

  protected function search($class, $search = array(), $options = array()) {
    $filter = $this->denormalizeSearch($class, array($search));
    return $this->options(ldap_search($this->link, $this->base, $filter), $options);
  }

  protected function options($search, $options = array()) {
    foreach ((array) $options as $option => $value) {
      switch ($option) {
      case 'sort':
        foreach ($value as $k => $order) {
          ldap_sort($this->link, $search, $k);
        }
        break;
      }
    }
    return Promise\When::resolve($search);
  }

  protected function normalizeEntry($class, $entry) {
    $normalized = array();
    foreach ($entry as $attribute => $value) {
      if (!is_string($attribute) || !is_array($value)) {
        continue;
      }
      unset($value['count']);
      if (!CM::get($class, "attributes.$attribute.multiple")) {
        $value = array_shift($value);
      }
      $explode = explode(';', $attribute);
      $attribute = array_shift($explode);
      foreach ((array) $explode as $tag) {
        // TODO tagged attributes
      }
      $normalized[$attribute] = $value;
    }
    return Promise\When::resolve($normalized);
  }

  protected function denormalizeEntry(&$entry) {
  }

  protected function denormalizeSearch($class, $conditions, $logic = '$and',
    $op = '=', $prefix = '') {
    $where = array();
    foreach ($conditions as $search) {
      $w = array();
      foreach ($search as $attribute => $condition) {
        switch ($attribute) {
        case '$and':
        case '$or':
          $where[] = $this->denormalizeSearch($class, $condition, $attribute);
          continue 2;
        case '$nor':
          $where[] = "!(" . $this->denormalizeSearch($class, $condition, '$or')
            . ")";
          continue 2;
        default:
          if (is_array($condition)) {
            $c = array();
            foreach ($condition as $key => $value) {
              switch ($key) {
              case '$in':
                $this->denormalizeValue($value, $attribute, $class);
                $in = array();
                foreach ($value as $v) {
                  $in[] = array($attribute => $v);
                }
                $c[] = $this->denormalizeSearch($class, $in, '$or');
                continue 2;
              case '$nin':
                $this->denormalizeValue($value, $attribute, $class);
                $nin = array();
                foreach ($value as $v) {
                  $nin[] = array($attribute => $v);
                }
                $c[] = "!(" . $this->denormalizeSearch($class, $nin, '$or')
                  . ")";
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
                $prefix = '!';
                break;
              case '$all':
                $all = array_map(function ($value) use ($attribute) {
                  return array($attribute => $value);
                }, $value);
                $c[] = $this->normalizeSearch($class, $all, '$or');
                continue 2;
              case '$not':
                $not = array($attribute => $value);
                $c[] = "!(" . $this->normalizeSearch($class, array($not)) . ")";
                continue 2;
              case '$maxDistance':
              case '$uniqueDocs':
              case '$near':
              case '$within':
                continue 2;
              }
              is_null($value) && $op = 'IS';
              $this->denormalizeValue($value, $attribute, $class);
              $c[] = $prefix . $attribute . $op . $value;
              $op = '=';
            }
            switch (count($c)) {
            case 0:
              break;
            case 1:
              $w[] = array_shift($c);
              break;
            default:
              $w[] = "&(" . implode(")(", $c) . ")";
            }
            continue 2;
          }
          is_null($condition) && $op = 'IS';
          $this->denormalizeValue($condition, $attribute, $class);
          $w[] = $prefix . $attribute . $op . $condition;
          $op = '=';
        }
      }
      switch (count($w)) {
      case 0:
        break;
      case 1:
        $where[] = array_shift($w);
        break;
      default:
        $where[] = "&(" . implode(")(", $w) . ")";
      }
    }
    switch ($logic) {
    case '$not':
      $logic = '!';
      break;
    case '$and':
      $logic = '&';
      break;
    case '$or':
      $logic = '|';
      break;
    }
    switch (count($where)) {
    case 0:
      return self::FILTER_ALL;
    case 1:
      return array_shift($where);
    default:
      return "({$logic}(" . implode(")(", $where) . "))";
    }
  }

  protected function denormalizeValue() {
  }

  protected function dn($object) {
    $class = get_class($object);
    $dn = array();
    foreach (CM::get($class, 'keys') as $key) {
      $dn[] = "{$key}={$object->$key}";
    }
    $dn[] = $this->base;
    return implode(',', $dn);
  }

  protected function littleEndian($hex) {
    $result = '';
    for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2)
      $result .= substr($hex, $x, 2);
    return $result;
  }

  protected function binSIDtoText($binsid, $pop = true) {
    $hex_sid = bin2hex($binsid);
    $rev = hexdec(substr($hex_sid, 0, 2)); // Get revision-part of SID
    $subcount = hexdec(substr($hex_sid, 2, 2)); // Get count of sub-auth entries
    $auth = hexdec(substr($hex_sid, 4, 12)); // SECURITY_NT_AUTHORITY
    $result = "$rev-$auth";
    for ($x = 0; $x < $subcount; $x++) {
      $subauth[$x] = hexdec($this->littleEndian(substr($hex_sid, 16 + ($x * 8), 8))); // get all SECURITY_NT_AUTHORITY
      $result .= sprintf('-%s', $subauth[$x]);
    }
    $parts = explode('-', $result);
    return $pop ? array_pop($parts) : $result;
  }
}
