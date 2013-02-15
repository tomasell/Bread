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

namespace Bread\View\Helpers\DOM\Selector\Node;
use Bread\View\Helpers\DOM\Selector\Interfaces;
use Bread\View\Helpers\DOM\Selector\XPathExpr;
use Exception as ParseException;

/**
 * AttribNode represents a "selector[namespace|attrib operator value]" node.
 *
 * This component is a port of the Python lxml library,
 * which is copyright Infrae and distributed under the BSD license.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AttribNode implements Interfaces\Node {
  protected $selector;
  protected $namespace;
  protected $attrib;
  protected $operator;
  protected $value;

  /**
   * Constructor.
   *
   * @param NodeInterface $selector  The XPath selector
   * @param string        $namespace The namespace
   * @param string        $attrib    The attribute
   * @param string        $operator  The operator
   * @param string        $value     The value
   */
  public function __construct($selector, $namespace, $attrib, $operator, $value) {
    $this->selector = $selector;
    $this->namespace = $namespace;
    $this->attrib = $attrib;
    $this->operator = $operator;
    $this->value = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    if ($this->operator == 'exists') {
      return sprintf('%s[%s[%s]]', __CLASS__, $this->selector, $this->formatAttrib());
    }

    return sprintf('%s[%s[%s %s %s]]', __CLASS__, $this->selector, $this->formatAttrib(), $this->operator, $this->value);
  }

  /**
   * {@inheritDoc}
   */
  public function toXpath() {
    $path = $this->selector->toXpath();
    $attrib = $this->xpathAttrib();
    $value = $this->value;
    if ($this->operator == 'exists') {
      $path->addCondition($attrib);
    }
    elseif ($this->operator == '=') {
      $path->addCondition(sprintf('%s = %s', $attrib, XPathExpr::xpathLiteral($value)));
    }
    elseif ($this->operator == '!=') {
      // FIXME: this seems like a weird hack...
      if ($value) {
        $path->addCondition(sprintf('not(%s) or %s != %s', $attrib, $attrib, XPathExpr::xpathLiteral($value)));
      }
      else {
        $path->addCondition(sprintf('%s != %s', $attrib, XPathExpr::xpathLiteral($value)));
      }
      // path.addCondition('%s != %s' % (attrib, xpathLiteral(value)))
    }
    elseif ($this->operator == '~=') {
      $path->addCondition(sprintf("contains(concat(' ', normalize-space(%s), ' '), %s)", $attrib, XPathExpr::xpathLiteral(' '
        . $value . ' ')));
    }
    elseif ($this->operator == '|=') {
      // Weird, but true...
      $path->addCondition(sprintf('%s = %s or starts-with(%s, %s)', $attrib, XPathExpr::xpathLiteral($value), $attrib, XPathExpr::xpathLiteral($value
        . '-')));
    }
    elseif ($this->operator == '^=') {
      $path->addCondition(sprintf('starts-with(%s, %s)', $attrib, XPathExpr::xpathLiteral($value)));
    }
    elseif ($this->operator == '$=') {
      // Oddly there is a starts-with in XPath 1.0, but not ends-with
      $path->addCondition(sprintf('substring(%s, string-length(%s)-%s) = %s', $attrib, $attrib, strlen($value)
        - 1, XPathExpr::xpathLiteral($value)));
    }
    elseif ($this->operator == '*=') {
      // FIXME: case sensitive?
      $path->addCondition(sprintf('contains(%s, %s)', $attrib, XPathExpr::xpathLiteral($value)));
    }
    else {
      throw new ParseException(sprintf('Unknown operator: %s', $this->operator));
    }

    return $path;
  }

  /**
   * Returns the XPath Attribute
   *
   * @return string The XPath attribute
   */
  protected function xpathAttrib() {
    // FIXME: if attrib is *?
    if ($this->namespace == '*') {
      return '@' . $this->attrib;
    }

    return sprintf('@%s:%s', $this->namespace, $this->attrib);
  }

  /**
   * Returns a formatted attribute
   *
   * @return string The formatted attribute
   */
  protected function formatAttrib() {
    if ($this->namespace == '*') {
      return $this->attrib;
    }

    return sprintf('%s|%s', $this->namespace, $this->attrib);
  }
}
