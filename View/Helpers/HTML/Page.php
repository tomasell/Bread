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
use DOMXPath;

class Page extends DOM\Document {
  public function __construct($html = null) {
    parent::__construct('html');
    if ($html) {
      $this->load($html);
    }
    else {
      $this->root = new Node($this, $this->root);
      $this->head = $this->root->append('<head></head>');
      $this->charset = $this->head->append('<meta charset="utf-8"/>');
      $this->title = $this->head->append('<title></title>');
      $this->body = $this->root->append('<body></body>');
    }
  }
  
  public function __invoke($name) {
    return new Node($this, parent::__invoke($name));
  }

  public function load($filename) {
    libxml_use_internal_errors(true);
    $this->document->loadHTMLFile($filename);
    $this->xpath = new DOMXPath($this->document);
    $this->root = new Node($this, $this->document->documentElement);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
  }
  
  public function save($node = null, $options = LIBXML_NOXMLDECL) {
    return $this->document->saveHTML($node);
    return parent::save($node, $options);
  }
}