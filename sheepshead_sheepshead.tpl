{OVERALL_GAME_HEADER}

<div id="playertables">
    <!-- BEGIN playerhandblock -->
    <div class="playertable playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertokens" id="playertokens_{PLAYER_ID}"></div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div>
    <!-- END playerhandblock -->
    <div class="playertable_info">
        <div id="partner_card" class="info_block"></div>
        <div id="current_trick" class="info_block"></div>
        <div id="points_details" class="info_block"></div>
    </div>
</div>
<div id="myhand_wrap">
    <div id="myhand">
    </div>
</div>
<div id="card_help" style="text-align: center">
    <a href="#" id="toggle_help_button" class="bgabutton bgabutton_gray expandabletoggle expandablearrow">
        {HELP_STR}<div class="icon20"></div>
    </a>
    <div id="help_hidden" class="expandablecontent whiteblock">{HELP}</div>
</div>

<script type="text/javascript">

// Javascript HTML templates

var jstpl_cardontable = '<div class="${class}" id="cardontable_${player_id}" style="background-position:-${x}% -${y}%">\
                        </div>';

var jstpl_token = '<div class="playertoken" id="playertoken_${token_id}" style="background-position:-${x}px -${y}px">\
                   </div>';

</script>  

{OVERALL_GAME_FOOTER}
