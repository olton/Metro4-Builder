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

    $.json("source/package.json").then(function(package){
        $("#assembly-version").text(package.version);
    })
});