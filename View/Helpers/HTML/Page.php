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

namespace Bread\View\Helpers\HTML;

use Bread\View\Helpers\DOM;

class Page extends DOM\Document {
  public function __construct() {
    parent::__construct('html');
    $this->head = $this->root->append('<head></head>');
    $this->charset = $this->head->append('<meta/>');
    $this->charset->attr('charset', 'utf-8');
    $this->title = $this->head->append('<title/>');
    $this->body = $this->root->append('<body/>');
  }

  public function save($node = null, $options = LIBXML_NOXMLDECL) {
    return $this->document->saveHTML($node);
    return parent::save($node, $options);
  }
}