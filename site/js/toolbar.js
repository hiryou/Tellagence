$(document).ready(function() {
    // toolbar animation and help overlay functions
    $("#toolbar").hide();
    $("#tab").click(
        function(){
            $("#toolbar").slideToggle("fast");
        }
    );
    
    // functions for controlling the help overlay visibility and position
    function loadPopup(){
        //loads popup only if it is disabled
        if($("#bgPopup").data("state") === 0){
            $("#bgPopup").css({
                "opacity": "0.7"
            });
            $("#bgPopup").fadeIn("medium");
            $("#Popup").fadeIn("medium");
            $("#bgPopup").data("state",1);
        }
    }
    
    function disablePopup(){
        if ($("#bgPopup").data("state")==1){
            $("#bgPopup").fadeOut("medium");
            $("#Popup").fadeOut("medium");
            $("#bgPopup").data("state",0);
        }
    }
    
    function centerPopup(){
        var winw = $(window).width();
        var winh = $(window).height();
        var popw = $('#Popup').width();
        var poph = $('#Popup').height();
        $("#Popup").css({
            "position" : "absolute",
            "top" : winh/2 - poph/2,
            "left" : winw/2 - popw/2
        });
    }
    
    // selectors on the help button to trigger the functions
    $("#bgPopup").data("state", 0);
    $("#help_button").click(function(){
        centerPopup();
        loadPopup();
    });

    $("#bgPopup").click(function(){
	if ($("#progress_pbImage").attr("title") == " 100%") {
            disablePopup();
	}
    });

    centerPopup();
    loadPopup();
    
    $("#progress").progressBar(0, {boxImage:"static/bar.gif",
				   barImage:{0:"static/bar_fill.gif",
					     30:"static/bar_fill.gif",
					     70:"static/bar_fill.gif"}});

    $(window).resize(function() {
        centerPopup();
    });
});