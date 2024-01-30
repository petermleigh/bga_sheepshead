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
  
require_once( APP_BASE_PATH."view/common/game.view.php" );
  
class view_sheepshead_sheepshead extends game_view
{
    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "sheepshead";
    }
    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();
        
        $directions = array('S', 'SW', 'NW', 'NE', 'SE');
        
        // this will inflate our player block with actual players data
        $this->page->begin_block($template, "playerhandblock");
        foreach ( $players as $player_id => $info ) {
            $dir = array_shift($directions);
            $this->page->insert_block(
                "playerhandblock", 
                array(
                    "PLAYER_ID" => $player_id,
                    "PLAYER_NAME" => $info['player_name'],
                    "PLAYER_COLOR" => $info['player_color'],
                    "DIR" => $dir 
                )
            );
        }
        // this will make our My Hand text translatable
        $this->tpl['MY_HAND'] = self::_("My hand");
        
        /*********** Do not change anything below this line  ************/
  	}
}
