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
 * states.inc.php
 *
 * Sheepshead game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),

    // New Hand
    2 => array(
        "name" => "newHand",
        "description" => clienttranslate('Dealing Cards'),
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,
        "transitions" => array( "" => 10 )
    ),

    // Bid Round
    10 => array(
        "name" => "bid",
        "description" => clienttranslate('${actplayer} choosing to pick or pass'),
        "descriptionmyturn" => clienttranslate('${you} must Pick or Pass?'),
        "type" => "activeplayer",
        "possibleactions" => array( "pick", "pass" ),
        "transitions" => array( "pick" => 11, "pass" => 17 )
    ),
    11 => array(
        "name" => "checkForLoner",
        "description" => "",
        "type" => "game",
        "action" => "stCheckForLoner",
        "transitions" => array( "loner" => 12, "noLoner" => 15 )
    ),
    12 => array(
        "name" => "chooseLoner",
        "description" => clienttranslate('${actplayer} Picked and is exchanging cards'),
        "descriptionmyturn" => clienttranslate('${you} have the Jack of Diamonds. Go alone or choose another card for the partner.'),
        "type" => "activeplayer",
        "possibleactions" => array( "goAlone", "choosePartner" ),
        "transitions" => array( "goAlone" => 13, "choosePartner" => 14 )
    ),
    13 => array(
        "name" => "setLoner",
        "description" => "",
        "type" => "game",
        "action" => "stSetLoner",
        "transitions" => array( "" => 15 )
    ),
    14 => array(
        "name" => "choosePartnerCard",
        "description" => clienttranslate('${actplayer} Picked and is exchanging cards'),
        "descriptionmyturn" => clienttranslate('${you} must choose another card for the partner.'),
        "type" => "activeplayer",
        "possibleactions" => array( "pickPartnerCard" ),
        // TODO: ???  "args" => "argChoosePartnerCard",
        "transitions" => array( "pickPartnerCard" => 15 )
    ),
    15 => array(
        "name" => "exchangeCards",
        "description" => clienttranslate('${actplayer} Picked and is exchanging cards'),
        "descriptionmyturn" => clienttranslate('${you} must put 2 cards in the Blind (these will be added to your score at the end of the hand)'),
        "type" => "activeplayer",
        "possibleactions" => array( "exchangeCards" ),
        "transitions" => array( "exchangeCards" => 20 )
    ),
    16 => array(
        "name" => "setStuckBid",
        "description" => "",
        "type" => "game",
        "action" => "stSetStuckBid",
        "transitions" => array( "" => 11 )
    ),
    17 => array(
        "name" => "nextBidder",
        "description" => "",
        "type" => "game",
        "action" => "stNextBidder",
        "transitions" => array( "nextBidder" => 10, "stuck" => 16 )
    ),

    // Update table info for play
    20 => array(
        "name" => "updateBid",
        "description" => "",
        "type" => "game",
        "action" => "stUpdateBid",
        "transitions" => array( "" => 30 )
    ),

    // Play
    30 => array(
        "name" => "newTrick",
        "description" => "",
        "type" => "game",
        "action" => "stNewTrick",
        "transitions" => array( "" => 31 )
    ),
    31 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard" ),
        "transitions" => array( "playCard" => 32, "unplayable" => 31 )
    ), 
    32 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array( "nextPlayer" => 31, "nextTrick" => 30, "endHand" => 40 )
    ), 

    40 => array(
        "name" => "endHand",
        "description" => "",
        "type" => "game",
        "action" => "stEndHand",
        "transitions" => array( "nextHand" => 2, "endGame" => 99 )
    ),   

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



