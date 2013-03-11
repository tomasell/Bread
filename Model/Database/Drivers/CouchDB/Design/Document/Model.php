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

namespace Bread\Model\Database\Driver\CouchDB\Design\Document;

use Bread\Model\Database\Driver\CouchDB\Document;

class Model extends Document\Model {
  public $language;
  public $views;

  public function __construct($name, $views = array(), $language = 'javascript') {
    $this->_id = "_design/{$name}";
    $this->language = $language;
    $this->views = $views;
  }

  public function view($name, $map, $reduce = null) {
    $this->views[$name]['map'] = $map;
    if ($reduce) {
      $this->views[$name]['reduce'] = $reduce;
    }
  }
}
