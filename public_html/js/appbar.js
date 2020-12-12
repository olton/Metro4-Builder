var appBarBackground = false;

$(window).on("scroll", function(){
    var appBar = $(".app-bar");
    var h = appBar.height();
    if (scrollY > h && !appBarBackground) {
        appBar.parent().addClass("bg-white");
        appBarBackground = true;
    } else if (appBarBackground && scrollY <= h) {
        appBarBackground = false;
        appBar.parent().removeClass("bg-white");
    }
});

function appBarMenuOpen(menu){
    if (!appBarBackground) $(menu).parents(".container-fluid").addClass("bg-white");
}

function appBarMenuClose(menu){
    if (!appBarBackground) $(menu).parents(".container-fluid").removeClass("bg-white");
}
