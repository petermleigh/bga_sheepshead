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


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Sheepshead extends Table
{
	function __construct( )
	{
        parent::__construct();
        self::initGameStateLabels( array( 
            "dealer" => 15,
            "picker" => 11,
            "partner" => 12, 
            "partnerCard" => 14,
            "trickSuit" => 10,
            "bids" => 16,
            "hands" => 17,
            "defaultPartnerCard" => 18,
            "revealedPartner" => 19,
            // Game Variants
            "gameRounds" => 110,
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
        for ($suit = 1; $suit <= 4; $suit ++) {
            // spade, heart, diamond, club
            for ($value = 7; $value <= 14; $value ++) {
                //  7, 8, 9, 10, J, Q, K, A
                $cards [] = array ('type' => $suit,'type_arg' => $value,'nbr' => 1 );
            }
        }
        $this->cards->createCards( $cards, 'deck' );

        $players = self::loadPlayersBasicInfos();

        // Set current trick suit to zero (= no trick suit)
        self::setGameStateInitialValue( 'trickSuit', 0 );
        // Set picker and partner to zero (= no picker / partner player number)
        self::setGameStateInitialValue( 'picker', 0 );
        self::setGameStateInitialValue( 'partner', 0 );
        self::setGameStateInitialValue( 'revealedPartner', 0 );
        // Set default partner card: Jack of Diamods (4-1)*13 + (11-2)=
        self::setGameStateInitialValue( 'defaultPartnerCard', 48);
        self::setGameStateInitialValue( 'partnerCard', 48);
        // Set the dealer player (BGA makes random start player, will make that first player in startHand below)
        self::setGameStateInitialValue( 'dealer', 0);
        // Set the number of hands
        self::setGameStateInitialValue( 'hands', 0);
        // Initialize stats
        self::initStat( 'table', 'handsPlayed', 0 );     
        self::initStat( 'player', 'averagePointsEarned', 0.0 ); 
        self::initStat( 'player', 'averageTricksTaken', 0.0 );    
        self::initStat( 'player', 'averageNumberOfQueens', 0.0 );     
        self::initStat( 'player', 'averageNumberOfTrump', 0.0 );  
        self::initStat( 'player', 'totalHandsWon', 0 );
        self::initStat( 'player', 'numPick', 0 );
        self::initStat( 'player', 'numPartner', 0 );
        self::initStat( 'player', 'numStuckPick', 0 );
        self::initStat( 'player', 'numLoner', 0 );
        self::initStat( 'player', 'totalPointsEarned', 0 );
        self::initStat( 'player', 'totalTricksTaken', 0 );
        self::initStat( 'player', 'totalNumberOfQueens', 0 );
        self::initStat( 'player', 'totalNumberOfTrump', 0 );

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
        $partner_card_str = $this->getCardStr($this->getCardFromNo(self::getGameStateValue('partnerCard')));
        $trick_suit_str = $this->suit[self::getGameStateValue('trickSuit')]['uni'];
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );
        
        // Cards played on the table
        $result['cardsontable'] = $this->cards->getCardsInLocation( 'cardsontable' );

        // Partner Card
        $result['partnercardstr'] = $partner_card_str;

        // Current trick suit
        $result['tricksuitstr'] = $trick_suit_str;

        // Player tokens
        $result['playertokens'] = array(
            array(
                'player_id' => self::getGameStateValue('dealer'),
                'token_id' => $this->token['dealer']['token_id'],
                'token_name' => $this->token['dealer']['nametr'],
            ),
            array(
                'player_id' => self::getGameStateValue('picker'),
                'token_id' => $this->token['picker']['token_id'],
                'token_name' => $this->token['picker']['nametr'],
            ),
            array(
                'player_id' => self::getGameStateValue('revealedPartner'),
                'token_id' => $this->token['revealedPartner']['token_id'],
                'token_name' => $this->token['revealedPartner']['nametr'],
            ),
        );


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
        $num_rounds = self::getGameStateValue('gameRounds');
        $num_hands = self::getGameStateValue('hands');
        $num_players = self::getPlayersNumber();
        return ($num_hands / ($num_rounds * $num_players)) * 100;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function getCardFromNo($card_no) {
        return array(
            'type' => intdiv($card_no, 13) + 1,
            'type_arg' => ($card_no % 13) + 2,
        );
    }

    function getNofromCard($card) {
        return ($card['type'] - 1) * 13 + ($card['type_arg'] - 2);
    }

    function getCardStr($card) {
        return $this->rank[$card['type_arg']] . $this->suit[$card['type']]['uni'];
    }

    function isTrump($card) {
        return ($card['type'] == 4 || $card['type_arg'] == 11 || $card['type_arg'] == 12);
    }

    function isEndOfGame($num_hands) {
        $num_rounds = self::getGameStateValue('gameRounds');
        $num_players = self::getPlayersNumber();
        return ($num_hands >= $num_rounds * $num_players);
    }
    
    function getPartnerId() {
        $players = self::loadPlayersBasicInfos();
        $partner_card_no = self::getGameStateValue('partnerCard');
        $partner_card = $this->getCardFromNo($partner_card_no);
        foreach ( $players as $player_id => $player ) {
            $cards_in_hand = $this->cards->getCardsInLocation( 'hand', $player_id );
            foreach ($cards_in_hand as $card) {
                if ($card['type'] == $partner_card['type'] && $card['type_arg'] == $partner_card['type_arg']){
                    return $player_id;
                }
            }
        }
        return 0;
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
            if ($player_id == $picker_id || $player_id == $partner_id) {
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

        if ($partner_id == $picker_id) {
            if ($picker_points == -9){
                // Loner picker gets no points, -12 points, + 3 for all others
                $picker_points = -12;
            }
            else {
                // Loner picker 4x opossing team points (vs 2x with partner)
                $picker_points = $picker_points * 2;
            }
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
        // set the picker
        $picker_id = self::getActivePlayerId();
        self::setGameStateValue('picker', $picker_id);
        $this->gamestate->nextState('pick');
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
        $this->gamestate->nextState('pass');
    }

    function goAlone() {
        self::checkAction("goAlone");
        $this->gamestate->nextState('goAlone');
    }

    function choosePartner() {
        self::checkAction("choosePartner");
        $this->gamestate->nextState('choosePartner');
    }

    function choosePartnerCard($card_no) {
        self::checkAction("choosePartnerCard");              
        $partner_card_str = $this->getCardStr($this->getCardFromNo($card_no));
        $player_id = self::getActivePlayerId();
        self::notifyAllPlayers(
            'partnerCardChosen', 
            clienttranslate('${player_name} chose ${partner_card_str}'), 
            array (
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'partner_card_str' => $partner_card_str,
            )
        );
        if ($card_no == 0) {
            $this->gamestate->nextState('goAlone');
        }
        else {
            self::setGameStateValue('partnerCard', $card_no);
            $this->gamestate->nextState('choosePartnerCard');
        }
    }

    function exchangeCards($card_id1, $card_id2) {
        self::checkAction("exchangeCards");
        $player_id = self::getActivePlayerId();
        $this->cards->moveCard($card_id1, 'cardswon', $player_id);
        $this->cards->moveCard($card_id2, 'cardswon', $player_id);
        $this->gamestate->nextState('exchangeCards');
    }

    function playCard($card_id) {
        self::checkAction("playCard");
        $player_id = self::getActivePlayerId();
        $currentCard = $this->cards->getCard($card_id);
        if ( ! $this->isCardPlayable($currentCard, $player_id)) {
            $this->gamestate->nextState('unplayable');
            throw new BgaUserException(clienttranslate("Card not in suit"));
        }
        $this->cards->moveCard($card_id, 'cardsontable', $player_id);
        if (self::getGameStateValue('trickSuit') == 0) {
            self::setGameStateValue('trickSuit', $this->getCardSuit($currentCard));
        }
        self::notifyAllPlayers(
            'playCard', 
            '', 
            array(
                'i18n' => array('card_uni'),
                'card_id' => $card_id,
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'value' => $currentCard['type_arg'],
                'suit' => $currentCard['type'], 
                'card_str' => $this->getCardStr($currentCard),
                'trick_suit_str' => $this->suit[self::getGameStateValue('trickSuit')]['uni'],
            )
        );
        if ($this->getNofromCard($currentCard) == self::getGameStateValue('partnerCard')){
            self::setGameStateValue('revealedPartner', $player_id);
            self::notifyAllPlayers( 
                'moveTokens',
                '', 
                array(
                    'tokens' => array(
                        array('token_id' => $this->token['revealedPartner']['token_id'], 'token_name' => $this->token['revealedPartner']['nametr'], 'player_id' => $player_id),    
                    )
                ) 
            );  

        }
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

    function argChoosePartnerCard() {
        $player_id = self::getActivePlayerId();
        self::giveExtraTime($player_id);  
        $available_jacks = array(
            1 => array(
                'card_no' => $this->getNofromCard(array('type' => 1, 'type_arg' => 11)), 
                'card_str' => $this->getCardStr(array('type' => 1, 'type_arg' => 11)) 
            ),
            2 => array(
                'card_no' => $this->getNofromCard(array('type' => 2, 'type_arg' => 11)), 
                'card_str' => $this->getCardStr(array('type' => 2, 'type_arg' => 11)) 
            ),  
            3 => array(
                'card_no' => $this->getNofromCard(array('type' => 3, 'type_arg' => 11)), 
                'card_str' => $this->getCardStr(array('type' => 3, 'type_arg' => 11))
            ),  
        );
        $cards_in_hand = $this->cards->getCardsInLocation( 'hand', $player_id );
        foreach ($cards_in_hand as $card) {
            if ($card['type_arg'] == 11) {
                unset($available_jacks[$card['type']]);
            }
        }
        return array_values($available_jacks);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    function stNewHand() { 
        // Set current trick suit to zero (= no trick suit)
        self::setGameStateValue( 'trickSuit', 0 );
        // Set picker and partner to zero (= no picker / partner player number)
        self::setGameStateValue( 'picker', 0 );
        self::setGameStateValue( 'partner', 0 );
        self::setGameStateValue('revealedPartner', 0);
        // Set default partner card: Jack of Diamods (4-1)*13 + (11-2)=
        $partner_card = self::getGameStateValue('defaultPartnerCard');
        self::setGameStateValue( 'partnerCard', $partner_card);
        // set the start player
        $dealer_id = self::getGameStateValue('dealer');
        if ($dealer_id == 0) {
            $dealer_id = self::getActivePlayerId();
        }
        else {
            $dealer_id = self::getPlayerAfter($dealer_id);
        }
        self::setGameStateValue('dealer', $dealer_id);
        // start to the left of the dealer
        $this->gamestate->changeActivePlayer(self::getPlayerAfter($dealer_id));

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
        self::notifyAllPlayers( 
            'moveTokens',
            '', 
            array(
                'tokens' => array(
                    array('token_id' => $this->token['dealer']['token_id'], 'token_name' => $this->token['dealer']['nametr'], 'player_id' => $dealer_id),
                    array('token_id' => $this->token['picker']['token_id'], 'token_name' => $this->token['picker']['nametr'], 'player_id' => 0),
                    array('token_id' => $this->token['revealedPartner']['token_id'], 'token_name' => $this->token['revealedPartner']['nametr'], 'player_id' => 0),

                )
            ) 
        );    
        $this->gamestate->nextState("");
    }

    function stCheckForLoner() {
        $current_player_id = self::getActivePlayerId();
        $partner_card = self::getGameStateValue('partnerCard');
        // player picked, give them the blind
        $blind_cards = $this->cards->pickCards(2, 'deck', $current_player_id);
        // get all cards in player hand
        $cards = $this->cards->getCardsInLocation('hand', $current_player_id);
        self::notifyPlayer($current_player_id, 'newHand', '', array ('cards' => $cards ));
        foreach ($cards as $card) {
            $card_no = $this->getNofromCard($card);
            if ($card_no == $partner_card) {
                $this->gamestate->nextState("loner");
                return;
            }
        }
        $this->gamestate->nextState("noLoner");
    }

    function stSetLoner() {
        // placeholder for special loner rules        
        $this->gamestate->nextState("");
    }

    function stSetStuckBid() {
        // placeholder for special stuck rules 
        // set the picker
        $picker_id = self::getActivePlayerId();        
        self::setGameStateValue('picker', $picker_id); 
        self::giveExtraTime($picker_id);  
        $players = self::loadPlayersBasicInfos();         
        self::notifyAllPlayers( 
            'message',
            clienttranslate('${player_name} is stuck and must pick'), 
            array(
                'player_id' => $picker_id,
                'player_name' => $players[$picker_id]['player_name']
            ) 
        );
        $this->gamestate->nextState("");
    }
    
    function stNextBidder() {
        $player_id = self::activeNextPlayer();
        self::giveExtraTime($player_id);
        $dealer_id = self::getGameStateValue('dealer');
        if ($player_id == $dealer_id) {
            $this->gamestate->nextState("stuck");
        } else {
            $this->gamestate->nextState("nextBidder");
        }        
    }

    function stUpdateBid() {
        $dealer_id = self::getGameStateValue('dealer');
        $picker_id = self::getGameStateValue('picker');
        // determine the partner        
        $partner_id = $this->getPartnerId();
        self::setGameStateValue('partner', $partner_id);
        // start to the left of the dealer
        $leader_id = self::getPlayerAfter($dealer_id);
        $this->gamestate->changeActivePlayer($leader_id);
        // Notify the partner        
        self::notifyPlayer(
            $partner_id, 
            'message', 
            clienttranslate('You are the Partner for this hand'),
            array(
                'player_id' => $partner_id,
            )
        );
        self::notifyAllPlayers( 
            'moveTokens',
            '', 
            array(
                'tokens' => array(
                    array('token_id' => $this->token['picker']['token_id'], 'token_name' => $this->token['picker']['nametr'], 'player_id' => $picker_id),
                )
            ) 
        );   
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $num_queens = 0;
            $num_trump = 0;
            foreach ($this->cards->getCardsInLocation( 'hand', $player_id ) as $card) {
                if ($this->isTrump($card)) {
                    $num_trump += 1;
                }
                if ($card['type_arg'] == 12){
                    $num_queens += 1;
                }
            }
            self::incStat($num_queens, 'averageNumberOfQueens', $player_id);     
            self::incStat($num_trump, 'averageNumberOfTrump', $player_id); 
            if ($player_id == $picker_id) {
                self::incStat(1, 'numPick', $player_id);
                if ($player_id == $dealer_id) {
                    self::incStat(1, 'numStuckPick', $player_id);
                }
                if ($player_id == $partner_id) {
                    self::incStat(1, 'numLoner', $player_id);
                }
            }
            else if ($player_id == $partner_id) {
                self::incStat(1, 'numPartner', $player_id);
            }
        }

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
        }
        $new_scores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        self::notifyAllPlayers( "newScores", '', array( 'newScores' => $new_scores ) );
        // display table of scores
        $picker_id = self::getGameStateValue('picker');
        $partner_id = self::getGameStateValue('partner');
        $table = array(
            array(
                clienttranslate("Player Name"), 
                clienttranslate("Points Taken"),
                clienttranslate("Team"),
                clienttranslate("Last Hand Score"), 
                clienttranslate("Current Score"),
            ) 
        );
        $winning_team = "Picking";
        foreach ($player_hand_points as $player_id => $hand_points) {
            if ($player_id == $picker_id) {
                $team = clienttranslate("Picker");
                if ($player_id == $partner_id) {
                    $team = clienttranslate("Picker (Alone)");
                }
            }
            else if ($player_id == $partner_id) {
                $team = clienttranslate('Partner');
            }
            else {
                if ($player_points[$player_id] > 0) {
                    $winning_team = "Opposing";
                }
                $team = clienttranslate("Opposing");
            }
            $table[] = array(
                $players[$player_id]['player_name'],
                $hand_points,
                $team,
                $player_points[$player_id],
                $new_scores[$player_id],
            );
        }
        
        // Check for end of game
        $num_hands = self::getGameStateValue('hands') + 1;
        $end_of_game = $this->isEndOfGame($num_hands);
        if ($end_of_game){
            $this->gamestate->nextState("endGame");
        }
        else{
            self::setGameStateValue('hands', $num_hands);
            $this->gamestate->nextState("nextHand");        
            $this->notifyAllPlayers( "tableWindow", '', array(
                "id" => 'Scoring',
                "title" => clienttranslate("Hand Result"),
                "header" => array(
                    'str' => clienttranslate('${team} team wins!'),
                    'args' => [ 'team' => $winning_team ],
                ),
                "table" => $table,
                "closing" => clienttranslate("Next Hand")
            )); 
        }
        self::incStat(1, 'handsPlayed');   
        foreach ($players as $player_id => $player) {
            $num_players = self::getPlayersNumber();
            $cards_taken = 0;      
            $cards = $this->cards->getCardsInLocation("cardswon");
            foreach ( $cards as $card ) {
                if ($card ['location_arg'] == $player_id) {
                    $cards_taken += 1;
                }
            }
            $tricks_taken = intdiv($cards_taken, $num_players);
            self::incStat($player_hand_points[$player_id], 'totalPointsEarned', $player_id);
            self::incStat($tricks_taken, 'totalTricksTaken', $player_id);
            if ($winning_team == "Picking" && ($player_id == $picker_id || $player_id == $partner_id)) {
                self::incStat(1, 'totalHandsWon', $player_id);
            }
            if ($winning_team != "Picking" && $player_id != $picker_id && $player_id != $partner_id) {
                self::incStat(1, 'totalHandsWon', $player_id);
            }
            $hands_won = self::getStat('totalHandsWon', $player_id);
            $sql = "UPDATE player SET player_score_aux=player_score_aux+$hands_won  WHERE player_id='$player_id'";
            self::DbQuery($sql);
            
            $num_hands = self::getStat('handsPlayed');
            $num_queens = self::getStat('totalNumberOfQueens', $player_id);
            $num_trump = self::getStat('totalNumberOfTrump', $player_id);
            
            self::setStat($player_hand_points[$player_id] / $num_hands, 'averagePointsEarned', $player_id); 
            self::setStat($tricks_taken / $num_hands, 'averageTricksTaken', $player_id);    
            self::setStat($num_queens / $num_hands, 'averageNumberOfQueens', $player_id);     
            self::setStat($num_trump / $num_hands, 'averageNumberOfTrump', $player_id); 
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
    function getZombieCard($cards, $active_player) {
        foreach ($cards as $card){
            if ($this->isCardPlayable($card, $active_player)) {
                return $card;
            }
        }
        // should never reach this...
        return $cards[0];
    }
    
    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        $cards = $this->cards->getCardsInLocation('hand', $active_player);


        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                //Zombie will always pass unless forced to pick
                case "bid":
                    $this->gamestate->nextState( "pass" );
                	break;
                //Zombie will always go alone
                case "chooseLoner":
                    $this->gamestate->nextState( "goAlone" );
                    break;
                case "choosePartnerCard":
                    $this->gamestate->nextState( "goAlone" );
                    break;
                //Zombie will burry first 2 cards
                case "exchangeCards":
                    $card_id1 = array_pop($cards)['id'];
                    $card_id2 = array_pop($cards)['id'];
                    $this->exchangeCards($card_id1, $card_id2);
                    break;
                //Zombie will play first playable card
                case "playerTurn":
                    $card = $this->getZombieCard($cards, $active_player);
                    $this->playCard($card['id']);
                    break;
            }

            return;
        }
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
