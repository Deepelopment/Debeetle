jQuery.fn.visible = function()
{
    return this.css('visibility', 'visible');
};

jQuery.fn.invisible = function()
{
    return this.css('visibility', 'hidden');
};

jQuery.fn.visibilityToggle = function()
{
    return
        this.css(
            'visibility',
            function(i, visibility)
            {
                return (visibility == 'visible') ? 'hidden' : 'visible';
            }
        );
};

/**
 * @todo complete docblocking
 */

var $d =
{
    /**#@+
     * @private
     */

    frame:      top.frames ? top.frames['debeetleFrame'] : window,
    container:  document.createElement('DIV'),
    panel:      null,
    tab:        null,
    tabs:       null,

    data:       null,
    dictionary: null,

    state:      {},
    launched:   false,

    /**#@-*/

    setDictionary: function(dictionary)
    {
        this.View.Locale.add(dictionary);
        this.dictionary = dictionary;
    },

    /**
     * Debeetle enrty point
     *
     * @param  {object} data
     * @param  {object} tabs
     * @return {void}
     * @todo   Process tabs argument
     */
    startup: function(data, tabs, parentWindowHeight)
    {
        this.tabs = tabs;
        this.data = data;

        this.data.parentWindowHeight = parentWindowHeight;

        // Load iframe related CSS-file into parent document {

        var
            parent = this.frame.parentNode.ownerDocument,
            file = document.createElement('LINK'),
            heads = $('<head>', parent);

        if (heads.length < 1) {
            parent.appendChild(document.createElement('HEAD'));
        }
        file.setAttribute('rel', 'stylesheet');
        file.setAttribute('type', 'text/css');
        file.setAttribute('media', 'screen');
        file.setAttribute(
            'href',
            document.location.href.
                replace(
                    /source=frame&/,
                    'source=resource&type=css&target=parent&'
                )
        );
        parent.getElementsByTagName('head')[0].appendChild(file);

        // } Load iframe related CSS-file into parent document
        // Load state {

        var
            state = $.cookie(this.data.cookieName);

        if (state) {
            this.state = JSON.parse(state);
        }
        this.state = $.extend({}, this.data.defaults, this.state);

        // } Load state
        // Load panel template {

        var iframe = document.createElement('IFRAME');
        this.container.id = 'container';
        this.container.className = 'ui-widget-content';
        this.container.appendChild(iframe);
        document.body.appendChild(this.container);
        iframe.src =
            this.data.path.script + '?source=template&type=html&skin=' +
            this.state.skin + '&theme=' + this.state.theme +
            '&v=' + data.version;

        // } Load panel template
        // Initialize plugins {

        for (var plugin in $d.Plugins) {
            if (typeof($d.Plugins[plugin].startup) == 'function') {
                $d.Plugins[plugin].startup();
            }
        }

        // } Initialize plugins
    },

    /**
     * ...
     *
     * @return {void}
     * @todo   Describe
     */
    postStartup: function()
    {
        var id, elements, i, j, q, elems;

        // Set locales from dictionary
        for (i in this.dictionary) {
            $('.locale-' + i).attr('title', this.dictionary[i]);
        }
        // Set captions from dictionary/passed data
        for (i in this.data) {
            elements = $('.locale-' + i);
            for (j in elements) {
                var data = this.data[i];
                elements[j].innerHTML = data[0];
                if (typeof(data[1]) != 'undefined') {
                    if (typeof(this.dictionary[data[1]]) != 'undefined') {
                        elements[j].title =
                            this.dictionary[data[1]] + ' ' + elements[j].title;
                        elements[j].className += ' ' + data[1];
                    }
                    if (data[2]) {
                        elements[j].title += ' ' + data[2];
                    }
                }
            }
        }
        $('.visibleVersion').html($.parseHTML(this.data['visibleVersion']));

        // Cleanup memory
        this.dictionary = null;

        elements = {
            'dPanel': 'panel',
            'dTab':   'tab'
        };
        for (id in elements) {
            elems = $('#' + id);
            if (elems.length > 0) {
                this[elements[id]] = elems[0];
            }
        }

        // Call plugins post startup {

        for (var plugin in $d.Plugins) {
            if (typeof($d.Plugins[plugin].postStartup) == 'function') {
                $d.Plugins[plugin].postStartup();
            }
        }

        // } Call plugins post startup

        this.Panel.fixHeight();
    },

    storeState: function(index, value, checkFlag)
    {
        if (checkFlag && !$d.Panel.storeState) {
            return;
        }
        if (value != null) {
            this.state[index] = value;
        } else {
            delete this.state[index];
        }
        $.cookie(
            this.data.cookieName,
            JSON.stringify(
                // $(this.data.defaults).not(this.state).get()
                this.getObjectDifference(this.data.defaults, this.state)
            ),
            {path: this.data.path.cookie}
        );
    },

    keys: function(object)
    {
        var keys = [];
        for (key in object) {
            var type = typeof(object[key]);
            if (type != 'undefined' && type != 'function') {
                keys.push(key);
            }
        }
        return keys;
    },

    getObjectDifference: function (prev, now)
    {
        var
            isArray = now instanceof Array,
            changes = isArray ? [] : {},
            prop,
            pc;

        for (prop in now) {
            if (!prev || (prev[prop] !== now[prop])) {
                if (typeof(now[prop]) == 'object') {
                    pc = this.getObjectDifference(prev[prop], now[prop]);
                    if ($d.keys(pc).length > 0) {
                        changes[prop] = pc;
                    }
                } else {
                    changes[prop] = now[prop];
                }
            }
        }
        return changes;
    }
}

$d.Plugins =
{
}

$d.View =
{
    templates: {},

    /**
     * Load templates from element
     *
     * @param  {DOMElement} element
     * @param  {string}     mode 'merge'|'add'|'set'
     * @return {void}
     */
    load: function(element, mode/* = 'merge'*/)
    {
        if (typeof(mode) == 'undefined') {
            mode = 'merge';
        }

        if (mode == 'set') {
            this.templates = {};
        }

        var
            templates =
                element
                    ? $('.template', element)
                    : [],
            merge = mode != 'add';

        for (var i = 0, q = templates.length; i < q; i++) {
            var template = templates[i];

            if (
                template.id && (
                    merge ||
                    typeof(this.templates[template.id]) == 'undefined'
                )
            ) {
                this.templates[template.id] =
                    template.innerHTML.replace(/^\s*<!--\s*|\s*-->\s*$/g, '');
            }
        }
    },

    /**
     * Parses template
     *
     * @param  {string} id
     * @param  {object} args
     * @return {string}|{null}
     */
    parse: function(id, args)
    {
        if (typeof(this.templates[id]) == 'undefined') {
            return null;
        }
        if (typeof(args) == 'undefined') {
            args = {};
        }
        var template = this.templates[id];
        for (var key in args) {
            template = template.replace('{$' + key + '}', args[key]);
        }
        return template;
    }
}

$d.View.Locale =
{
    /**
     * @private
     */
    dictionary: {},

    /**
     * @param  {object} dictionary
     * @param  {string} mode        'merge'|'add'|'set'
     * @return {void}
     */
    add: function(dictionary, mode/* = 'merge'*/)
    {
        if (typeof(mode) == 'undefined') {
            mode = 'merge';
        }
        if (mode == 'set') {
            this.dictionary = dictionary;
        } else {
            var merge = mode == 'merge';
            for (var key in dictionary) {
                if (merge || typeof(this.dictionary[key]) == 'undefined') {
                    this.dictionary[key] = dictionary[key];
                }
            }
        }
    },

    /**
     * @param  {string} key
     * @param  {object} args
     * @return {string}|{null}
     */
    get: function(key, args)
    {
        var caption = null;

        if (typeof(this.dictionary[key])) {
            caption = this.dictionary[key];
            if (typeof(args) == 'object') {
                for (var name in args){
                    caption = caption.replace('{$' + name + '}', args[name]);
                }
            }
        }
        return caption;
    }
}

$d.View.Container =
{
    maxClientHeight: 0,

    /**
     * Display container: bar/panel
     *
     * @return {void}
     */
    display: function()
    {
        if (top.document.body.style.backgroundColor) {
            /*
            $d.container.style.borderTopColor =
                '#fff';
            $d.container.className = 'visible';
                //top.document.body.style.backgroundColor;
            */
            $d.container.style.borderBottomColor =
                top.document.body.style.backgroundColor;
        }

        $d.container.innerHTML =
            $d.View.parse('bar') +
            $d.View.parse(
                'panel',
                {
                    tabs: this.getTab(1, '1', $d.tabs)
                }
            );
        $d.postStartup();

        $($d.container).invisible();

        this.clickEachFirstTab($d.tabs, '');
/*
        $d.frame.style.height =
            (this.maxClientHeight - $d.container.clientTop + 4) + 'px';
*/

        // console.log(this.maxClientHeight);///

        if (typeof($d.state.top) != 'undefined') {
            $d.Panel.changePosition(
                $('.locale-barToBottom')[0]
            );
        }
        if ($d.state.visible) {
            $('#bottom-line').hide();
        } else {
            $d.Panel.toggleVisibility($('.locale-panelHide')[0]);
            if ('undefined' === typeof($d.state.visible)) {
                $('#bottom-line').show();
            }
        }
        if ($d.state.launch) {
            $d.launched = true;
        } else {
            $d.Panel.toggleClientLaunch($('.locale-panelOff')[0]);
            $('.locale-panelHide').hide();
            var td = $('.locale-reloadPage')[0];
            td.innerHTML = td.title;
            td.title = '';
        }

        if ($d.state.tab) {
            // Set active stored last tab including all its parent tabs
            var lastTab = $d.state.tab.split('|'), parentTabs = [];
            for (i in lastTab) {
                parentTabs.push(lastTab[i]);
                elements =
                    $('[tabid="' + parentTabs.join('|') + '"]', $d.panel);
                if (elements.length) {
                    $d.Panel.Tab.click(elements[0]);
                } else {
                    break;
                }
            }
        } else if ($d.tabs.tabs) {
            // mark active first alavilable tab
            for (i in $d.tabs.tabs) {
                elements = $('[tabid="' + i + '"]', $d.panel);
                if (elements.length) {
                    $d.Panel.Tab.click(elements[0]);
                }
                break;
            }
        }
/*
        $d.frame.style.height =
            ($d.container.clientHeight - $d.container.clientTop + 4) + 'px';
*/
        if (document.forms['settings']) {
            var skin = document.forms['settings'].elements['skin'];

            $.each(
                $d.data.skins,
                function(key, value)
                {
                    $(skin).append($('<option>', {value: key}).text(key));
                }
            );
            skin.value = $d.state.skin;
            $d.Panel.onSelectSkin(skin, $d.state.theme);
            $d.Panel.highlightSettings(skin.form, true);
            $d.Panel.setOpacity(0, $d.state.opacity);
        }
        $d.Panel.storeState = true;

        $($d.container).visible();
        $($d.container).show();

        $('#container').resizable({
            handles:    's',
            alsoResize: '#dTab',
            minHeight:  60, // @odo: avoid hardcode
            maxHeight:  parseInt($('.panel').css('max-height')) + 100, // @odo: avoid hardcode
            resize: function(event, ui)
            {
                $d.Panel.fixHeight();

                return true;
            }
        });

        $(
            function()
            {
                $.extend(
                    $.fn.disableTextSelect = function()
                    {
                        return
                            this.each(
                                function()
                                {
                                    if($.browser.mozilla){ // Firefox
                                        $(this).css('MozUserSelect', 'none');
                                    }else if($.browser.msie){ // IE
                                        $(this).bind(
                                            'selectstart',
                                            function()
                                            {
                                                return false;
                                            }
                                        );
                                    }else{ // Opera, etc.
                                        $(this).mousedown(
                                            function()
                                            {
                                                return false;
                                            }
                                        );
                                    }
                                }
                            );
                    }
                );
                $('.selectionDisabled').disableTextSelect();
            }
        );
    },

    clickEachFirstTab: function(tab, parentPrefix)
    {
        if (typeof(tab.tabs) != 'undefined') {
            parentPrefix = parentPrefix ? parentPrefix + '|' : '';
            var
                captions = $d.keys(tab.tabs);

            for (var i = 0, q = captions.length; i < q; i++) {
                this.clickEachFirstTab(
                    tab.tabs[captions[i]],
                    parentPrefix + captions[i]
                );
                this.maxClientHeight =
                    Math.max(this.maxClientHeight, $d.container.clientHeight);
            }

            var elements =
                $('[tabid="' + parentPrefix + captions[0] + '"]', $d.panel);
            if (elements.length) {
                $d.Panel.Tab.click(elements[0]);
            }
        }
    },

    /**
     * Returns tab HTML-code
     *
     * @param  {int} level
     * @param  {string} postfix
     * @param  {object} tab
     * @param  {string} parentPrefix
     * @return {string}
     */
    getTab: function(level, postfix, tab, parentPrefix)
    {
        if (!tab) {
            return '';
        }

        var
            content = '',
            tabId = parentPrefix ? parentPrefix : '';

        parentPrefix = parentPrefix ? parentPrefix + '|' : '';

        if (typeof(tab.content) != 'undefined') {
            content += $d.View.parse(
                'tabContent',
                {
                    level:   level,
                    postfix: postfix,
                    active:  true /* tab.active */ ? ' a' : '', ///
                    content: tab.content,
                    tabId:   tabId
                }
            );
        } else if (tab.tabs) {

            // tab controls {

            var
                captions = $d.keys(tab.tabs),
                classes, controls = '';

            for (var i = 0, q = captions.length; i < q; i++) {
                var
                    caption = captions[i],
                    active = i == (q - 1), ///tab.tabs[caption].active,
                    activeTail = '';

                if (!tab.tabs[caption]) {
                    continue;
                }

                classes = ['selectionDisabled'];
                if (!i) {
                    classes.push('l');
                }
                if ((q - i) == 1) {
                    classes.push('r');
                }
                if (active) {
                    classes.push('a');
                    activeTail = $d.View.parse(
                        'tabControlTail',
                        {
                            caption: caption
                        }
                    );
                }
                controls += $d.View.parse(
                    'tabControl',
                    {
                        classes:    classes.join(' '),
                        caption:    caption,
                        tabId:      parentPrefix + caption,
                        activeTail: activeTail
                    }
                );
            }

            content += $d.View.parse(
                'tabList',
                {
                    level:    level,
                    postfix:  postfix,
                    controls: controls,
                    tabId:    tabId
                }
            );

            // } tab controls

            if (1 === (level % 2)) {
                content += $d.View.parse('level1Opener');
            }

            var index = 0;

            for (var tabName in tab.tabs) {
                index++;
                content +=
                    this.getTab(
                        level,
                        postfix + '_' + index,
                        typeof(tab.tabs[tabName].content) != 'undefined'
                            ?  tab.tabs[tabName]
                            : {
                                content:
                                    this.getTab(
                                        level + 1,
                                        postfix + '_' + (level + 1) + '_' +
                                            index,
                                        tab.tabs[tabName],
                                        parentPrefix + tabName
                                    )
                            },
                        parentPrefix + tabName
                    );
            }

            if ((level % 2) == 1) {
                content += $d.View.parse('level1Closer');
            }
        }

        return content;
    }
}

$d.Panel =
{
    launched:    true,
    visible:     true,
    storeState:  false,
    onTop:       true,
    lastClientY: 0,

    tabSettingsClickisInterrupted: false,

    /**
     * Change bar & panel top/bottom position
     *
     * @param  {DOMElement} control
     * @return {bool}       Always false
     * @todo   Use my jQuery.iEvt plugin to calc height & etc.
     */
    changePosition: function(control)
    {
        var
            resizeLine = $('#resize-line')[0];

        this.onTop = !this.onTop;
        if (this.onTop) {
            $(resizeLine).addClass('resizable');
        } else {
            $(resizeLine).removeClass('resizable');
        }
        resizeLine.title =
            this.onTop ? $d.View.Locale.get('panelResize') : '';
        if (this.onTop) {
            // put to the top
            control.title = $d.View.Locale.get('barToBottom');
            $(control).removeClass('onBottom');
            $(control).addClass('onTop');
            $($d.container).removeClass('onBottom');
            $($d.container).addClass('onTop');
            $($d.frame).removeClass('onBottom');
            $($d.frame).addClass('onTop');
        } else {
            // put to the bottom
            control.title = $d.View.Locale.get('barToTop');
            $(control).removeClass('onTop');
            $(control).addClass('onBottom');
            $($d.container).removeClass('onTop');
            $($d.container).addClass('onBottom');
            $($d.frame).removeClass('onTop');
            $($d.frame).addClass('onBottom');
        }
        $d.frame.style.height =
            (
                $d.container.clientHeight - $d.container.clientTop +
                this.getFrameHeightDiff()
            ) +
            'px';
        control.blur();
        $d.storeState('top', this.onTop ? null : 0, true);
        return false;
    },

    /**
     * Toggle debugger launching
     *
     * @param  {DOMElement} control
     * @return {bool}                Always false
     */
    toggleClientLaunch: function(control)
    {
        var
            infoCell = $('.info', $d.container)[0],
            opacity = $d.state.opacity || 100;

        control.title =
            $d.View.Locale.get(this.launched ? 'panelOn' : 'panelOff');
        if (this.launched) {
            if (this.visible) {
                // hide panel
                this.toggleVisibility(infoCell);
            }
            $(infoCell.children[0]).hide();
            infoCell.title = '';
            $(infoCell).removeClass('resizable');
        } else {
            if ($d.panel) {
                $(infoCell.children[0]).show();
                infoCell.title = $d.View.Locale.get('panelView');
                $(infoCell).addClass('resizable');
            }
        }
        this.launched = !this.launched;
        $d.container.style.opacity =
            this.launched ? opacity / 100 : '0.5';
        control.blur();
        if (this.storeState) {
            $d.storeState('launch', this.launched ? 1 : null);
            if (!$d.launched) {
                $('.locale-reloadPage').show();
            }
        }
        return false;
    },

    /**
     * Toggle panel visibility
     *
     * @param  {DOMElement} cell
     * @return void
     */
    toggleVisibility: function(cell)
    {
        if (!this.launched || !$d.panel) {
            return;
        }

        this.visible ? $($d.panel).hide() : $($d.panel).show();
        if (this.visible) {
            // hide
            cell.title = $d.View.Locale.get('panelView');
            $d.container.style.height = 'auto';
        } else {
            // show
            cell.title = $d.View.Locale.get('panelHide');
        }

        $('#bottom-line').toggle();
        $('#resize-line').toggle();
        /*
        ///
        $d.frame.style.height =
            ($d.container.clientHeight - $d.container.clientTop + 4) + 'px';
        */

        this.visible = !this.visible;

        if (this.visible) {
            this.fixHeight();
        }

        $d.storeState('visible', this.visible ? 1 : null, true);
    },

    onSelectSkin: function(skin, forceTheme)
    {
        var i, q, theme = skin.form.elements['theme'];

        theme.length = 0;
        $.each(
            $d.data.skins[skin.value],
            function(key, value)
            {
                $(theme).append($('<option>', {value: value}).text(value));
            }
        );
        if (forceTheme) {
            theme.value = forceTheme;
        }
        $(theme.options[theme.selectedIndex]).addClass('selected');
        this.onSelectTheme(theme);
    },

    onSelectTheme: function(select)
    {
        if (!$d.Panel.storeState) {
            return;
        }
        var
            date = new Date(),
            node = document.createElement('LINK'),
            ts =
                document.location.href.
                replace(/^.*\?/, '').indexOf('&dev=1') < 0
                ? ''
                : '&dev=1&ts=' + date.getMilliseconds();

        node.setAttribute('rel', 'stylesheet');
        node.setAttribute('type', 'text/css');
        node.setAttribute('media', 'screen');
        node.setAttribute(
            'href',
            $d.data.path.script + '?source=resource&type=css&skin=' +
                select.form.elements['skin'].value + '&theme=' + select.value +
                '&v=' + $d.data.version + '&h=' + $d.data.hash + ts
        );
        document.getElementsByTagName('head')[0].appendChild(node);
    },

    highlightSettings: function(form)
    {
        var
            selectors = ['skin', 'theme']

        for (var i in selectors) {
            var select = form.elements[selectors[i]];
            for (var j = 0, q = select.options.length; j < q; j++) {
                if (j != select.selectedIndex) {
                    $(select.options[j]).removeClass('selected');
                } else {
                    $(select.options[j]).addClass('selected');
                }
            }
        }
    },

    setOpacity: function(difference, value)
    {
        var
            opacity = document.forms['settings'].elements['opacity'];

        if (difference == 0) {
            opacity.value = value;
        } else {
            opacity.value = parseInt(opacity.value) + difference;
            if (opacity.value < 0) {
                opacity.value = 0;
            } else if (opacity.value > 100) {
                opacity.value = 100;
            }
        }
        $('#container').css('opacity', opacity.value / 100);
        return false;
    },

    onTabSettingsTabClick: function(checkbox)
    {
        var
            value = checkbox.value,
            selector = "[value^='" + (value + '|').replace("'", "\'") + "']";

        if ($d.Panel._isTabSettingsCheckboxProtected(value)) {
            return false;
        }

        // Check possibility to click
        $(selector).each(
            function()
            {
                if ($d.Panel._isTabSettingsCheckboxProtected($(this)[0].value)) {
                    $d.Panel.tabSettingsClickisInterrupted = true;
                    return false;
                }
            }
        );
        if ($d.Panel.tabSettingsClickisInterrupted) {
            $d.Panel.tabSettingsClickisInterrupted = false;
            return false;
        }

        // Check children
        $(selector).each(
            function()
            {
                if (checkbox.checked) {
                    $(this).attr('saved-checked', $(this).prop('checked') ? 1 : 0);
                    $(this).prop('checked', true);
                } else {
                    $(this).prop('checked', $(this).attr('saved-checked') > 0 ? true : false);
                }
                $(this).prop('disabled', checkbox.checked);
            }
        );

        return true;
    },

    _isTabSettingsCheckboxProtected: function(value)
    {
        var
            result =
                'Debeetle|Settings' === value ||
                'Debeetle|Settings|Tabs' === value;

        if (result) {
            alert($d.View.Locale.get('tabSettingClickForbidden') + '!');
        }

        return result;
    },

    saveSettings: function(button)
    {
        var
            form = button.form,
            elements = form.elements;

        switch (form.name) {
            case 'settings':
                $d.storeState('skin', elements['skin'].value, true);
                $d.storeState('theme', elements['theme'].value, true);
                $d.storeState('opacity', elements['opacity'].value, true);
                this.highlightSettings(form);
                break; // case 'settings'

            case 'tabSettings':
                var
                    checkboxes = $('input[type="checkbox"]', form),
                    disabledTabs = [];

                for (var index in checkboxes) {
                    if (
                        checkboxes[index].checked &&
                        !checkboxes[index].disabled
                    ) {
                        disabledTabs.push(checkboxes[index].value);
                    }
                }
                $d.storeState('disabledTabs', disabledTabs, true);
                alert('Reload page to view changes.');
                break; // case 'tabSettings'
        }

        return false;
    },

    resetSettings: function(button)
    {
        var form = button.form;

        switch (form.name) {
            case 'settings':
                var buttons = ['skin', 'theme', 'opacity'];

                for (var i in buttons) {
                    button.form.elements[buttons[i]].value =
                        $d.state[buttons[i]];
                }
                this.onSelectSkin(button.form.elements['skin']);
                this.highlightSettings(button.form);
                $('#container').css('opacity', $d.state['opacity'] / 100);
                break; // case 'settings'
            case 'tabSettings':
                $('input[type="checkbox"]', form).each(
                    function()
                    {
                        $(this).prop(
                            'checked',
                            $(this).attr('source-checked') > 0 ? true : false
                        );
                        $(this).prop(
                            'disabled',
                            $(this).attr('source-disabled') > 0 ? true : false
                        );
                    }
                );
                alert('Reload page to view changes.');
                break; // case 'tabSettings'
        }

        return false;
    },

    /**
     * Limits panel max heigh
     *
     * @return {void}
     */
    fixHeight: function()
    {
        var offset = $("#dPanel").offset();

        offset = offset.top + parseInt(
            $(document.body).css('border-top-width').replace(/[a-z]+$/, '')
        );
        $('.panel')
            .css(
                'max-height',
                parseInt(
                    ($d.data.parentWindowHeight - offset - 2) *
                    $d.data.defaults.maxPanelHeight / 100
                )
            );
    }
}

$d.Panel.Tab =
{
    _lastTab: null,

    click: function(sourceControl, storeState)
    {
        var tabId = $(sourceControl).attr('tabid');

        if (tabId === this._lastTab) {
            // Same tab, no action
            return false;
        }

        if ('undefined' === typeof(storeState)) {
            storeState = true;
        }

        var
            tabChildren = $('[ptabid="' + tabId + '"]', $d.panel),
            caption = sourceControl.children[0].innerHTML,
            lastTab = tabId,
            lastTabLevel = tabId.split('|').length;

        // If last tab is'not parent of current tab hide prevoious tabs
        if (this._lastTab && tabId.indexOf(this._lastTab + '|') !== 0) {
            // .hide() doesn't work !!!
            $('[ptabid="' + this._lastTab + '"]', $d.panel).hide();
        }

        // Click all active subtabs
        $('li.a', tabChildren).each(function(){
            $d.Panel.Tab._lastTab = null;
            $d.Panel.Tab.click(this, false);
            if ($d.Panel.Tab._lastTab.split('|').length > lastTabLevel) {
                lastTab = $d.Panel.Tab._lastTab;
                lastTabLevel = lastTab.split('|').length;
            }
        });

        // Set visible all children
        tabChildren.show();

        // Recalc visible part height
        $d.Panel.fixHeight();

        // Set activity class and bold caption
        $(sourceControl)
            .addClass('a')
            .append('<div><div>' +  caption + '</div>');

        // All same level tabs loop
        $(sourceControl).parent().children().each(function()
        {
            var child = $(this);

            if (child.attr('tabid') !== tabId) {
                // Hide tab
                $('[ptabid="' + child.attr('tabid') + '"]', $d.panel).hide();
                // Normalize caption
                child.html(child.html().replace(/<div>.*/ig, ''));
                child.removeClass('a');
            }
        });

        this._lastTab = lastTab;
        if (storeState) {
            $d.storeState('tab', lastTab, true);
        }

        return false;
    }
}
