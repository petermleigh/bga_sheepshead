{OVERALL_GAME_HEADER}

<div id="playertables">
    <!-- BEGIN playerhandblock -->
    <div class="playertable whiteblock playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertokens" id="playertokens_{PLAYER_ID}"></div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div>
    <!-- END playerhandblock -->
    <div class="playertable playertable_info">
        <div>
            Partner Card<br>
            <span id="player_card_span" class="bgabutton_black"></span>
        </div>
        <br>
        <div>
            Current Trick Suit<br>
            <span id="current_trick_span"></span>
        </div>
        <div id="tokens" class="tokenstash"></div>
    </div>
</div>
<div id="card_help">
    <a href="#" id="toggle_help" class="expandabletoggle expandablearrow">
        <div class="icon20"></div>
    </a>
    <div id="help_hidden" class="expandablecontent">
        Trump Strength: Q♣ Q♠ Q♥ Q♦ J♣ J♠ J♥ J♦ A♦ 10♦ K♦ 9♦ 8♦ 7♦ <br>
        Other Suit Strength: A 10 K 9 8 7 <br>
        Card Points: A-11, 10-10, K-4, Q-3, J-2
    </div>
</div>
<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand">
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

var jstpl_cardontable = '<div class="cardontable" id="cardontable_${player_id}" style="background-position:-${x}px -${y}px">\
                        </div>';

var jstpl_token = '<div class="playertoken" id="playertoken_${token_id}" style="background-position:-${x}px -${y}px">\
                   </div>';
</script>  

{OVERALL_GAME_FOOTER}
