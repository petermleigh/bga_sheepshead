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
            "hands" => 17
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
        // Set the starting player (BGA makes random start player)
        self::setGameStateInitialValue( 'startPlayer', 0);
        // Set the number of bids
        self::setGameStateInitialValue( 'bids', 0);
        // Set the number of hands
        self::setGameStateInitialValue( 'hands', 0);

        // TODO: Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        // self::initStat( 'table', 'table_handsPlayed', 0 );     
        // self::initStat( 'player', 'player_pointsEarned', 0 );
        // self::initStat( 'player', 'player_tricksTaken', 0 ); 
        // self::initStat( 'player', 'player_handsWon', 0 );    
        // self::initStat( 'player', 'player_numPick', 0 );     
        // self::initStat( 'player', 'player_numPartner', 0 );  
        // self::initStat( 'player', 'player_numStuckPick', 0 );

        $this->activeNextPlayer();
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
    
        $current_player_id = self::getCurrentPlayerId();
    
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

    function isTrump($card) {
        return ($card['type'] == 4 || $card['type_arg'] == 11 || $card['type_arg'] == 12);
    }

    function isEndOfGame($num_hands) {
        // end game after 3 rounds
        return ($num_hands >= 15);
    }

    function isPickingTeam($player_id) {
        $picker_id = self::getGameStateValue('picker');
        $partner_id = self::getGameStateValue('partner');
        return ($player_id == $picker_id || $player_id == $partner_id);
    }

    function isLonerHand() {
        $partner_id = self::getGameStateValue('partner');
        return ($partner_id == 0);
    }

    function isCardPlayable($card, $player_id) {
        $cards_in_hand = $this->cards->getCardsInLocation( 'hand', $player_id );
        $currentTrickSuit = self::getGameStateValue('trickSuit');
        // if played card is same suit as trick or no trick suit yet
        if ($currentTrickSuit == 0 || $this->getCardSuit($card) == $currentTrickSuit){
            return true;
        }
        // only allow non trick suit play if void in trick suit
        foreach ($cards_in_hand as $card) {
            if ($this->getCardSuit($card) == $currentTrickSuit) {
                return false;
            }
        }
        // all cards are not in trick suit so player can play whatever they want
        return true;
    }

    function getCardSuit($card) {
        if ($this->isTrump($card)) {
            return 4;  // All trump treated as a diamond
        }
        return $card['type'];
    }

    function getTrickWinner($cards_on_table) {        
        $best_value = 0;
        $best_value_player_id = null;
        $currentTrickSuit = self::getGameStateValue('trickSuit');
        foreach ( $cards_on_table as $card ) {
            // Note: type = card suit
            $card_rank = $card['type_arg'];
            $card_suit = $card['type'];
            $card_power = $this->cardPower[$card_suit][$card_rank];

            if ($card_suit == $currentTrickSuit || $this->isTrump($card)) {
                if ($best_value_player_id === null || $card_power > $best_value) {
                    $best_value_player_id = $card ['location_arg']; // location_arg = player who played this card on table
                    $best_value = $card_power;
                }
            }
        }
        return $best_value_player_id;
    }

    function calcHandPoints($players) {
        $player_hand_points = array ();
        foreach ( $players as $player_id => $player ) {
            $player_hand_points [$player_id] = 0;
        }

        $cards = $this->cards->getCardsInLocation("cardswon");
        foreach ( $cards as $card ) {
            $player_id = $card ['location_arg'];
            if (array_key_exists($card ['type_arg'], $this->points)) {
                $player_hand_points [$player_id] += $this->points [$card ['type_arg']];
            }
        }
        return $player_hand_points;
    }

    function calcPoints($player_hand_points) {    
        
        $picker_id = self::getGameStateValue('picker');
        $partner_id = self::getGameStateValue('partner');

        $picking_hand_points = 0;
        $opposing_hand_points = 0;
        $picker_points = 0;
        $partner_points = 0;
        $opposing_points = 0;

        foreach ($player_hand_points as $player_id => $hand_points) {
            if ($this->isPickingTeam($player_id)) {
                $picking_hand_points = $picking_hand_points + $hand_points;
            }     
            else {
                $opposing_hand_points = $opposing_hand_points + $hand_points;
            }
        }
        if ($picking_hand_points == 120) {
            $picker_points = 6;
            $partner_points = 3;
            $opposing_points = -3;
        }
        else if ($picking_hand_points >= 91) {
            $picker_points = 4;
            $partner_points = 2;
            $opposing_points = -2;
        }
        else if ($picking_hand_points >= 61) {
            $picker_points = 2;
            $partner_points = 1;
            $opposing_points = -1;
        }
        else if ($picking_hand_points >= 31) {
            $picker_points = -2;
            $partner_points = -1;
            $opposing_points = 1;
        }
        else if ($picking_hand_points >= 1) {
            $picker_points = -4;
            $partner_points = -2;
            $opposing_points = 2;
        }
        else if ($picking_hand_points == 0) {
            $picker_points = -9;
            $partner_points = 0;
            $opposing_points = 3;
        }

        if ($this->isLonerHand()) {
            $picker_points = $picker_points + $partner_points;
        }
        
        $player_points = array ();
        foreach ( $player_hand_points as $player_id => $player ) {
            if ($player_id == $picker_id) {
                $player_points [$player_id] = $picker_points;
            }     
            else if ($player_id == $partner_id) {
                $player_points [$player_id] = $partner_points;
            }     
            else {
                $player_points [$player_id] = $opposing_points;
            }
        }
        return $player_points;
    }

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
        self::notifyAllPlayers(
            'playerPassed', 
            clienttranslate('${player_name} passed'), 
            array (
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),

            )
        );
        // Next bidder
        $this->gamestate->nextState('pass');
    }

    function goAlone() {
        self::checkAction("goAlone");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id goes alone");
    }

    function choosePartner($card_id) {
        self::checkAction("choosePartner");
        $player_id = self::getActivePlayerId();
        throw new BgaUserException(self::_("Not implemented: ") . "$player_id choosing partner card");
    }

    function exchangeCard($card_id1, $card_id2) {
        self::checkAction("exchangeCard");
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id1, 'cardswon', $player_id);
        $this->cards->moveCard($card_id2, 'cardswon', $player_id);
    }

    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        $currentCard = $this->cards->getCard($card_id);
        if ( ! $this->isCardPlayable($currentCard, $player_id)) {
            $this->gamestate->nextState('unplayable');
            return;
        }
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);
        if (self::getGameStateValue('trickSuit') == 0) {
            self::setGameStateValue('trickSuit', $this->getCardSuit($currentCard));
        }
        // And notify
        self::notifyAllPlayers(
            'playCard', 
            clienttranslate('${player_name} plays ${value_displayed} of ${suit_displayed}'), 
            array (
                'i18n' => array ('suit_displayed','value_displayed' ),
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'value' => $currentCard ['type_arg'],
                'value_displayed' => $this->rank [$currentCard ['type_arg']],
                'suit' => $currentCard ['type'],
                'suit_displayed' => $this->suit [$currentCard ['type']] ['name'] 
            )
        );
        // Next player
        $this->gamestate->nextState('playCard');
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
        // reset bid number
        self::setGameStateValue( 'bids', 0);
        // set the start player
        $start_player_id = self::getGameStateValue('startPlayer');
        if ($start_player_id == 0) {
            $start_player_id = self::getActivePlayerId();
        }
        else {
            $start_player_id = self::getPlayerAfter( $start_player_id );
        }
        self::setGameStateValue( 'startPlayer', $start_player_id);
        $this->gamestate->changeActivePlayer($start_player_id);

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
    
        $this->gamestate->nextState("");
    }

    function stCheckForLoner() {
        $current_player_id = self::getCurrentPlayerId();
        $partner_card = self::getGameStateValue('partnerCard');
        // player picked, give them the blind
        $blind_cards = $this->cards->pickCards(2, 'deck', $current_player_id);
        // get all cards in player hand
        $cards = $this->cards->getCardsInLocation( 'hand', $current_player_id );
        self::notifyPlayer($current_player_id, 'newHand', '', array ('cards' => $cards ));
        foreach ($cards as $card_id => $card) {
            if ($card_id == $partner_card) {
                $this->gamestate->nextState("loner");
                return;
            }
        }
        $this->gamestate->nextState("noLoner");
    }

    function stSetLoner() {
        self::setGameStateValue( 'pickerAlone', 1 );
        $this->gamestate->nextState("");
    }

    function stSetStuckBid() {
        // placeholder for special stuck rules
        // TODO: self::notifyPlayer($player_id, 'newHand', '', array ('cards' => $cards ));
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
        self::setGameStateValue('bids', $num_bids);
    }

    function stUpdateBid() {
        $start_player_id = self::getGameStateValue('startPlayer');
        $second_player_id = self::getPlayerAfter( $start_player_id );
        self::setGameStateValue('picker', $start_player_id);
        self::setGameStateValue('partner', $second_player_id);

        // self::notifyAllPlayers( 'message', clienttranslate('hello'), [] );
        $this->gamestate->nextState("");
    }

    function stNewTrick(){
        self::setGameStateValue('trickSuit', 0);
        $this->gamestate->nextState("");
    }

    function stNextPlayer() {
        // Active next player OR end the trick and go to the next trick OR end the hand
        $num_players = self::getPlayersNumber();
        if ($this->cards->countCardInLocation('cardsontable') == $num_players) {
            // This is the end of the trick
            // Move all cards to "cardswon" of the given player
            $cards_on_table = $this->cards->getCardsInLocation('cardsontable');
            $best_value_player_id = $this->getTrickWinner($cards_on_table);
            
            // Active this player => he's the one who starts the next trick
            $this->gamestate->changeActivePlayer( $best_value_player_id );
            
            // Move all cards to "cardswon" of the given player
            $this->cards->moveAllCardsInLocation('cardsontable', 'cardswon', null, $best_value_player_id);

            // Notify
            $players = self::loadPlayersBasicInfos();
            self::notifyAllPlayers( 
                'trickWin', 
                clienttranslate('${player_name} wins the trick'), 
                array(
                    'player_id' => $best_value_player_id,
                    'player_name' => $players[ $best_value_player_id ]['player_name']
                ) 
            );            
            self::notifyAllPlayers( 
                'giveAllCardsToPlayer',
                '', 
                array(
                    'player_id' => $best_value_player_id
                ) 
            );
        
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
        // Count and score points, then end the game or go to the next hand.
        $players = self::loadPlayersBasicInfos();
        $player_hand_points = $this->calcHandPoints($players);
        $player_points = $this->calcPoints($player_hand_points);

        // Apply scores to player
        foreach ( $player_points as $player_id => $points ) {
            $sql = "UPDATE player SET player_score=player_score+$points  WHERE player_id='$player_id'";
            self::DbQuery($sql);
            self::notifyAllPlayers(
                "points", 
                clienttranslate('${player_name} gets ${nbr} points'), 
                array (
                    'player_id' => $player_id,
                    'player_name' => $players [$player_id] ['player_name'],
                    'nbr' => $points 
                )
            );
        }
        $new_scores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $new_scores ) );
        // display table of scores
        $table = array(
            array(
                clienttranslate("Player Name"), 
                clienttranslate("Points Taken"),
                clienttranslate("Team"),
                clienttranslate("Last Hand Score"), 
                clienttranslate("Current Score"),
            ) 
        );
        foreach ($player_hand_points as $player_id => $hand_points) {
            $team = clienttranslate("Opposing");
            if ($this->isPickingTeam($player_id)) {
                $team = clienttranslate("Picking");
            }
            $table[] = array(
                $players[$player_id]['player_name'],
                $hand_points,
                $team,
                $player_points[$player_id],
                $new_scores[$player_id],
            );
        }

        $this->notifyAllPlayers( "tableWindow", '', array(
            "id" => 'Scoring',
            "title" => clienttranslate("Hand Result"),
            "table" => $table
        )); 

        $num_hands = self::getGameStateValue('hands') + 1;
        $end_of_game = $this->isEndOfGame($num_hands);
        if ($end_of_game){
            $this->gamestate->nextState("endGame");
        }
        else{
            self::setGameStateValue('hands', $num_hands);
            $this->gamestate->nextState("nextHand");
        }
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
