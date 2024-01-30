<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sheepshead implementation : © Peter Leigh petermleigh@gmail.com
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
$this->token = array(
  'dealer' => array(
    'name' => clienttranslate('dealer'),
    'nametr' => self::_('dealer'),
    'token_id' => 2,
  ),
  'picker' => array(
    'name' => clienttranslate('picker'),
    'nametr' => self::_('picker'),
    'token_id' => 8,
  ),
  'revealedPartner' => array(
    'name' => clienttranslate('partner'),
    'nametr' => self::_('partner'),
    'token_id' => 7,
  ),
);

$this->suit = array(
  0 => array(
    'name' => clienttranslate('none'),
    'nametr' => self::_('none'),
    'uni' => ' ',
  ),
  1 => array(
    'name' => clienttranslate('spade'),
    'nametr' => self::_('spade'),
    'uni' => '♠',
  ),
  2 => array( 
    'name' => clienttranslate('heart'),
    'nametr' => self::_('heart'),
    'uni' => '♡',
  ),
  3 => array(
    'name' => clienttranslate('club'),
    'nametr' => self::_('club'),
    'uni' => '♣',
  ),
  4 => array(
    'name' => clienttranslate('diamond'),
    'nametr' => self::_('diamond'),
    'uni' => '♢',
  ),
);

$this->rank = array(
  0 => '',
  2 => '2',
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
  14 => clienttranslate('A'),
);

$this->points = array(
  10 => 10,
  11 => 2,
  12 => 3,
  13 => 4,
  14 => 11,
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
