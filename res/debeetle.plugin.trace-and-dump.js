/**
 * JavaScript routines for Krumo
 *
 * Patched for Debeetle
 *
 * @version $Id: krumo.js 22 2007-12-02 07:38:18Z Mrasnika $
 * @link http://sourceforge.net/projects/krumo
 */

/////////////////////////////////////////////////////////////////////////////

/**
 * Krumo JS Class
 */
function krumo()
{
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

/**
 * Add a CSS class to an HTML element
 *
 * @param HtmlElement el
 * @param string className
 * @return void
 */
krumo.reclass = function(el, className)
{
    if (el.className.indexOf(className) < 0) {
        el.className += (' ' + className);
    }
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

/**
 * Remove a CSS class to an HTML element
 *
 * @param HtmlElement el
 * @param string className
 * @return void
 */
krumo.unclass = function(el, className)
{
    if (el.className.indexOf(className) > -1) {
        el.className = el.className.replace(className, '');
    }
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

/**
 * Toggle the nodes connected to an HTML element
 *
 * @param HtmlElement el
 * @return void
 */
krumo.toggle = function(el)
{
    var ul = el.parentNode.getElementsByTagName('ul');
    for (var i=0; i<ul.length; i++) {
        if (ul[i].parentNode.parentNode == el.parentNode) {
            ul[i].parentNode.style.display = (ul[i].parentNode.style.display == 'none')
            ? 'block'
            : 'none';
        }
    }

    // toggle class
    if (ul[0].parentNode.style.display == 'block') {
        krumo.reclass(el, 'krumo-opened');
    } else {
        krumo.unclass(el, 'krumo-opened');
    }

    $d.Panel.fixFrameHeight();
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

/**
 * Hover over an HTML element
 *
 * @param HtmlElement el
 * @return void
 */
krumo.over = function(el)
{
    krumo.reclass(el, 'krumo-hover');
}

// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --

/**
 * Hover out an HTML element
 *
 * @param HtmlElement el
 * @return void
 */
krumo.out = function(el)
{
    krumo.unclass(el, 'krumo-hover');
}

/////////////////////////////////////////////////////////////////////////////

$d.Plugins.TraceAndDump = {
    checkboxes: {
        hide_trace:        'tnd_t',
        hide_dump:         'tnd_d',
        expand_trace_args: 'tnd_eta',
        hide_trace_args:   'tnd_hta'
    },

    disableTraceArgs: null,

    /**
     * @static
     */
    postStartup: function()
    {
        var checkboxes = $d.Plugins.TraceAndDump.checkboxes;
        for (var name in checkboxes) {
            $('input[name="' + name + '"]')
                .attr('checked', $d.state[checkboxes[name]] ? true : false);
        }
        this.updatePanel();
        this.disableTraceArgs = $d.state['tnd_hta'] ? true : false;
    },

    click: function(legend)
    {
        var
            fieldset = legend.parentElement;

        legend.title =
            $d.View.Locale.get(
                $(fieldset).hasClass('invisible') ? 'hide' : 'show'
            );
        $(fieldset).toggleClass('invisible');
        return false;
    },

    onMouse: function(entity, add)
    {
        if (add) {
            $(entity).addClass('over');
        } else {
            $(entity).removeClass('over');
        }
    },

    onDisableTraceArgs: function(checkbox)
    {
        var expand = checkbox.form.elements['expand_trace_args'];

        if (checkbox.checked) {
            $(expand).attr('debee-checked', $(expand).prop('checked') ? '1' : '0');
            $(expand).prop('checked', false);
        } else if (typeof($(expand).attr('debee-checked')) != 'undefined') {
            $(expand).prop('checked', parseInt($(expand).attr('debee-checked')) ? true : false);
        }
        expand.disabled = checkbox.checked;
        return true;
    },

    saveSettings: function(button)
    {
        for (var i in this.checkboxes) {
            $d.storeState(
                this.checkboxes[i],
                button.form.elements[i].checked ? 1 : 0,
                true
            );
        }
        this.updatePanel();

        if (this.disableTraceArgs != $d.state['tnd_hta']) {
            alert('Reload page to get effect of "Disable trace arguments" setting');
        }

        return false;
    },

    resetSettings: function(button)
    {
        for (var i in this.checkboxes) {
            button.form.elements[i].checked =
                $d.state[this.checkboxes[i]] ? true : false;
        }
        this.updatePanel();

        return false;
    },

    updatePanel: function()
    {
        var
            elements = ['trace', 'dump'],
            hide, cssToClick;

        for (var i = 0, q = elements.length; i < q; i++) {
            hide = $d.state[this.checkboxes['hide_' + elements[i]]];
            cssToClick = hide ? 'block' : 'none';
            $('.' + elements[i]).each(
                function()
                {
                    var legend = $(this).children()[0];
                    if (
                        legend.tagName == 'LEGEND' &&
                        $($(this).children()[1]).css('display') == cssToClick
                    ) {
                        $d.Plugins.TraceAndDump.click(legend);
                    }
                }
            );
        }

        if ($d.state['tnd_eta']) {
            $('.trace-args').find('div[onclick]').each(
                function()
                {
                    $(this).click();
                }
            );
        }
    }
}
