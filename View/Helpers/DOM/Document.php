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

use DOMImplementation;
use DOMDocument;
use DOMXPath;

class Document {
  protected $implementation;
  protected $doctype;
  protected $document;
  protected $xpath;
  protected $root;

  public function __construct($qualifiedName, $publicId = null, $systemId = null, $namespace = null) {
    $this->implementation = new DOMImplementation();
    $this->doctype = $this->implementation->createDocumentType($qualifiedName, $publicId, $systemId);
    $this->document = $this->implementation->createDocument($namespace, $qualifiedName, $this->doctype);
    $this->document->encoding = 'utf-8';
    $this->xpath = new DOMXPath($this->document);
  }

  public function __toString() {
    return $this->save();
  }

  public function save($node = null, $options = 0) {
    return $this->document->saveXML($node, $options);
  }
}
