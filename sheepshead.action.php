<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sheepshead implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * sheepshead.action.php
 *
 * Sheepshead main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/sheepshead/sheepshead/myAction.html", ...)
 *
 */
  
  
  class action_sheepshead extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "sheepshead_sheepshead";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 

    function pick() {
        self::setAjaxMode();
        $this->game->pick();
        self::ajaxResponse();
    }

    function pass() {
        self::setAjaxMode();
        $this->game->pass();
        self::ajaxResponse();
    }

    function goAlone() {
        self::setAjaxMode();
        $this->game->goAlone();
        self::ajaxResponse();
    }

    function choosePartner() {
        self::setAjaxMode();
        $this->game->choosePartner();
        self::ajaxResponse();
    }

    function choosePartnerCard() {
        self::setAjaxMode();
        $card_no = self::getArg("no", AT_posint, true);
        $this->game->choosePartnerCard($card_no);
        self::ajaxResponse();
    }

    function exchangeCards() {
        self::setAjaxMode();
        $card_id1 = self::getArg("id1", AT_posint, true);
        $card_id2 = self::getArg("id2", AT_posint, true);
        $this->game->exchangeCards($card_id1, $card_id2);
        self::ajaxResponse();
    }

    public function playCard() {
        self::setAjaxMode();
        $card_id = self::getArg("id", AT_posint, true);
        $this->game->playCard($card_id);
        self::ajaxResponse();
    }


  }
  

