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

namespace Bread\View\Helpers\DOM\Interfaces;

use Iterator, Countable;

interface Node extends Iterator, Countable {
  /**
   * Insert content, specified by the parameter, after each element in the set
   * of matched elements.
   */
  public function after($content);

  /**
   * Insert content, specified by the parameter, to the end of each element in
   * the set of matched elements.
   */
  public function append($content);

  /**
   * Insert every element in the set of matched elements to the end of the
   * target.
   */
  public function appendTo($target);

  /**
   * Get the value of an attribute for the first element in the set of matched
   * elements or set one or more attributes for every matched element.
   */
  public function attr($attributes);

  /**
   * Insert content, specified by the parameter, before each element in the set
   * of matched elements.
   */
  public function before($content);

  /**
   * Create a deep copy of the set of matched elements.
   *
   * TODO Implement clone() in __call() and __clone()
   */
  public function __clone();

  /**
   * Remove the set of matched elements from the DOM.
   */
  public function detach();

  /**
   * Remove all child nodes of the set of matched elements from the DOM.
   *
   * TODO Implement empty() in __call()
   */
  public function __call($method, $arguments);

  /**
   * Insert every element in the set of matched elements after the target.
   */
  public function insertAfter($target);

  /**
   * Insert every element in the set of matched elements before the target.
   */
  public function insertBefore($target);

  /**
   * Insert content, specified by the parameter, to the beginning of each
   * element in the set of matched elements.
   */
  public function prepend($content);

  /**
   * Insert every element in the set of matched elements to the beginning of the
   * target.
   */
  public function prependTo($target);

  /**
   * Get the value of a property for the first element in the set of matched
   * elements or set one or more properties for every matched element.
   */
  public function prop($properties);

  /**
   * Remove the set of matched elements from the DOM.
   */
  public function remove();

  /**
   * Remove an attribute from each element in the set of matched elements.
   */
  public function removeAttr($attribute);

  /**
   * Remove a property for the set of matched elements.
   */
  public function removeProp($property);

  /**
   * Replace each target element with the set of matched elements.
   */
  public function replaceAll(Node $targets);

  /**
   * Replace each element in the set of matched elements with the provided new
   * content and return the set of elements that was removed.
   */
  public function replaceWith();

  /**
   * Get the combined text contents of each element in the set of matched
   * elements, including their descendants, or set the text contents of the
   * matched elements.
   */
  public function text();

  /**
   * Remove the parents of the set of matched elements from the DOM, leaving the
   * matched elements in their place.
   */
  public function unwrap();

  /**
   * Get the current value of the first element in the set of matched elements
   * or set the value of every matched element.
   */
  public function val();

  /**
   * Wrap an HTML structure around each element in the set of matched elements.
   */
  public function wrap();

  /**
   * Wrap an HTML structure around all elements in the set of matched elements.
   */
  public function wrapAll();

  /**
   * Wrap an HTML structure around the content of each element in the set of
   * matched elements.
   */
  public function wrapInner();
}
