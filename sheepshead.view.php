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

        /*********** Place your code below:  ************/
        $template = self::getGameName() . "_" . self::getGameName();
        
        $directions = array('S', 'SW', 'NW', 'NE', 'SE');  
        global $g_user;
        $current_player_id = $g_user->get_id();
        $player_to_dir = array();
        foreach ($directions as $dir) {
            $player_to_dir[$current_player_id] = $dir;
            $current_player_id = $this->game->getPlayerAfter($current_player_id);
        }
        
        // this will inflate our player block with actual players data
        $this->page->begin_block($template, "playerhandblock");
        foreach ( $players as $player_id => $info ) {
            if (array_key_exists($player_id, $player_to_dir)){
                $dir = $player_to_dir[$player_id];
            }
            else {
                $dir = array_shift($directions);
            }
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
        $trump_str = self::_("Trump Strength");
        $other_str = self::_("Other Suit Strength");
        $card_str = self::_('Card');
        $points_str = self::_('Points');
        $token_str = self::_('Token');
        $token_desc_str = self::_('Description');
        $picker_str = self::_('Picker');
        $partner_str = self::_('Partner');
        $deaker_str = self::_('Dealer');
        
        $this->tpl['HELP_STR'] = self::_("Toggle Help");
        $this->tpl['HELP'] = self::raw("
            $trump_str: Q♣ Q♠ <span style='color: red'>Q♥ Q♦</span> J♣ J♠ <span style='color: red'>J♥ J♦ A♦ 10♦ K♦ 9♦ 8♦ 7♦ </span><br><br>
            $other_str: A 10 K 9 8 7 <br><br>
            <table class='help_table'>
                <tr>
                    <th class='help_table'>$card_str</th>
                    <th class='help_table'>$points_str</th>
                </tr>
                <tr>
                    <td>A</td>
                    <td>11</td>
                </tr>
                <tr>
                    <td>10</td>
                    <td>10</td>
                </tr>
                <tr>
                    <td>K</td>
                    <td>4</td>
                </tr>
                <tr>
                    <td>Q</td>
                    <td>3</td>
                </tr>
                <tr>
                    <td>J</td>
                    <td>2</td>
                </tr>
            </table>
            <br><br>   
            <table class='help_table'>
                <tr>
                    <th class='help_table'>$token_str</th>
                    <th class='help_table'>$token_desc_str</th>
                </tr>
                <tr style='height:30px;'>
                    <td class='playertoken' style='background-position:-210px 0px'></td>
                    <td>$picker_str</td>
                </tr>
                <tr style='height:30px;'>
                    <td class='playertoken' style='background-position:-180px 0px'></td>
                    <td>$partner_str</td>
                </tr>
                <tr style='height:30px;'>
                    <td class='playertoken' style='background-position:-30px 0px'></td>
                    <td>$deaker_str</td>
                </tr>
            </table>"
        );
        
        /*********** Do not change anything below this line  ************/
  	}
}
