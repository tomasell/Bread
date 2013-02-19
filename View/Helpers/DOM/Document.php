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

use DOMImplementation, DOMDocument, DOMXPath;

class Document {
  protected $implementation;
  protected $doctype;
  protected $document;
  protected $xpath;
  protected $root;

  public function __construct($qualifiedName, $publicId = null,
    $systemId = null, $namespace = null) {
    $this->implementation = new DOMImplementation();
    $this->doctype = $this->implementation->createDocumentType($qualifiedName, $publicId, $systemId);
    $this->document = $this->implementation->createDocument($namespace, $qualifiedName, $this->doctype);
    $this->document->encoding = 'utf-8';
    $this->xpath = new DOMXPath($this->document);
    $this->root = new Node($this, $this->document->documentElement);
  }

  public function __toString() {
    return $this->save();
  }

  public function create($name, $value = null, $attributes = array()) {
    $classes = explode('.', $name);
    $name = array_shift($classes);
    if (is_array($value)) {
      $attributes = $value;
      $value = null;
    }
    if (!empty($classes)) {
      if (isset($attributes['class'])) {
        $classes[] = $attributes['class'];
      }
      $attributes = array_merge($attributes, array(
        'class' => implode(" ", array_unique($classes))
      ));
    }
    list($name, $id) = explode('#', $name)
      + array(
        null, null
      );
    if ($id) {
      $attributes['id'] = $id;
    }
    $element = $this->document->createElement($name);
    foreach ($attributes as $name => $value) {
      $element->setAttribute($name, $value);
    }
    return $element;
  }
  
  public function save($node = null, $options = 0) {
    return $this->document->saveXML($node, $options);
  }
}
