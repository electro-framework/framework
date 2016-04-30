function nop () {}

function bootSeleniaClient () {
  if (!('$' in window))
    return console.error ("jQuery is required");

  // Private vars

  var
    /** Pub/Sub topics registry */
    topics = {},
    /** The #selenia-form jQuery element */
    form;

  // The Selenia API

  window.selenia = {

    prevFocus: $ (),
    lang:      '',
    /**
     * Extensions and components plug-in into this namespace.
     */
    ext:       {},

    /**
     * Selenia's Pub-Sub system.
     * @param {string} id Topic ID.
     * @returns {Object} A topic interface.
     */
    topic: function (id) {
      var callbacks
        , topic = id && topics[id];

      if (!topic) {
        callbacks = jQuery.Callbacks ();
        topic     = {
          send:        callbacks.fire,
          subscribe:   callbacks.add,
          unsubscribe: callbacks.remove
        };
        if (id)
          topics[id] = topic;
      }
      return topic;
    },

    /**
     * Listens for a Selenia client-side event.
     * @param topic
     * @param handler
     * @returns {selenia}
     */
    on: function (topic, handler) {
      this.topic (topic).subscribe (handler);
      return this;
    },

    /**
     * Sets the POST action and submits the form.
     * @param {string} name
     * @param {string} param
     */
    doAction: function (name, param) {
      selenia.setAction (name, param);
      form.submit ();
    },

    /**
     * Sets the POST action for later submission.
     * @param {string} name
     * @param {string} param
     */
    setAction: function (name, param) {
      form.find ('input[name=selenia-action]').val (name + (param ? ':' + param : ''));
    },

    /**
     * Returns the POST action currently set for submission.
     * @returns {*}
     */
    getAction: function () {
      return form.find ('input[name=selenia-action]').val ().split (':')[0];
    },

    /**
     * Is invoked before #selenia-form is submitted.
     * Return false to cancel the submission.
     * @param {Event} ev
     * @returns {boolean|*}
     */
    onSubmit: function (ev) {
      // Re-enable all buttons if form sbmission is aborted.
      setTimeout (function () {
        if (ev.isDefaultPrevented ())
          selenia.enableButtons (true);
      });
      // Disable all buttons while for is being submitted.
      selenia.enableButtons (false);
      return selenia.getAction () != 'submit' || selenia.validateForm ();
    },

    /**
     * Validates all form inputs that have validation rules.
     * @returns {boolean} true if the for is valid and can be submitted.
     */
    validateForm: function () {
      var i18nInputs = $ ('input[lang]');
      i18nInputs.addClass ('validating'); // hide inputs but allow field validation

      // HTML5 native validation integration.
      // Note: validateInput() is provided by the Input component.
      var inputs = $ ('input,textarea,select');
      inputs.each (function () {
        $ (this).parents ('.Field').find ('.help-block').remove ();
      });
      if (selenia.validateInput)
        inputs.each (function () { selenia.validateInput (this) });
      var valid = form[0].checkValidity ();
      if (!valid) {
        var first = true;
        setTimeout (function () {
          inputs.each (function () {
            if (!this.checkValidity ()) {
              if (first) {
                $ (this).focus ();
                first    = false;
                var lang = $ (this).attr ('lang');
                if (lang) selenia.setLang (lang, this);
              }
              var h = $ (this).parents ('.Field').find ('.help-block');
              if (!h.length)
                h = $ (this).parents ('.Field').append ('<span class="help-block"></span>').find ('.help-block');
              h.text (this.validationMessage);
            }
          });

          var e = document.activeElement;
          i18nInputs.removeClass ('validating'); // restore display:none state
          if (!e) return;
        }, 0);
        return false;
      }
      // restore display:none state
      i18nInputs.removeClass ('validating');
      return true;
    },

    /**
     * Disables or re-enables all buttons, but re-enables only those that were not previously disabled.
     * @param {boolean} enable
     */
    enableButtons: function (enable) {
      form.find ('button,input[type=button],input[type=submit]').each (function () {
        var btn      = $ (this)
          , disabled = btn.prop ('disabled');
        if (enable) {
          if (this.wasDisabled)
            return delete this.wasDisabled;
        }
        else this.wasDisabled = disabled;
        btn.prop ('disabled', !enable);
      });
    },

    go: function (url, /*Event*/ ev) {
      window.location = url;
      if (ev) ev.stopImmediatePropagation();
    },

    saveScrollPos: function (form) {
      form.elements.scroll.value = document.getElementsByTagName ("HTML")[0].scrollTop
        + document.body.scrollTop;
    },

    scroll: function (y) {
      if (y == undefined) y = 9999;
      setTimeout (function () {
        document.getElementsByTagName ("HTML")[0].scrollTop = y;
        if (document.getElementsByTagName ("HTML")[0].scrollTop != y)
          document.body.scrollTop = y;
      }, 1);
    },

    /**
     * Changes the active language for multilingual form inputs.
     * @param {string} lang
     * @param {boolean} inputsGroup
     */
    setLang: function (lang, inputsGroup) {
      selenia.lang = lang;

      var c = $ ('body')
        .attr ('lang', lang); //not currently used

      c.find ('[lang]').removeClass ('active');
      c.find ('[lang="' + lang + '"]').addClass ('active');

      // Focus input being shown.
      if (inputsGroup)
        $ (inputsGroup).find ('[lang=' + lang + ']').focus ();

      else {
        // Restore the focus to the previously focused element.
        if (selenia.prevFocus.attr ('lang'))
          selenia.prevFocus.parent ().find ('[lang="' + lang + '"]').focus ();
        else selenia.prevFocus.focus ();
      }

      selenia.topic ('languageChanged').send (lang);
    }

  };

  (function initSelenia () {

    // Memorize the previously focused input.
    var body = $ ('body')
      .focusout (function (ev) {
        if (ev.target.tagName == 'INPUT' || ev.target.tagName == 'TEXTAREA')
          selenia.prevFocus = $ (ev.target);
        else selenia.prevFocus = $ ();
      });

    form = $ ('<form id="selenia-form" method="post" action="' + location.pathname + '" novalidate></form>')
      .submit (selenia.onSubmit)
      .append ('<input type="hidden" name="selenia-action" value="submit">')
      .append (body.children (':not(script)'))
      .prependTo (body);
  }) ();

}
