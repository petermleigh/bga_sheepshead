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
        <div id="partner_card"></div>
        <br>
        <div id="current_trick"></div>
        <br>
        <div id="points_details"></div>
    </div>
</div>
<div id="card_help">
    <a href="#" id="toggle_help" class="expandabletoggle expandablearrow">
        Show Help <div class="icon20"></div>
    </a>
    <div id="help_hidden" class="expandablecontent whiteblock">{HELP}</div>
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
