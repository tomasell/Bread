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

namespace Bread\Model\Database\Driver\MySQL;

use Bread\Stream\Interfaces\Wrapper;
use mysqli;

class Stream implements Wrapper {
  private $link;
  private $table;
  private $field;
  private $where;
  private $position;

  public function stream_cast($as) {
  }

  public function stream_close() {
    $this->link->close();
  }

  public function stream_eof() {
    return ($this->position
      >= $this->query("SELECT LENGTH(`{$this->field}`) "
        . "AS `{$this->field}` FROM `{$this->table}` {$this->where}"));
  }

  public function stream_flush() {
  }

  public function stream_lock($operation) {
  }

  public function stream_metadata($path, $option, $var) {
  }

  public function stream_open($path, $mode, $options, &$opened_path) {
    $config = array_merge(array(
      'scheme' => 'mysql',
      'host' => 'localhost',
      'port' => 3306,
      'user' => null,
      'pass' => null,
      'path' => '/',
      'query' => null,
      'fragment' => 'utf-8'
    ), parse_url($path));
    list($database, $this->table, $this->field) = explode('.', ltrim($config['path'], '/'))
      + array(
        null, null, null
      );
    parse_str($config['query'], $query);
    $where = array();
    foreach ($query as $key => $value) {
      $where[] = "`$key` = '$value'";
    }
    $this->where = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    if (!$this->link = new mysqli($config['host'], $config['user'],
      $config['pass'], $database, $config['port'])) {
      return false;
    }
    $this->link->set_charset($config['fragment']);
    switch ($mode) {
    case 'r':
      $this->position = 0;
      break;
    case 'w':
      $this->position = $this->query("SELECT LENGTH(`{$this->field}`) "
        . "AS `{$this->field}` FROM `{$this->table}` {$this->where}");
      break;
    }
    return true;
  }

  public function stream_read($count) {
    $position = $this->position;
    $this->position += $count;
    return $this->query("SELECT SUBSTRING(`{$this->field}`, {$position} + 1, "
      . "{$count}) AS `{$this->field}` FROM `{$this->table}` {$this->where}");
  }

  public function stream_seek($offset, $whence = SEEK_SET) {
    $this->position = $offset;
  }

  public function stream_set_option($option, $arg1, $arg2) {
  }

  public function stream_stat() {
  }

  public function stream_tell() {
    return $this->position;
  }

  public function stream_truncate($new_size) {
    $this->query("UPDATE `{$this->table}` SET `{$this->field}` = "
      . "SUBSTRING(`{$this->field}`, 1, $new_size) {$this->where}");
  }

  public function stream_write($data) {
    $position = $this->position;
    $this->position += strlen($data);
    $data = $this->link->real_escape_string($data);
    $this->query("UPDATE `{$this->table}` SET `{$this->field}` = "
      . "IFNULL(CONCAT(SUBSTRING(`{$this->field}`, 1, {$position}), "
      . "'$data'), '$data')");
  }

  private function query($query) {
    $result = $this->link->query($query);
    if (!is_bool($result)) {
      $return = $result->fetch_assoc();
      $result->free();
      return $return[$this->field];
    }
  }
}
