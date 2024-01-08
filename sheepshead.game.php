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
  * sheepshead.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Sheepshead extends Table
{
	function __construct( )
	{
        parent::__construct();
        self::initGameStateLabels( array( 
            "trickSuit" => 10,
            "picker" => 11,
            "partner" => 12, 
            "pickerAlone" => 13,
            "partnerCard" => 14,
            "startPlayer" => 15,
            "bids" => 16,
            ) 
        );

        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );  
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "sheepshead";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        
        // Create cards
        $cards = array ();
        foreach ( $this->suit as $suit_id => $suit ) {
            // spade, heart, diamond, club
            for ($value = 7; $value <= 14; $value ++) {
                //  7, 8, 9, 10, J, Q, K, A
                $cards [] = array ('type' => $suit_id,'type_arg' => $value,'nbr' => 1 );
            }
        }
        $this->cards->createCards( $cards, 'deck' );

        $players = self::loadPlayersBasicInfos();

        // Set current trick suit to zero (= no trick suit)
        self::setGameStateInitialValue( 'trickSuit', 0 );
        // Set picker and partner to zero (= no picker / partner player number)
        self::setGameStateInitialValue( 'picker', 0 );
        self::setGameStateInitialValue( 'partner', 0 );
        // Mark "loner" hand (picker has jack of diamonds and chooses loner)
        self::setGameStateInitialValue( 'pickerAlone', 0 );
        // Set default partner card: Jack of Diamods (4-1)*13 + (11-2)=
        self::setGameStateInitialValue( 'partnerCard', 48);
        // Set the starting player
        // TODO: make random
        self::setGameStateInitialValue( 'startPlayer', 0);
        // Set the starting player
        self::setGameStateInitialValue( 'bids', 0);

        // initialize 6 cards to each player
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(6, 'deck', $player_id);
        }

        // TODO: Initialize blind
        // $blindCards = $this->cards->pickCards(2, 'deck', 0)

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );
        
        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    function pick() {
        self::checkAction("pick");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id picks");
    }

    function pass() {
        self::checkAction("pass");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id passes");
    }

    function goAlone() {
        self::checkAction("goAlone");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id goes alone");
    }

    function choosePartner() {
        self::checkAction("choosePartner");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id choosing partner card");
    }

    function exchangeCard($card_id1, $card_id2) {
        self::checkAction("exchangeCard");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id exchanges $card_id1 and $card_id2");
    }

    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id plays $card_id");
    }
    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    function stNewHand() { 
        // Set current trick suit to zero (= no trick suit)
        self::setGameStateValue( 'trickSuit', 0 );
        // Set picker and partner to zero (= no picker / partner player number)
        self::setGameStateValue( 'picker', 0 );
        self::setGameStateValue( 'partner', 0 );
        // Mark "loner" hand (picker has jack of diamonds and chooses loner)
        self::setGameStateValue( 'pickerAlone', 0 );
        // Set default partner card: Jack of Diamods (4-1)*13 + (11-2)=
        self::setGameStateValue( 'partnerCard', 48);
        $last_start_player = self::getGameStateValue('startPlayer');
        $num_players = self::getPlayersNumber();
        $next_start_player = ($last_start_player % $num_players) + 1;
        self::setGameStateValue( 'startPlayer', $next_start_player);

        // Take back all cards (from any location => null) to deck
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');
        // Deal 6 cards to each player
        $players = self::loadPlayersBasicInfos();
        foreach ( $players as $player_id => $player ) {
            $cards = $this->cards->pickCards(6, 'deck', $player_id);
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
        }
        // TODO: Initialize blind
        // $blindCards = $this->cards->pickCards(2, 'deck', 0)
    
        $this->gamestate->nextState("");
    }

    function stCheckForLoner() {
        // TODO: if jack of diamonds -> "loner"
        // else -> "noLoner"
        $this->gamestate->nextState("noLoner");
    }

    function stSetLoner() {
        self::setGameStateValue( 'pickerAlone', 1 );
        $this->gamestate->nextState("");
    }

    function stSetStuckBid() {
        // placeholder for special stuck rules
        # TODO: self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
        $this->gamestate->nextState("");
    }
    
    function stNextBidder() {
        $num_bids = self::getGameStateValue('bids') + 1;
        $num_players = self::getPlayersNumber();
        if ($num_bids === $num_players) {
            $this->gamestate->nextState("stuck");
        } else {
            $this->gamestate->nextState("nextBidder");
        }        
    }

    function stUpdateGame() {
        // TODo: update game display
        $this->gamestate->nextState("");
    }

    function stNewTrick(){
        self::setGameStateValue('trickColor', 0);
        $this->gamestate->nextState("");
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        if ($this->cards->countCardInLocation('cardsontable') == 4) {
            // This is the end of the trick
            // Move all cards to "cardswon" of the given player
            $best_value_player_id = self::activeNextPlayer(); // TODO figure out winner of trick
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);
        
            if ($this->cards->countCardInLocation('hand') == 0) {
                // End of the hand
                $this->gamestate->nextState("endHand");
            } else {
                // End of the trick
                $this->gamestate->nextState("nextTrick");
            }
        } else {
            // Standard case (not the end of the trick)
            // => just active the next player
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    function stEndHand() {
        // TODO: check score / hand count
        $this->gamestate->nextState("nextHand");
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
