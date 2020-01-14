$(function(){
    window.onscroll = function(){
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        console.log(winScroll);
        if (winScroll > 50) {
            $("#header").addClass("scrolled-header");
        } else {
            $("#header").removeClass("scrolled-header");
        }
    };

    $.json("config.json").then(function(config){
        $("#assembly-version-release").text(config.setup.release);
        $("#assembly-version-next").text(config.setup.next);
    })

    var appBar = $(".app-bar");

    appBar.on("menuopen", function(){
        $(this.parentNode).addClass("scrolled-header");
    });

    appBar.on("menuclose", function(){
        if (!(document.body.scrollTop || document.documentElement.scrollTop))
            $(this.parentNode).removeClass("scrolled-header");
    });

});
