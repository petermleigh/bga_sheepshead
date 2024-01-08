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

$this->cardStrength = array(
  7 => 1,
  8 => 2,
  9 => 3,
  13 => 4,
  10 => 5,
  14 => 6,
);

$this->trumpStrength = array(
  44 => 11,  // 7-D
  45 => 12,  // 8-D
  46 => 13,  // 9-D
  50 => 14,  // K-D
  47 => 15,  // 10-D
  60 => 16,  // A-D
  48 => 17,  // J-D
  22 => 18,  // J-H
  9 => 19,   // J-S
  35 => 20, // J-C
  49 => 21, // Q-D
  23 => 22, // Q-H
  10 => 23, // Q-S
  36 => 24, // Q-C
);
