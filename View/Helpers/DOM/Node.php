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

namespace Bread\View\Helpers\DOM;

use DOMNode, DOMNodeList;

class Node implements Interfaces\Node {
  protected $document;
  protected $nodes = array();

  public function __construct(Document $document, $name, $value = null,
    $attributes = array()) {
    $this->document = $document;
    if (is_string($name)) {
      $this->nodes = array($document->create($name, $value, $attributes));
    }
    elseif ($name instanceof DOMNode) {
      $this->nodes = array($name);
    }
    elseif ($name instanceof DOMNodeList) {
      foreach ($name as $node) {
        $this->nodes[] = $node;
      }
    }
    elseif ($name instanceof Node) {
      $this->nodes = $name->nodes;
    }
  }
}
