var SelectDefaultConfig = {
    clearButton: false,
    clearButtonIcon: "<span class='default-icon-cross'></span>",
    placeholder: "",
    addEmptyValue: false,
    emptyValue: "",
    duration: 100,
    prepend: "",
    append: "",
    filterPlaceholder: "",
    filter: true,
    copyInlineStyles: true,
    dropHeight: 200,

    clsSelect: "",
    clsSelectInput: "",
    clsPrepend: "",
    clsAppend: "",
    clsOption: "",
    clsOptionActive: "",
    clsOptionGroup: "",
    clsDropList: "",
    clsSelectedItem: "",
    clsSelectedItemRemover: "",

    onChange: Metro.noop,
    onUp: Metro.noop,
    onDrop: Metro.noop,
    onItemSelect: Metro.noop,
    onItemDeselect: Metro.noop,
    onSelectCreate: Metro.noop
};

Metro.selectSetup = function (options) {
    SelectDefaultConfig = $.extend({}, SelectDefaultConfig, options);
};

if (typeof window["metroSelectSetup"] !== undefined) {
    Metro.selectSetup(window["metroSelectSetup"]);
}

var Select = {
    init: function( options, elem ) {
        this.options = $.extend( {}, SelectDefaultConfig, options );
        this.elem  = elem;
        this.element = $(elem);
        this.list = null;
        this.placeholder = null;

        this._setOptionsFromDOM();
        this._create();

        return this;
    },

    _setOptionsFromDOM: function(){
        var element = this.element, o = this.options;

        $.each(element.data(), function(key, value){
            if (key in o) {
                try {
                    o[key] = JSON.parse(value);
                } catch (e) {
                    o[key] = value;
                }
            }
        });
    },

    _create: function(){
        var element = this.element, o = this.options;

        Metro.checkRuntime(element, "select");

        this._createSelect();
        this._createEvents();

        Utils.exec(o.onSelectCreate, null, element[0]);
        element.fire("selectcreate");
    },

    _setPlaceholder: function(){
        var element = this.element, o = this.options;
        var input = element.siblings(".select-input");
        if (!Utils.isValue(element.val()) || element.val() == o.emptyValue) {
            input.html(this.placeholder);
        }
    },

    _addOption: function(item, parent){
        var option = $(item);
        var l, a;
        var element = this.element, o = this.options;
        var multiple = element[0].multiple;
        var input = element.siblings(".select-input");
        var html = Utils.isValue(option.attr('data-template')) ? option.attr('data-template').replace("$1", item.text):item.text;
        var tag;

        l = $("<li>").addClass(o.clsOption).data("option", item).attr("data-text", item.text).attr('data-value', Utils.isValue(item.value) ? item.value : item.text).appendTo(parent);
        a = $("<a>").html(html).appendTo(l);

        l.addClass(item.className);

        if (option.is(":disabled")) {
            l.addClass("disabled");
        }

        if (option.is(":selected")) {
            if (multiple) {
                l.addClass("d-none");
                tag = $("<div>").addClass("selected-item").addClass(o.clsSelectedItem).html("<span class='title'>"+html+"</span>").appendTo(input);
                tag.data("option", l);
                $("<span>").addClass("remover").addClass(o.clsSelectedItemRemover).html("&times;").appendTo(tag);
            } else {
                element.val(item.value);
                input.html(html);
                element.fire("change", {
                    val: item.value
                });
                l.addClass("active");
            }
        }

        a.appendTo(l);
        l.appendTo(parent);
    },

    _addOptionGroup: function(item, parent){
        var that = this;
        var group = $(item);

        $("<li>").html(item.label).addClass("group-title").appendTo(parent);

        $.each(group.children(), function(){
            that._addOption(this, parent);
        })
    },

    _createOptions: function(){
        var that = this, element = this.element, o = this.options, select = element.parent();
        var list = select.find("ul").html("");
        var selected = element.find("option[selected]").length > 0;

        if (o.addEmptyValue === true) {
            element.prepend($("<option "+(!selected ? 'selected' : '')+" value='"+o.emptyValue+"' class='d-none'></option>"));
        }

        $.each(element.children(), function(){
            if (this.tagName === "OPTION") {
                that._addOption(this, list);
            } else if (this.tagName === "OPTGROUP") {
                that._addOptionGroup(this, list);
            }
        });
    },

    _createSelect: function(){
        var element = this.element, o = this.options;

        var container = $("<label>").addClass("select " + element[0].className).addClass(o.clsSelect);
        var multiple = element[0].multiple;
        var select_id = Utils.elementId("select");
        var buttons = $("<div>").addClass("button-group");
        var input, drop_container, list, filter_input, placeholder, dropdown_toggle;

        this.placeholder = $("<span>").addClass("placeholder").html(o.placeholder);

        container.attr("id", select_id);

        dropdown_toggle = $("<span>").addClass("dropdown-toggle");
        dropdown_toggle.appendTo(container);

        if (multiple) {
            container.addClass("multiple");
        }

        container.insertBefore(element);
        element.appendTo(container);
        buttons.appendTo(container);

        input = $("<div>").addClass("select-input").addClass(o.clsSelectInput).attr("name", "__" + select_id + "__");
        drop_container = $("<div>").addClass("drop-container");
        list = $("<ul>").addClass( o.clsDropList === "" ? "d-menu" : o.clsDropList).css({
            "max-height": o.dropHeight
        });
        filter_input = $("<input type='text' data-role='input'>").attr("placeholder", o.filterPlaceholder);

        container.append(input);
        container.append(drop_container);

        drop_container.append(filter_input);

        if (o.filter !== true) {
            filter_input.hide();
        }

        drop_container.append(list);

        this._createOptions();

        this._setPlaceholder();

        Metro.makePlugin(drop_container, "dropdown", {
            dropFilter: ".select",
            duration: o.duration,
            toggleElement: [container],
            onDrop: function(){
                var dropped, target;
                dropdown_toggle.addClass("active-toggle");
                dropped = $(".select .drop-container");
                $.each(dropped, function(){
                    var drop = $(this);
                    if (drop.is(drop_container)) {
                        return ;
                    }
                    var dataDrop = drop.data('dropdown');
                    if (dataDrop && dataDrop.close) {
                        dataDrop.close();
                    }
                });

                filter_input.val("").trigger(Metro.events.keyup).focus();

                target = list.find("li.active").length > 0 ? $(list.find("li.active")[0]) : undefined;
                if (target !== undefined) {
                    list[0].scrollTop = target.position().top - ( (list.height() - target.height() )/ 2);
                }

                Utils.exec(o.onDrop, [list[0]], element[0]);
                element.fire("drop", {
                    list: list[0]
                });
            },
            onUp: function(){
                dropdown_toggle.removeClass("active-toggle");
                Utils.exec(o.onUp, [list[0]], element[0]);
                element.fire("up", {
                    list: list[0]
                });
            }
        });

        this.list = list;

        if (o.clearButton === true && !element[0].readOnly) {
            var clearButton = $("<button>").addClass("button input-clear-button").addClass(o.clsClearButton).attr("tabindex", -1).attr("type", "button").html(o.clearButtonIcon);
            clearButton.appendTo(buttons);
        } else {
            buttons.addClass("d-none");
        }

        if (o.prepend !== "") {
            var prepend = $("<div>").html(o.prepend);
            prepend.addClass("prepend").addClass(o.clsPrepend).appendTo(container);
        }

        if (o.append !== "") {
            var append = $("<div>").html(o.append);
            append.addClass("append").addClass(o.clsAppend).appendTo(container);
        }

        if (o.copyInlineStyles === true) {
            for (var i = 0, l = element[0].style.length; i < l; i++) {
                container.css(element[0].style[i], element.css(element[0].style[i]));
            }
        }

        if (element.attr('dir') === 'rtl' ) {
            container.addClass("rtl").attr("dir", "rtl");
        }

        if (element.is(':disabled')) {
            this.disable();
        } else {
            this.enable();
        }

    },

    _createEvents: function(){
        var that = this, element = this.element, o = this.options;
        var container = element.closest(".select");
        var drop_container = container.find(".drop-container");
        var input = element.siblings(".select-input");
        var filter_input = drop_container.find("input");
        var list = drop_container.find("ul");
        var clearButton = container.find(".input-clear-button");

        clearButton.on(Metro.events.click, function(e){
            element.val(o.emptyValue);
            if (element[0].multiple) {
                list.find("li").removeClass("d-none");
            }
            that._setPlaceholder();
            e.preventDefault();
            e.stopPropagation();
        });

        element.on(Metro.events.change, function(){
            that._setPlaceholder();
        });

        container.on(Metro.events.click, function(e){
            $(".focused").removeClass("focused");
            container.addClass("focused");
            // e.preventDefault();
            // e.stopPropagation();
        });

        input.on(Metro.events.click, function(){
            $(".focused").removeClass("focused");
            container.addClass("focused");
        });

        list.on(Metro.events.click, "li", function(e){
            if ($(this).hasClass("group-title")) {
                e.preventDefault();
                e.stopPropagation();
                return ;
            }
            var leaf = $(this);
            var val = leaf.data('value');
            var html = leaf.children('a').html();
            var selected_item, selected;
            var option = leaf.data("option");
            var options = element.find("option");

            if (element[0].multiple) {
                leaf.addClass("d-none");
                selected_item = $("<div>").addClass("selected-item").addClass(o.clsSelectedItem).html("<span class='title'>"+html+"</span>").appendTo(input);
                selected_item.data("option", leaf);
                $("<span>").addClass("remover").addClass(o.clsSelectedItemRemover).html("&times;").appendTo(selected_item);
            } else {
                list.find("li.active").removeClass("active").removeClass(o.clsOptionActive);
                leaf.addClass("active").addClass(o.clsOptionActive);
                input.html(html);
                Metro.getPlugin(drop_container[0], "dropdown").close();
            }

            $.each(options, function(){
                if (this === option) {
                    this.selected = true;
                }
            });

            Utils.exec(o.onItemSelect, [val, option, leaf[0]], element[0]);
            element.fire("itemselect", {
                val: val,
                option: option,
                leaf: leaf[0]
            });

            selected = that.getSelected();

            Utils.exec(o.onChange, [selected], element[0]);
            element.fire("change", {
                selected: selected
            });
        });

        input.on("click", ".selected-item .remover", function(e){
            var item = $(this).closest(".selected-item");
            var leaf = item.data("option");
            var option = leaf.data('option');
            var selected;

            leaf.removeClass("d-none");
            $.each(element.find("option"), function(){
                if (this === option) {
                    this.selected = false;
                }
            });
            item.remove();

            Utils.exec(o.onItemDeselect, [option], element[0]);
            element.fire("itemdeselect", {
                option: option
            });

            selected = that.getSelected();
            Utils.exec(o.onChange, [selected], element[0]);
            element.fire("change", {
                selected: selected
            });

            e.preventDefault();
            e.stopPropagation();
        });

        filter_input.on(Metro.events.keyup, function(){
            var filter = this.value.toUpperCase();
            var li = list.find("li");
            var i, a;
            for (i = 0; i < li.length; i++) {
                if ($(li[i]).hasClass("group-title")) continue;
                a = li[i].getElementsByTagName("a")[0];
                if (a.innerHTML.toUpperCase().indexOf(filter) > -1) {
                    li[i].style.display = "";
                } else {
                    li[i].style.display = "none";
                }
            }
        });

        filter_input.on(Metro.events.click, function(e){
            e.preventDefault();
            e.stopPropagation();
        });

        drop_container.on(Metro.events.click, function(e){
            e.preventDefault();
            e.stopPropagation();
        });
    },

    disable: function(){
        this.element.data("disabled", true);
        this.element.closest(".select").addClass("disabled");
    },

    enable: function(){
        this.element.data("disabled", false);
        this.element.closest(".select").removeClass("disabled");
    },

    toggleState: function(){
        if (this.elem.disabled) {
            this.disable();
        } else {
            this.enable();
        }
    },

    reset: function(to_default){
        var element = this.element, o = this.options;
        var options = element.find("option");
        var select = element.closest('.select');
        var selected;

        $.each(options, function(){
            this.selected = !Utils.isNull(to_default) ? this.defaultSelected : false;
        });

        this.list.find("li").remove();
        select.find(".select-input").html('');

        this._createOptions();

        selected = this.getSelected();
        Utils.exec(o.onChange, [selected], element[0]);
        element.fire("change", {
            selected: selected
        });
    },

    getSelected: function(){
        var element = this.element;
        var result = [];

        element.find("option").each(function(){
            if (this.selected) result.push(this.value);
        });

        return result;
    },

    val: function(val){
        var element = this.element, o = this.options;
        var input = element.siblings(".select-input");
        var options = element.find("option");
        var list_items = this.list.find("li");
        var result = [];
        var multiple = element.attr("multiple") !== undefined;
        var option;
        var i, html, list_item, option_value, tag, selected;

        if (Utils.isNull(val)) {
            $.each(options, function(){
                if (this.selected) result.push(this.value);
            });
            return multiple ? result : result[0];
        }

        $.each(options, function(){
            this.selected = false;
        });
        list_items.removeClass("active");
        input.html('');

        if (Array.isArray(val) === false) {
            val  = [val];
        }

        $.each(val, function(){
            for (i = 0; i < options.length; i++) {
                option = options[i];
                html = Utils.isValue(option.getAttribute('data-template')) ? option.getAttribute('data-template').replace("$1", option.text) : option.text;
                if (""+option.value === ""+this) {
                    option.selected = true;
                    break;
                }
            }

            for(i = 0; i < list_items.length; i++) {
                list_item = $(list_items[i]);
                option_value = list_item.attr("data-value");
                if (""+option_value === ""+this) {
                    if (multiple) {
                        list_item.addClass("d-none");
                        tag = $("<div>").addClass("selected-item").addClass(o.clsSelectedItem).html("<span class='title'>"+html+"</span>").appendTo(input);
                        tag.data("option", list_item);
                        $("<span>").addClass("remover").addClass(o.clsSelectedItemRemover).html("&times;").appendTo(tag);
                    } else {
                        list_item.addClass("active");
                        input.html(html);
                    }
                    break;
                }
            }
        });

        selected = this.getSelected();
        Utils.exec(o.onChange, [selected], element[0]);
        element.fire("change", {
            selected: selected
        });
    },

    data: function(op){
        var element = this.element;
        var option_group;

        element.html("");

        if (typeof op === 'string') {
            element.html(op);
        } else if (Utils.isObject(op)) {
            $.each(op, function(key, val){
                if (Utils.isObject(val)) {
                    option_group = $("<optgroup label=''>").attr("label", key).appendTo(element);
                    $.each(val, function(key2, val2){
                        $("<option>").attr("value", key2).text(val2).appendTo(option_group);
                    });
                } else {
                    $("<option>").attr("value", key).text(val).appendTo(element);
                }
            });
        }

        this._createOptions();
    },

    changeAttribute: function(attributeName){
        if (attributeName === 'disabled') {
            this.toggleState();
        }
    },

    destroy: function(){
        var element = this.element;
        var container = element.closest(".select");
        var drop_container = container.find(".drop-container");
        var input = element.siblings(".select-input");
        var filter_input = drop_container.find("input");
        var list = drop_container.find("ul");
        var clearButton = container.find(".input-clear-button");

        container.off(Metro.events.click);
        container.off(Metro.events.click, ".input-clear-button");
        input.off(Metro.events.click);
        filter_input.off(Metro.events.blur);
        filter_input.off(Metro.events.focus);
        list.off(Metro.events.click, "li");
        filter_input.off(Metro.events.keyup);
        drop_container.off(Metro.events.click);
        clearButton.off(Metro.events.click);

        drop_container.data("dropdown").destroy();

        return element;
    }
};

$(document).on(Metro.events.click, function(){
    $(".select").removeClass("focused");
}, {ns: "blur-select-elements"});

Metro.plugin('select', Select);

