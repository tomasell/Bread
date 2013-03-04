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

class Parser {
  var $symbols_ = array();
  var $terminals_ = array();
  var $productions_ = array();
  var $table = array();
  var $defaultActions = array();
  var $version = '0.3.12';
  var $debug = false;

  public function __construct() {

    $accept = 'accept';
    $end = 'end';

    //parser
    $this->symbols_ = json_decode(
      '{"error":2,"expressions":3,"e":4,"EOF":5,"?":6,":":7,"||":8,"&&":9,"<":10,"<=":11,">":12,">=":13,"!=":14,"==":15,"%":16,"(":17,")":18,"n":19,"NUMBER":20,"$accept":0,"$end":1}',
      true);
    $this->terminals_ = json_decode(
      '{"2":"error","5":"EOF","6":"?","7":":","8":"||","9":"&&","10":"<","11":"<=","12":">","13":">=","14":"!=","15":"==","16":"%","17":"(","18":")","19":"n","20":"NUMBER"}',
      true);
    $this->productions_ = json_decode(
      '[0,[3,2],[4,5],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,1],[4,1]]',
      true);
    $this->table = json_decode(
      '[{"3":1,"4":2,"17":[1,3],"19":[1,4],"20":[1,5]},{"1":[3]},{"5":[1,6],"6":[1,7],"8":[1,8],"9":[1,9],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16]},{"4":17,"17":[1,3],"19":[1,4],"20":[1,5]},{"5":[2,13],"6":[2,13],"7":[2,13],"8":[2,13],"9":[2,13],"10":[2,13],"11":[2,13],"12":[2,13],"13":[2,13],"14":[2,13],"15":[2,13],"16":[2,13],"18":[2,13]},{"5":[2,14],"6":[2,14],"7":[2,14],"8":[2,14],"9":[2,14],"10":[2,14],"11":[2,14],"12":[2,14],"13":[2,14],"14":[2,14],"15":[2,14],"16":[2,14],"18":[2,14]},{"1":[2,1]},{"4":18,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":19,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":20,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":21,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":22,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":23,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":24,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":25,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":26,"17":[1,3],"19":[1,4],"20":[1,5]},{"4":27,"17":[1,3],"19":[1,4],"20":[1,5]},{"6":[1,7],"8":[1,8],"9":[1,9],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16],"18":[1,28]},{"6":[1,7],"7":[1,29],"8":[1,8],"9":[1,9],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16]},{"5":[2,3],"6":[2,3],"7":[2,3],"8":[2,3],"9":[1,9],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16],"18":[2,3]},{"5":[2,4],"6":[2,4],"7":[2,4],"8":[2,4],"9":[2,4],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16],"18":[2,4]},{"5":[2,5],"6":[2,5],"7":[2,5],"8":[2,5],"9":[2,5],"10":[2,5],"11":[2,5],"12":[2,5],"13":[2,5],"14":[2,5],"15":[2,5],"16":[1,16],"18":[2,5]},{"5":[2,6],"6":[2,6],"7":[2,6],"8":[2,6],"9":[2,6],"10":[2,6],"11":[2,6],"12":[2,6],"13":[2,6],"14":[2,6],"15":[2,6],"16":[1,16],"18":[2,6]},{"5":[2,7],"6":[2,7],"7":[2,7],"8":[2,7],"9":[2,7],"10":[2,7],"11":[2,7],"12":[2,7],"13":[2,7],"14":[2,7],"15":[2,7],"16":[1,16],"18":[2,7]},{"5":[2,8],"6":[2,8],"7":[2,8],"8":[2,8],"9":[2,8],"10":[2,8],"11":[2,8],"12":[2,8],"13":[2,8],"14":[2,8],"15":[2,8],"16":[1,16],"18":[2,8]},{"5":[2,9],"6":[2,9],"7":[2,9],"8":[2,9],"9":[2,9],"10":[2,9],"11":[2,9],"12":[2,9],"13":[2,9],"14":[2,9],"15":[2,9],"16":[1,16],"18":[2,9]},{"5":[2,10],"6":[2,10],"7":[2,10],"8":[2,10],"9":[2,10],"10":[2,10],"11":[2,10],"12":[2,10],"13":[2,10],"14":[2,10],"15":[2,10],"16":[1,16],"18":[2,10]},{"5":[2,11],"6":[2,11],"7":[2,11],"8":[2,11],"9":[2,11],"10":[2,11],"11":[2,11],"12":[2,11],"13":[2,11],"14":[2,11],"15":[2,11],"16":[2,11],"18":[2,11]},{"5":[2,12],"6":[2,12],"7":[2,12],"8":[2,12],"9":[2,12],"10":[2,12],"11":[2,12],"12":[2,12],"13":[2,12],"14":[2,12],"15":[2,12],"16":[2,12],"18":[2,12]},{"4":30,"17":[1,3],"19":[1,4],"20":[1,5]},{"5":[2,2],"6":[1,7],"7":[2,2],"8":[1,8],"9":[1,9],"10":[1,10],"11":[1,11],"12":[1,12],"13":[1,13],"14":[1,14],"15":[1,15],"16":[1,16],"18":[2,2]}]',
      true);
    $this->defaultActions = json_decode('{"6":[2,1]}', true);
    
    $this->lexer = new Lexer();

    //lexer
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

  public function trace() {

  }

  public function parser_performAction(&$thisS, $yytext, $yyleng, $yylineno, $yystate,
    $S, $_S, $O) {

    switch ($yystate) {
    case 1:
      $json = sprintf('{"type":"GROUP","expr":%s}', json_encode($S[$O - 1]));
      return json_decode($json, true);
    case 2:
      $json = sprintf(
        '{"type":"TERNARY","expr":%s,"truthy":%s,"falsey":%s}',
        json_encode($S[$O - 4]), json_encode($S[$O - 2]), json_encode($S[$O]));
      break;
    case 3:
      $json = sprintf('{"type":"OR","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 4:
      $json = sprintf('{"type":"AND","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 5:
      $json = sprintf('{"type":"LT","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 6:
      $json = sprintf('{"type":"LTE","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 7:
      $json = sprintf('{"type":"GT","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 8:
      $json = sprintf('{"type":"GTE","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 9:
      $json = sprintf('{"type":"NEQ","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 10:
      $json = sprintf('{"type":"EQ","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 11:
      $json = sprintf('{"type":"MOD","left":%s,"right":%s}', json_encode($S[$O - 2]),
        json_encode($S[$O]));
      break;
    case 12:
      $json = sprintf('{"type":"GROUP","expr":%s}', json_encode($S[$O - 1]));
      break;
    case 13:
      $json = sprintf('{"type":"VAR"}');
      break;
    case 14:
      $json = sprintf('{"type":"NUM","val":%d}', (int) $yytext);
      break;
    }
    $thisS = json_decode($json, true);
  }

  public function lex() {
    $token = $this->lexer->lex(); // $end = 1
    $token = (isset($token) ? $token : 1);

    // if token isn't its numeric value, convert
    if (isset($this->symbols_[$token]))
      return $this->symbols_[$token];

    return $token;
  }

  public function parseError($str = "", $hash = array()) {
    throw new Exception($str);
  }

  public function parse($input) {
    $stack = array(
      0
    );
    $stackCount = 1;

    $vstack = array(
      null
    );
    $vstackCount = 1;
    // semantic value stack

    $lstack = array(
      $this->lexer->yyloc
    );
    $lstackCount = 1;
    //location stack

    $shifts = 0;
    $reductions = 0;
    $recovering = 0;
    $TERROR = 2;

    $this->lexer->setInput($input);

    $yyval = (object) array();
    $yyloc = $this->lexer->yyloc;
    $lstack[] = $yyloc;

    while (true) {
      // retreive state number from top of stack
      $state = $stack[$stackCount - 1];
      // use default actions if available
      if (isset($this->defaultActions[$state])) {
        $action = $this->defaultActions[$state];
      }
      else {
        if (empty($symbol) == true) {
          $symbol = $this->lex();
        }
        // read action for current state and first input
        if (isset($this->table[$state][$symbol])) {
          $action = $this->table[$state][$symbol];
        }
        else {
          $action = '';
        }
      }

      if (empty($action) == true) {
        if (!$recovering) {
          // Report error
          $expected = array();
          foreach ($this->table[$state] as $p => $item) {
            if (!empty($this->terminals_[$p]) && $p > 2) {
              $expected[] = $this->terminals_[$p];
            }
          }

          $errStr = "Parse error on line " . ($this->lexer->yylineno + 1) . ":\n"
            . $this->showPosition() . "\nExpecting " . implode(", ", $expected)
            . ", got '"
            . (isset($this->terminals_[$symbol]) ? $this->terminals_[$symbol]
              : 'NOTHING') . "'";

          $this
            ->parseError($errStr,
              array(
                "text" => $this->match,
                "token" => $symbol,
                "line" => $this->lexer->yylineno,
                "loc" => $yyloc,
                "expected" => $expected
              ));
        }

        // just recovered from another error
        if ($recovering == 3) {
          if ($symbol == $this->EOF) {
            $this->parseError(isset($errStr) ? $errStr : 'Parsing halted.');
          }

          // discard current lookahead and grab another
          $yyleng = $this->lexer->yyleng;
          $yytext = $this->lexer->yytext;
          $yylineno = $this->lexer->yylineno;
          $yyloc = $this->lexer->yyloc;
          $symbol = $this->lex();
        }

        // try to recover from error
        while (true) {
          // check for error recovery rule in this state
          if (isset($this->table[$state][$TERROR])) {
            break 2;
          }
          if ($state == 0) {
            $this->parseError(isset($errStr) ? $errStr : 'Parsing halted.');
          }

          array_slice($stack, 0, 2);
          $stackCount -= 2;

          array_slice($vstack, 0, 1);
          $vstackCount -= 1;

          $state = $stack[$stackCount - 1];
        }

        $preErrorSymbol = $symbol; // save the lookahead token
        $symbol = $TERROR; // insert generic error symbol as new lookahead
        $state = $stack[$stackCount - 1];
        if (isset($this->table[$state][$TERROR])) {
          $action = $this->table[$state][$TERROR];
        }
        $recovering = 3; // allow 3 real symbols to be shifted before reporting a new error
      }

      // this shouldn't happen, unless resolve defaults are off
      if (is_array($action[0])) {
        $this
          ->parseError(
            "Parse Error: multiple actions possible at state: " . $state
              . ", token: " . $symbol);
      }

      switch ($action[0]) {
      case 1:
      // shift
      //$this->shiftCount++;
        $stack[] = $symbol;
        $stackCount++;

        $vstack[] = $this->lexer->yytext;
        $vstackCount++;

        $lstack[] = $this->lexer->yyloc;
        $lstackCount++;

        $stack[] = $action[1]; // push state
        $stackCount++;

        $symbol = "";
        if (empty($preErrorSymbol)) { // normal execution/no error
          $yyleng = $this->lexer->yyleng;
          $yytext = $this->lexer->yytext;
          $yylineno = $this->lexer->yylineno;
          $yyloc = $this->lexer->yyloc;
          if ($recovering > 0)
            $recovering--;
        }
        else { // error just occurred, resume old lookahead f/ before error
          $symbol = $preErrorSymbol;
          $preErrorSymbol = "";
        }
        break;

      case 2:
      // reduce
        $len = $this->productions_[$action[1]][1];
        // perform semantic action
        $yyval->S = $vstack[$vstackCount - $len];// default to $S = $1
        // default location, uses first token for firsts, last for lasts
        $yyval->_S = array(
          "first_line" => $lstack[$lstackCount - (isset($len) ? $len : 1)]['first_line'],
          "last_line" => $lstack[$lstackCount - 1]['last_line'],
          "first_column" => $lstack[$lstackCount - (isset($len) ? $len : 1)]['first_column'],
          "last_column" => $lstack[$lstackCount - 1]['last_column']
        );

        $r = $this
          ->parser_performAction($yyval->S, $yytext, $yyleng, $yylineno,
            $action[1], $vstack, $lstack, $vstackCount - 1);

        if (isset($r)) {
          return $r;
        }

        // pop off stack
        if ($len > 0) {
          $stack = array_slice($stack, 0, -1 * $len * 2);
          $stackCount -= $len * 2;

          $vstack = array_slice($vstack, 0, -1 * $len);
          $vstackCount -= $len;

          $lstack = array_slice($lstack, 0, -1 * $len);
          $lstackCount -= $len;
        }

        $stack[] = $this->productions_[$action[1]][0]; // push nonterminal (reduce)
        $stackCount++;

        $vstack[] = $yyval->S;
        $vstackCount++;

        $lstack[] = $yyval->_S;
        $lstackCount++;

        // goto new state = table[STATE][NONTERMINAL]
        $newState = $this
          ->table[$stack[$stackCount - 2]][$stack[$stackCount - 1]];

        $stack[] = $newState;
        $stackCount++;

        break;

      case 3:
      // accept
        return true;
      }

    }
    return true;
  }
}
