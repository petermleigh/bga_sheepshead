{OVERALL_GAME_HEADER}

<div id="playertables">

    <!-- BEGIN playerhandblock -->
    <div class="playertable whiteblock playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div>
    <!-- END playerhandblock -->
    <div class="playertable playertable_N">
        <div>
            Partner Card<br>
            <span id="player_card_span"></span>
        </div>
        <br>
        <div>
            Current Trick Suit<br>
            <span id="current_trick_span"></span>
        </div>
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

</script>  

{OVERALL_GAME_FOOTER}
