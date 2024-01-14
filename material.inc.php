<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sheepshead implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * Sheepshead game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->suit = array(
  1 => array( 'name' => clienttranslate('spade'),
              'nametr' => self::_('spade') ),
  2 => array( 'name' => clienttranslate('heart'),
              'nametr' => self::_('heart') ),
  3 => array( 'name' => clienttranslate('club'),
              'nametr' => self::_('club') ),
  4 => array( 'name' => clienttranslate('diamond'),
              'nametr' => self::_('diamond') )
);

$this->rank = array(
  2 =>'2',
  3 => '3',
  4 => '4',
  5 => '5',
  6 => '6',
  7 => '7',
  8 => '8',
  9 => '9',
  10 => '10',
  11 => clienttranslate('J'),
  12 => clienttranslate('Q'),
  13 => clienttranslate('K'),
  14 => clienttranslate('A')
);

$this->points = array(
  10 => 10,
  11 => 2,
  12 => 3,
  13 => 4,
  14 => 11
);

$this->cardUnicode = array(
  1 => array(  // spades
    7 => "🂧",
    8 => "🂨",
    9 => "🂩",
    10 => "🂪",
    11 => "🂫",
    12 => "🂭",
    13 => "🂮",
    14 => "🂡",
  ),
  2 => array(  // hearts
    7 => "🂷",
    8 => "🂸",
    9 => "🂹",
    10 => "🂺",
    11 => "🂻",
    12 => "🂽",
    13 => "🂾",
    14 => "🂱",
  ),
  3 => array(  // clubs
    7 => "🃗",
    8 => "🃘",
    9 => "🃙",
    10 => "🃚",
    11 => "🃛",
    12 => "🃝",
    13 => "🃞",
    14 => "🃑",
  ),
  4 => array(  // diamonds
    7 => "🃇",
    8 => "🃈",
    9 => "🃉",
    10 => "🃊",
    11 => "🃋",
    12 => "🃍",
    13 => "🃎",
    14 => "🃁",
  ),
);

$this->cardPower = array(
  1 => array(  // spades
    7 => 1,
    8 => 2,
    9 => 3,
    10 => 5,
    11 => 18,
    12 => 22,
    13 => 4,
    14 => 6,
  ),
  2 => array(  // hearts
    7 => 1,
    8 => 2,
    9 => 3,
    10 => 5,
    11 => 17,
    12 => 21,
    13 => 4,
    14 => 6,
  ),
  3 => array(  // clubs
    7 => 1,
    8 => 2,
    9 => 3,
    10 => 5,
    11 => 19,
    12 => 23,
    13 => 4,
    14 => 6,
  ),
  4 => array(  // diamonds
    7 => 10,
    8 => 11,
    9 => 12,
    10 => 14,
    11 => 16,
    12 => 20,
    13 => 13,
    14 => 15,
  ),
);
