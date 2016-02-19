function nop () {}

!function () {

  // Private vars

  var topics = {};

  // The Selenia API

  window.selenia = {

    prevFocus: $ (),
    lang: '',

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

    doAction: function (name, param) {
      var form = $ ('form')[0];
      $ (form).find ('input[name=_action]').val (name + (param ? ':' + param : ''));
      form.action = location.href; //update url (ex. the hash may have changed)
      if (selenia.onSubmit ()) {
        form.submit ();
        $ (form).find ('button,input[type=button],input[type=submit]').prop ('disabled', true);
        return true;
      }
      return false;
    },

    onSubmit: function (ev) {
      var i18nInputs = $ ('input[lang]');
      i18nInputs.addClass ('validating'); // hide but allow field validation
      // HTML5 native validation integration.
      if ('validateInput' in window)
        $ ('input,textarea,select').each (function () { validateInput (this) });
      var form  = $ ('form')[0];
      var valid = form.checkValidity ();
      if (!valid) {
        setTimeout (function () {
          var e = document.activeElement;
          i18nInputs.removeClass ('validating'); // restore display:none state
          if (!e) return;
          var lang = $ (e).attr ('lang');
          if (lang) selenia.setLang (lang, e);
        }, 0);
        return false;
      }
      i18nInputs.removeClass ('validating'); // restore display:none state
      if (window.onSubmit) return window.onSubmit (form);
      return true;
    },

    go: function (url, ev) {
      window.location = url;
      if (ev) stopPropagation (ev);
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

    // Multilingual fields support

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

  // Memorize the previously focused input.

  $ ('body').focusout (function (ev) {
    if (ev.target.tagName == 'INPUT' || ev.target.tagName == 'TEXTAREA')
      selenia.prevFocus = $ (ev.target);
    else selenia.prevFocus = $ ();
  });

} ();
