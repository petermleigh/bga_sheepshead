/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sheepshead implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * sheepshead.js
 *
 * Sheepshead user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.sheepshead", ebg.core.gamegui, {
        constructor: function(){
            console.log('sheepshead constructor');
            this.cardwidth = 72;
            this.cardheight = 96;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
                                
            // Player hand
            this.playerHand = new ebg.stock(); // new stock object for hand
            this.playerHand.create( this, $('myhand'), this.cardwidth, this.cardheight );
            this.playerHand.image_items_per_row = 13; // 13 images per row
            
            // Create cards types:
            for (var suit = 1; suit <= 4; suit++) {
                for (var value = 2; value <= 14; value++) {
                    // Build card type id
                    var card_type_id = this.getCardTypeId(suit, value);
                    this.playerHand.addItemType(card_type_id, card_type_id, g_gamethemeurl + 'img/cards.jpg', card_type_id);
                }
            }

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
            // Trick suit and partner card
            $("partnerCard_span").innerHTML = this.gamedatas.partner_card_uni;
            $("trickSuit_span").innerHTML = this.gamedatas.suit_uni;
            
            dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
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
            console.log( 'Leaving state: ' + stateName );
            
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
            console.log( 'onUpdateActionButtons: '+stateName );
                      
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
                            let card_uni = args[i]['card_uni'];
                            let button_name = `${card_no}Partner_button`;
                            let button_text = _(`${card_uni}`);
                            this.addActionButton(button_name, button_text, ()=>this.ajaxcallwrapper('choosePartnerCard', {no: card_no}));
                            dojo.addClass(button_name,'cardunicode');
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

        // Get card type identifier based on its suit and value
        getCardTypeId : function(suit, value) {
            return (suit - 1) * 13 + (value - 2);
        },

        playCardOnTable : function(player_id, suit, value, card_id) {
            // player_id => direction
            dojo.place(this.format_block('jstpl_cardontable', {
                x : this.cardwidth * (value - 2),
                y : this.cardheight * (suit - 1),
                player_id : player_id
            }), 'playertablecard_' + player_id);

            if (player_id != this.player_id) {
                // Some opponent played a card
                // Move card from player panel
                this.placeOnObject('cardontable_' + player_id, 'overall_player_board_' + player_id);
            } else {
                // You played a card. If it exists in your hand, move card from there and remove
                // corresponding item

                if ($('myhand_item_' + card_id)) {
                    this.placeOnObject('cardontable_' + player_id, 'myhand_item_' + card_id);
                    this.playerHand.removeFromStockById(card_id);
                }
            }

            // In any case: move it to its final destination
            this.slideToObject('cardontable_' + player_id, 'playertablecard_' + player_id).play();
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
            console.log( 'onPlayerHandSelectionChanged: '+items );

            if (items.length > 0) {
                if (this.checkAction('playCard', true)) {
                    this.ajaxcallwrapper('playCard', {id : items[0].id, lock : true})
                    this.playerHand.unselectAll();
                } else if (this.checkAction('exchangeCards', true)) {
                    $('confirmExchange_button').innerHTML = `Confirm (${items.length}/2)`;
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
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe('newHand', this, "notif_newHand");
            dojo.subscribe('playCard', this, "notif_playCard");
            dojo.subscribe('trickWin', this, "notif_trickWin" );
            dojo.subscribe('newTrick', this, "notif_newTrick");
            dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer" );
            dojo.subscribe('newScores', this, "notif_newScores" );
 
            this.notifqueue.setSynchronous( 'trickWin', 1000 );

        },  
        
        notif_newHand : function(notif) {
            // We received a new full hand
            this.playerHand.removeAll();
            $("partnerCard_span").innerHTML = notif.args.partner_card_uni;
            $("trickSuit_span").innerHTML = " ";
            for ( var i in notif.args.cards) {
                var card = notif.args.cards[i];
                var suit = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardTypeId(suit, value), card.id);
            }
        },

        notif_playCard : function(notif) {
            // Play a card on the table
            this.playCardOnTable(notif.args.player_id, notif.args.suit, notif.args.value, notif.args.card_id);
        },

        notif_trickWin : function(notif) {
            $("trickSuit_span").innerHTML = " ";
        },

        notif_newTrick : function(notif) {
            $("trickSuit_span").innerHTML = notif.args.suit_uni;
        },

        notif_giveAllCardsToPlayer : function(notif) {
            // Move all cards on table to given table, then destroy them
            var winner_id = notif.args.player_id;
            for ( var player_id in this.gamedatas.players) {
                var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
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
            // Update players' scores
            for ( var player_id in notif.args.newScores) {
                this.scoreCtrl[player_id].toValue(notif.args.newScores[player_id]);
            }
        },

   });             
});
