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

namespace Bread\Stream;

class Through extends Composite {
  public function __construct() {
    $readable = new Readable();
    $writable = new Writable();
    parent::__construct($readable, $writable);
  }

  public function filter($data) {
    return $data;
  }

  public function write($data) {
    $this->readable->emit('data', array(
      $this->filter($data)
    ));
  }

  public function end($data = null) {
    if (null !== $data) {
      $this->readable->emit('data', array(
        $this->filter($data)
      ));
    }
    $this->writable->end($data);
  }
}
