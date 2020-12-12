"use strict";

function createHint(){
    const el = $(this);
    const caption = el.siblings(".caption");
    const element = el.data("element");

    caption.hint({
        hintText: `
            <div class="text-leader2 mb-1">${element.desc ? element.desc : element.name}</div>
            Repo: <b>${element.repo}</b><br/>
            Branch: <b>${element.branch}</b>
        `
    })
}

$(function(){

    let lobby = $("#lobby");
    const configFile = "https://raw.githubusercontent.com/olton/metro4-builder-config/master/config.json";
    let config;

    $.json(configFile).then( response => {
        config = response;

        $("#loader").hide();

        $.each(config.parts, (i, v) => {
            let part = $("<div>").attr("id", i).addClass("elements-group").appendTo(lobby);
            let elements, groupCheck;

            part.append( groupCheck = $(`<input type='checkbox' data-role='checkbox' data-caption='<span class="text-bold">${v} (${Metro.utils.objectLength(config[i])})</span'>`) );
            part.append( $("<hr>").addClass('thin bg-lightRed') );
            part.append( elements = $("<div>").addClass("elements") );

            $.each(config[i], (elementID, element) => {
                elements.append(
                    $(`<input type='checkbox'>`)
                        .attr("id", `${i}__${elementID}`)
                        .attr("name", `${i}[]`)
                        .attr("data-type", "element-checkbox")
                        .attr("data-group", i)
                        .attr("data-role", "checkbox")
                        .attr("data-caption", element.name)
                        .attr("data-on-checkbox-create", "createHint")
                        .data("element", element)
                        .val(`${elementID}`)
                );
            });

            groupCheck.attr("id", `group-${i}`).on("click", function() {
                let checked = this.checked;
                $(this).closest("label").siblings(".elements").find("input").each(function(){
                    this.checked = checked;
                })
            });
        });
    });

    function checkDependencies(elem){
        const el = $(elem);
        const group = el.attr("data-group");
        const value = el.val();
        const deps = config[group][value]["dependencies"];

        const checkboxes = $("input[data-type=element-checkbox]");

        $.each(deps, function(key, items){
            $.each(items, function(){
                const _name = this;
                checkboxes.each(function (){
                    const _el = $(this);
                    if (_el.attr("data-group") === key && _el.val() === _name) {
                        this.checked = true;
                        checkDependencies(this);
                    }
                })
            })
        });
    }

    function checkDependentObjects(elem){
        const el = $(elem);
        const group = el.attr("data-group");
        const value = el.val();
        const checkboxes = $("input[data-type=element-checkbox]");
        let result = false;

        checkboxes.each(function(){
            const _el = $(this);
            const parent = _el.parent();
            const _group = _el.attr("data-group");
            const _name = _el.val();
            const _deps = config[_group][_name]["dependencies"];

            if (!_deps || !_deps[group] || _deps[group].indexOf(value) === -1) {
                return ;
            }

            if (this.checked) {
                parent.addClass("flash-checkbox");
                setTimeout( () => {
                    parent.removeClass("flash-checkbox");
                }, 1000);
                result = true;
            }
        });

        return result;
    }

    $(document).on("click", "input[data-type=element-checkbox]", function(){
        const checked = this.checked;

        if (!checked) {
            if (checkDependentObjects(this)) {
                this.checked = true;
            }
        } else {
            checkDependencies(this);
        }
    });
})

function requestBuild(f){
    const activity = Metro.activity.open({
        type: 'atom',
        overlayColor: '#fff',
        overlayAlpha: 1,
        text: '<div class=\'mt-2 text-small\'>The assembly is started...<br>Please, wait...</div>',
        overlayClickClose: true
    });

    $.post("build.php", f).then(function(data){
        let response;

        Metro.activity.close(activity);

        response = JSON.parse(data.substr(data.indexOf('{"result":')));

        if (response.result) {
            document.location.href = response.data.href;
        } else {
            Metro.infobox.create("Error!<br/>" + response.message, "alert");
        }
    }, function(){
        Metro.activity.close(activity);
        Metro.infobox.create("Error!<br/>" + "Build operation failed!", "alert");
    });
}