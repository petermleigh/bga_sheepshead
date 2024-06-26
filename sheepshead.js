/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sheepshead implementation : © Peter Leigh petermleigh@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

define([
    "dojo",
    "dojo/_base/declare",
    "dojo/dom-style",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/expandablesection"
],
function (dojo, declare) {
    return declare("bgagame.sheepshead", ebg.core.gamegui, {
        constructor: function(){
            this.cardwidth = 216;
            this.cardheight = 336;
            this.tokenwidth = 30;
            this.tokenheight = 30;
        },
        
        setup: function( gamedatas )
        {     
            this.trick_counters = {};
            this.point_counters = {};
            for (var player_id in this.gamedatas.players)
            {
                var player = gamedatas.players[player_id];                         
                // Setting up players boards if needed
                var player_board_div = $('player_board_' + player_id);
                dojo.place(this.format_block('jstpl_player_board', player), player_board_div);

                var trick_counter = new ebg.counter();
                trick_counter.create('trick_counter_'+player_id);
                trick_counter.setValue(this.gamedatas.trick_counters[player_id]);
                this.trick_counters[player_id] = trick_counter;
            }

            // Help Expandable
            this.expanded = new ebg.expandablesection();
            this.expanded.create(this, "card_help");
            this.expanded.collapse();

            // Player hand
            this.playerHand = new ebg.stock(); // new stock object for hand
            this.playerHand.create( this, $('myhand'), this.cardwidth, this.cardheight );

            this.playerHand.image_items_per_row = 8; 
            this.playerHand.centerItems = true;
            this.playerHand.autowidth = true;
               
            var card_uri = g_gamethemeurl + 'img/base.png';
            if (this.prefs[100].value == 2) {
                card_uri = g_gamethemeurl + 'img/alt.png';
            }
            weights = {}
            // Create cards types:
            for (var suit = 1; suit <= 4; suit++) {
                for (var value = 7; value <= 14; value++) {
                    // Build card type id
                    var card_type_id = this.getCardTypeId(suit, value);
                    var card_image_pos = this.getCardImagePos(suit, value);
                    this.playerHand.addItemType(card_type_id, this.getCardWeight(suit, value), card_uri, card_image_pos);
                }
            }
            this.playerHand.resizeItems(this.cardwidth / 3, this.cardheight / 3, 576, 448);

            // Cards in player's hand
            for ( var i in this.gamedatas.hand) {
                var card = this.gamedatas.hand[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardTypeId(suit, value), card.id);
            }

            // Cards played on table
            for (i in this.gamedatas.cardsontable) {
                var card = this.gamedatas.cardsontable[i];
                var suit = card.type;
                var value = card.type_arg;
                var player_id = card.location_arg;
                this.playCardOnTable(player_id, suit, value, card.id);
            }

            for (i in this.gamedatas.playertokens) {
                var token = this.gamedatas.playertokens[i];
                var player_id = token.player_id;
                var token_id = token.token_id;
                this.placeToken(player_id, token_id);
                this.addTooltip('playertoken_' + token_id, token.token_name, '' );
            }

            $("partner_card").innerHTML = this.gamedatas.partnercardstr;
            $("current_trick").innerHTML = this.gamedatas.tricksuitstr;
            $("points_details").innerHTML = this.getPointsSpanStr(this.gamedatas.leaster, this.gamedatas.doublers);

            dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );
            
            this.showPlayable(this.gamedatas.playable_card_ids);

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {            
            switch( stateName )
            {
           
            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            switch( stateName )
            {
            
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'bid':
                        this.addActionButton( 'pick_button', _('Pick'), ()=>this.ajaxcallwrapper('pick'));
                        this.addActionButton( 'pass_button', _('Pass'), ()=>this.ajaxcallwrapper('pass'));
                        break;
                    
                    case 'chooseLoner':
                        this.addActionButton( 'goAlone_button', _('Go Alone'), ()=>this.ajaxcallwrapper('goAlone'));
                        this.addActionButton( 'choosePartner_button', _('Choose a Parner Card'), ()=>this.ajaxcallwrapper('choosePartner'));
                        break;
                    
                    case 'choosePartnerCard':
                        for (let i = 0; i < args.length; i++) {
                            let card_no = args[i]['card_no'];
                            let button_name = card_no + 'Partner_button';
                            let button_text = args[i]['card_str'];
                            this.addActionButton(button_name, button_text, ()=>this.ajaxcallwrapper('choosePartnerCard', {no: card_no, lock: true}));
                        } 
                        this.addActionButton( 'goAlone_button', _('Go Alone'), ()=>this.ajaxcallwrapper('goAlone'));
                        break;
                    
                    case 'exchangeCards':
                        this.addActionButton( 'confirmExchange_button', "Confirm (0/2)", 'onExchangeCards')
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        getCardWeight: function(suit, value) {
            switch (suit) {
                case 1:  // spades
                    weight = 30;
                    break;
                case 2: // hearts
                    weight = 15;
                    break;
                case 3: // clubs
                    weight = 45;
                    break;
                case 4: // diamonds
                    weight = 60;
                    break;
                default:
                    weight = 0;
                    break;
            }
            switch (value) {
                case 10: // tens
                    weight = weight + value + 3;
                    break;
                case 11: // jacks
                    weight = ((weight / 15) % 4) + 75;
                    break;
                case 12: // queens
                    weight = ((weight / 15) % 4) + 80;
                    break;
                case 13: // kings
                    weight = weight + value - 3;
                    break;
                default:
                    weight = weight + value;
            }
            return 85 - weight; // reverse it
        },
        
        getCardTypeId: function(suit, value) {
            return (suit - 1) * 13 + (value - 2);
        },

        getCardImagePos: function(suit, value) {
            return (suit - 1) * 8 + (value - 7);
        },

        getPointsSpanStr: function(leaster, doublers) {
            if (leaster == 1){
                return  "Leaster Hand";
            }
            if (doublers > 0){
                return doublers * 2 + "x Points";
            }
            return "";
        },

        showPlayable : function(playable_card_ids) {            
            if (this.prefs[101].value == 2) {
                return;
            }
            var hand = this.playerHand.getAllItems();
            for (var i in hand) {
                var card = hand[i]
                var card_div = $(this.playerHand.getItemDivId(card.id));
                dojo.removeClass(card_div, 'playable_card');
                dojo.addClass(card_div, 'unplayable_card');
            }
            for (var i in playable_card_ids) {
                var card_no = playable_card_ids[i];
                var card_div = $(this.playerHand.getItemDivId(card_no));
                dojo.removeClass(card_div, 'unplayable_card');
                dojo.addClass(card_div, 'playable_card');
            }
        },

        placeToken : function(player_id, token_id) {
            if (player_id == 0) {
                // destroy unused tokens
                dojo.destroy('playertoken_' + token_id);
                return;
            }
            if (dojo.query('#playertoken_' + token_id).length > 0){
                // destroy duplicate token
                dojo.destroy('playertoken_' + token_id);
            }
            dojo.place(
                this.format_block(
                    'jstpl_token', 
                    {
                        x : this.tokenwidth * (token_id - 1),
                        y : 0,
                        token_id: token_id
                    }
                ), 
                'playertokens_' + player_id
            );
            var num_tokens = dojo.query('#playertokens_' + player_id + ' .playertoken').length;
            var height_offset = (this.tokenheight / 2) * num_tokens;
            this.placeOnObjectPos('playertoken_' + token_id, 'playertokens_' + player_id, 0, height_offset);            
            this.slideToObjectPos('playertoken_' + token_id, 'playertokens_' + player_id, 0, height_offset).play();
        },

        createCard : function(player_id, suit, value) {   
            var css_class = "cardontable";         
            if (this.prefs[100].value == 2) {
                css_class = "cardontable_alt";         
            }
            dojo.place(
                this.format_block(
                    'jstpl_cardontable', 
                    {
                        x : (value - 7) * 100,
                        y : (suit - 1) * 100,
                        class: css_class,
                        player_id : player_id,
                    }
                ), 
                'playertablecard_' + player_id
            );
            return 'cardontable_' + player_id;
        },

        playCardOnTable : function(player_id, suit, value, card_id) {
            var card_element = this.createCard(player_id, suit, value);
            if (player_id != this.player_id) {
                // Some opponent played a card
                // Move card from player panel
                this.placeOnObject(card_element, 'overall_player_board_' + player_id);
            } else {
                // You played a card. If it exists in your hand, move card from there and remove
                // corresponding item

                if ($('myhand_item_' + card_id)) {
                    this.placeOnObject(card_element, 'myhand_item_' + card_id);
                    this.playerHand.removeFromStockById(card_id);
                    this.showPlayable();
                }
            }

            // In any case: move it to its final destination
            this.slideToObject(card_element, 'playertablecard_' + player_id).play();
        },

        ajaxcallwrapper: function(action, args, handler) {
            if (!args) {
                args = {};
            }
            args.lock = true;

            if (this.checkAction(action)) {
                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", args, this, (result) => { }, handler);
            }
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
        onExchangeCards: function() {
            var items = this.playerHand.getSelectedItems();
            if (items.length == 2) {
                if (this.checkAction('exchangeCards', true)) {
                    this.ajaxcallwrapper('exchangeCards', {id1 : items[0].id, id2 : items[1].id, lock : true})
                    this.playerHand.unselectAll();
                    this.playerHand.removeFromStockById(items[0].id);
                    this.playerHand.removeFromStockById(items[1].id);

                }
            }

        },

        onPlayerHandSelectionChanged: function() {
            var items = this.playerHand.getSelectedItems();
            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    this.ajaxcallwrapper('playCard', {id : items[0].id, lock : true})
                    this.playerHand.unselectAll();
                } else if (this.checkAction('exchangeCards', true)) {
                    $('confirmExchange_button').innerHTML = 'Confirm (' + items.length + '/2)';
                    if (items.length > 2) {
                        dojo.addClass('confirmExchange_button', 'disabled');
                    } 
                    else {
                        dojo.removeClass('confirmExchange_button', 'disabled');
                    }  
                } else {
                    this.playerHand.unselectAll();
                }
            }
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your sheepshead.game.php file.
        
        */
        setupNotifications: function()
        {            
            dojo.subscribe('moveTokens', this, "notif_moveTokens");
            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('partnerCardChosen', this, "notif_partnerCardChosen");
            dojo.subscribe('playCard', this, "notif_playCard");
            dojo.subscribe('trickWin', this, "notif_trickWin");
            dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
            dojo.subscribe('newScores', this, "notif_newScores");
            dojo.subscribe('doublers', this, "notif_setDoublers");
            dojo.subscribe('leaster', this, "notif_setLeaster");
            dojo.subscribe('nextPlayer', this, "notif_nextPlayer");
 
            this.notifqueue.setSynchronous( 'trickWin', 1000 );

        },  

        notif_moveTokens : function(notif) {
            for (var i in notif.args.tokens) {
                var player_id = notif.args.tokens[i]['player_id'];
                var token_id = notif.args.tokens[i]['token_id'];
                var token_name = notif.args.tokens[i]['token_name'];
                this.placeToken(player_id, token_id);
                this.addTooltip('playertoken_' + token_id, token_name, '');
            }
        },

        notif_newHand : function(notif) {
            // We received a new full hand
            this.playerHand.removeAll();

            for ( var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardTypeId(suit, value), card.id);
            }
        },

        notif_partnerCardChosen : function(notif) {
            // Update partner card
            $("partner_card").innerHTML = notif.args.partner_card_str;
        },

        notif_playCard : function(notif) {
            $("current_trick").innerHTML = notif.args.trick_suit_str;
            // Play a card on the table
            this.playCardOnTable(notif.args.player_id, notif.args.suit, notif.args.value, notif.args.card_id);
        },

        notif_trickWin : function(notif) {
            $("current_trick").innerHTML = "";
            this.trick_counters[notif.args.player_id].incValue(1);
        },

        notif_giveAllCardsToPlayer : function(notif) {
            // Move all cards on table to given table, then destroy them
            var winner_id = notif.args.player_id;
            for ( var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'cardontable_' + winner_id);
                dojo.connect(
                    anim, 
                    'onEnd', 
                    function(node) {
                        dojo.destroy(node);
                    }
                );
                anim.play();
            }
        },

        notif_newScores : function(notif) {
            $("points_details").innerHTML = this.getPointsSpanStr(null, null);  
            // Update players' scores
            for ( var player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
                this.trick_counters[player_id].toValue(0);
            }
        },

        notif_setDoublers : function(notif) {
            $("points_details").innerHTML = this.getPointsSpanStr(null, notif.args.doublers);
        },

        notif_setLeaster : function(notif) {
            $("points_details").innerHTML = this.getPointsSpanStr(notif.args.leaster, null);  
            $("partner_card").innerHTML = "";        
        },

        notif_nextPlayer : function(notif) {
            this.showPlayable(notif.args.playable_card_ids);
        },
   });             
});
