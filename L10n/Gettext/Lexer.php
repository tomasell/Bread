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

namespace Bread\L10n\Gettext;

use Bread\Exception;

class Lexer {
  var $EOF = 1;
  var $S = "";
  var $yy = "";
  var $yylineno = "";
  var $yyleng = "";
  var $yytext = "";
  var $match = "";
  var $matched = "";
  var $yyloc = array(
    "first_line" => 1,
    "first_column" => 0,
    "last_line" => 1,
    "last_column" => 0,
    "range" => array()
  );
  var $conditionsStack = array();
  var $conditionStackCount = 0;
  var $rules = array();
  var $conditions = array();
  var $done = false;
  var $less;
  var $more;
  var $_input;
  var $options;
  var $offset;

  public function __construct() {
    $this->rules = array(
      "/^(?:\\s+)/",
      "/^(?:[0-9]+(\\.[0-9]+)?\\b)/",
      "/^(?:n\\b)/",
      "/^(?:\\|\\|)/",
      "/^(?:&&)/",
      "/^(?:\\?)/",
      "/^(?::)/",
      "/^(?:<=)/",
      "/^(?:>=)/",
      "/^(?:<)/",
      "/^(?:>)/",
      "/^(?:!=)/",
      "/^(?:==)/",
      "/^(?:%)/",
      "/^(?:\\()/",
      "/^(?:\\))/",
      "/^(?:$)/",
      "/^(?:.)/"
    );
    $this->conditions = json_decode(
      '{"INITIAL":{"rules":[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17],"inclusive":true}}',
      true);

    $this->options = json_decode('{}', true);
  }

  public function setInput($input) {
    $this->_input = $input;
    $this->more = $this->less = $this->done = false;
    $this->yylineno = $this->yyleng = 0;
    $this->yytext = $this->matched = $this->match = '';
    $this->conditionStack = array(
      'INITIAL'
    );
    $this->yyloc["first_line"] = 1;
    $this->yyloc["first_column"] = 0;
    $this->yyloc["last_line"] = 1;
    $this->yyloc["last_column"] = 0;
    if (isset($this->options->ranges)) {
      $this->yyloc['range'] = array(
        0, 0
      );
    }
    $this->offset = 0;
    return $this;
  }

  public function input() {
    $ch = $this->_input[0];
    $this->yytext .= $ch;
    $this->yyleng++;
    $this->offset++;
    $this->match .= $ch;
    $this->matched .= $ch;
    $lines = preg_match("/(?:\r\n?|\n).*/", $ch);
    if (count($lines) > 0) {
      $this->yylineno++;
      $this->yyloc['last_line']++;
    }
    else {
      $this->yyloc['last_column']++;
    }
    if (isset($this->options->ranges))
      $this->yyloc['range'][1]++;

    $this->_input = array_slice($this->_input, 1);
    return $ch;
  }

  public function unput($ch) {
    $len = strlen($ch);
    $lines = explode("/(?:\r\n?|\n)/", $ch);
    $linesCount = count($lines);

    $this->_input = $ch . $this->_input;
    $this->yytext = substr($this->yytext, 0, $len - 1);
    //$this->yylen -= $len;
    $this->offset -= $len;
    $oldLines = explode("/(?:\r\n?|\n)/", $this->match);
    $oldLinesCount = count($oldLines);
    $this->match = substr($this->match, 0, strlen($this->match) - 1);
    $this->matched = substr($this->matched, 0, strlen($this->matched) - 1);

    if (($linesCount - 1) > 0)
      $this->yylineno -= $linesCount - 1;
    $r = $this->yyloc['range'];
    $oldLinesLength = (isset($oldLines[$oldLinesCount - $linesCount]) ? strlen(
        $oldLines[$oldLinesCount - $linesCount]) : 0);

    $this->yyloc["first_line"] = $this->yyloc["first_line"];
    $this->yyloc["last_line"] = $this->yylineno + 1;
    $this->yyloc["first_column"] = $this->yyloc['first_column'];
    $this->yyloc["last_column"] = (empty($lines) ? ($linesCount
        == $oldLinesCount ? $this->yyloc['first_column'] : 0) + $oldLinesLength
      : $this->yyloc['first_column'] - $len);

    if (isset($this->options->ranges)) {
      $this->yyloc['range'] = array(
        $r[0], $r[0] + $this->yyleng - $len
      );
    }

    return $this;
  }

  public function more() {
    $this->more = true;
    return $this;
  }

  public function pastInput() {
    $past = substr($this->matched, 0,
      strlen($this->matched) - strlen($this->match));
    return (strlen($past) > 20 ? '...' : '')
      . preg_replace("/\n/", "", substr($past, -20));
  }

  public function upcomingInput() {
    $next = $this->match;
    if (strlen($next) < 20) {
      $next .= substr($this->_input, 0, 20 - strlen($next));
    }
    return preg_replace("/\n/", "",
      substr($next, 0, 20) . (strlen($next) > 20 ? '...' : ''));
  }

  public function showPosition() {
    $pre = $this->pastInput();

    $c = '';
    for ($i = 0, $preLength = strlen($pre); $i < $preLength; $i++) {
      $c .= '-';
    }

    return $pre . $this->upcomingInput() . "\n" . $c . "^";
  }

  public function next() {
    if ($this->done == true)
      return $this->EOF;

    if (empty($this->_input))
      $this->done = true;

    if ($this->more == false) {
      $this->yytext = '';
      $this->match = '';
    }

    $rules = $this->_currentRules();
    for ($i = 0, $j = count($rules); $i < $j; $i++) {
      preg_match($this->rules[$rules[$i]], $this->_input, $tempMatch);
      if ($tempMatch
        && (empty($match) || count($tempMatch[0]) > count($match[0]))) {
        $match = $tempMatch;
        $index = $i;
        if (isset($this->options->flex) && $this->options->flex == false)
          break;
      }
    }
    if ($match) {
      $matchCount = strlen($match[0]);
      $lineCount = preg_match("/\n.*/", $match[0], $lines);

      $this->yylineno += $lineCount;
      $this->yyloc["first_line"] = $this->yyloc['last_line'];
      $this->yyloc["last_line"] = $this->yylineno + 1;
      $this->yyloc["first_column"] = $this->yyloc['last_column'];
      $this->yyloc["last_column"] = $lines ? count($lines[$lineCount - 1]) - 1
        : $this->yyloc['last_column'] + $matchCount;

      $this->yytext .= $match[0];
      $this->match .= $match[0];
      $this->matches = $match;
      $this->yyleng = strlen($this->yytext);
      if (isset($this->options->ranges)) {
        $this->yyloc['range'] = array(
          $this->offset, $this->offset += $this->yyleng
        );
      }
      $this->more = false;
      $this->_input = substr($this->_input, $matchCount, strlen($this->_input));
      $this->matched .= $match[0];
      $token = $this
        ->performAction($this->yy, $this, $rules[$index],
          $this->conditionStack[$this->conditionStackCount]);

      if ($this->done == true && empty($this->_input) == false)
        $this->done = false;

      if (empty($token) == false) {
        return $token;
      }
      else {
        return;
      }
    }

    if (empty($this->_input)) {
      return $this->EOF;
    }
    else {
      $this
        ->parseError(
          "Lexical error on line " . ($this->yylineno + 1)
            . ". Unrecognized text.\n" . $this->showPosition(),
          array(
            "text" => "", "token" => null, "line" => $this->yylineno
          ));
    }
  }

  public function lex() {
    $r = $this->next();

    while (empty($r) && $this->done == false) {
      $r = $this->next();
    }

    return $r;
  }

  public function begin($condition) {
    $this->conditionStackCount++;
    $this->conditionStack[] = $condition;
  }

  public function popState() {
    $this->conditionStackCount--;
    return array_pop($this->conditionStack);
  }

  public function _currentRules() {
    return $this
      ->conditions[$this->conditionStack[$this->conditionStackCount]]['rules'];
  }

  public function performAction(&$yy, $yy_, $avoiding_name_collisions,
    $YY_START = null) {
    $YYSTATE = $YY_START;

    switch ($avoiding_name_collisions) {
    case 0:/* skip whitespace */
      break;
    case 1:
      return 20;
    case 2:
      return 19;
    case 3:
      return 8;
    case 4:
      return 9;
    case 5:
      return 6;
    case 6:
      return 7;
    case 7:
      return 11;
    case 8:
      return 13;
    case 9:
      return 10;
    case 10:
      return 12;
    case 11:
      return 14;
    case 12:
      return 15;
    case 13:
      return 16;
    case 14:
      return 17;
    case 15:
      return 18;
    case 16:
      return 5;
    case 17:
      return 'INVALID';
    }
  }
}
