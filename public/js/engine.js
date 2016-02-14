var listeners = {
  onReady:{},
  onLoad:{},
  onUnload:{},
  onTabChange:{}
},evDepth = 0;
var currentSelector,selectors = {},pinCalendar;

window.onload = function(){
  callListeners('onLoad',null,true);
}

function seleniaReady() {
  callListeners('onReady',null,true);
  if ($id('status'))
    setTimeout(function () {
      fadeOut($id('status'));
    },3000);
  onEvent(document.body,'click',closeSelector);
  for (var selectorId in selectors) {
    var linked = selectors[selectorId].linkedSelector;
    if (linked && selectors[selectorId].loadLinkedOnInit) {
      var v = getSelValue(selectorId);
      if (v !== '' && v !== null)
        loadSelector(linked,v,false);
    }
  }
}

seleniaReady();

function isIE() {
  return navigator.userAgent.indexOf('MSIE') > 0;
}

function fadeOut(e) {
  var alpha = 100;
  var i = setInterval(function () {
    alpha -= 10;
    if (alpha == 0) {
      e.style.display = 'none';
      e = null;
      clearInterval(i);
    }
    else if (isIE()) e.style.filter = 'alpha(opacity=' + alpha + ')';
    else e.style.opacity = alpha/100;
  },50);
}

function getListeners(eventName,target) {
  var l = listeners[eventName];
  if (!l) return alert("Unknown event: "+eventName);
  var t = l[target];
  if (!t) l[target] = t = [];
  return t;
}

function callListeners(eventName,target,args) {
  if (!target) target = undefined;
  var l = getListeners(eventName,target);
  for (var i=0; i<l.length; ++i) l[i](target,args);
}

function listen(eventName,fn,target) {
  var l = getListeners(eventName,target);
  if (l) l.push(fn);
}

function unlisten(eventName,fn,target) {
  if (!target) target = undefined;
  var l = getListeners(eventName,target);
  for (var n = 0; n < l.length; ++n)
    if (l[n] == fn) {
      l.splice(n,1);
      return;
    }
  alert("Can't unregister " + eventName + " listener:\n" + fn);
}

function $id(id) {
  return document.getElementById(id);
}

function $f(name) {
  if (name) return document.forms[0][name];
  return document.forms[0];
}

function exists(name) {
  return name in window;
}

function unset(name) {
  window[name] = undefined;
}

function go(url,ev) {
  location = url;
  if (ev) stopPropagation(ev);
}

function doAction(name,param) {
  $f('_action').value = name + (param? ':' + param : '');
  $f().action = location.href; //update url (ex. the hash may have changed)
  if (Form_onSubmit())
    $f().submit();
}

/** In the expression, use $fieldName for an optional value and #fieldName for a required one. */
function evalFormBindingExpr(exp) {
  var abort = false;
  var r = exp.replace(/(\$|\#)(\w+)/g,function(e,op,name) {
    var v = $f(name).value;
    if (op == '#' && (v === '' || v === null))
      abort = true;
    return v;
  });
  return abort ? null : r;
}

function onEvent(e,evName,fn) {
  if (e['addEventListener']) {
    e.addEventListener(evName,fn,false);
    return true;
  }
  else if (e['attachEvent'])
    return e.attachEvent('on' + evName,function() {
      event.target = event.srcElement;
      fn(event);
    });
  else e['on' + evName] = fn;
}

function stopPropagation(ev) {
  if (window.event && window.event.cancelBubble != undefined)
    window.event.cancelBubble = true;
  if (ev && ev.stopPropagation)
    ev.stopPropagation();
}

function preventDefault(ev) {
  if (window.event && window.event.returnValue != undefined)
    window.event.returnValue = false;
  if (ev && ev.preventDefault)
    ev.preventDefault();
}

function Form_onSubmit(ev)
{
  // HTML5 native validation integration.
  if ('validateInput' in window)
    $('input,textarea,select').each(function(){ validateInput(this) });
  if (!$f().checkValidity()) {
    setTimeout (function () {
      var e = document.activeElement;
      if (!e) return;
      var lang = $(e).attr('lang');
      if (lang) setLang (lang, e);
    },1);
    return false;
  }

  if (window.onSubmit) return window.onSubmit($f());
  return $f('_action').value != '';
}

function $form(e) {
  if(e.form) return e.form;
  while(e=e.parentNode)
    if (e.nodeName=='FORM') return e;
  return null;
}

function ImageField_blank(uid,className) {
  var i=NthChild($id(uid), 1);
  i.innerHTML = '';
  i.className = 'emptyImg'+(className?' '+className:'');
  return i;
}

function ImageField_clear(uid)
{
  ImageField_blank(uid);
  $id(uid+'Field').value='';
  e=$id(uid+'File');
  if (e.outerHTML) e.outerHTML=e.outerHTML;
  else e.value='';
  Button_disable(uid+'Clear');
}

function ImageField_onChange(uid)
{
  var e=ImageField_blank(uid,'upload');
  var src=$id(uid+'File').value;
  if (!src.match(/.jpg$|.jpeg$|.png$|.gif$|.bmp$/i))
    alert("Atenção!\n\nVerifique se o ficheiro seleccionado\nse encontra no formato JPEG, PNG, GIF ou BMP!");
  src=src.match(/(?:\\|\/|^)([^\\\/]*)$/);
  e.innerHTML='<div class="label">'+src[1]+'</div>';
  Button_enable(uid+'Clear');
}

function VideoField_blank(uid,className) {
  var i=NthChild($id(uid),1);
  var e=document.createElement('DIV');
  e.className='emptyVid'+(className?' '+className:'');
  i.replaceChild(e,NthChild(i,0));
  return e;
}

function VideoField_clear(uid)
{
  VideoField_blank(uid);
  $id(uid+'Field').value='';
  e=$id(uid+'File');
  if (e.outerHTML) e.outerHTML=e.outerHTML;
  else e.value='';
  Button_disable(uid+'Clear');
}

function VideoField_onChange(uid)
{
  var e=VideoField_blank(uid,'upload');
  var src=$id(uid+'File').value;
  if (!src.match(/.flv$|.swf$/i))
    alert("Atenção!\n\nVerifique se o ficheiro seleccionado\nse encontra no formato FLV ou SWF!");
  src=src.match(/(?:\\|\/|^)([^\\\/]*)$/);
  e.innerHTML='<div class="label">'+src[1]+'</div>';
  Button_enable(uid+'Clear');
}

function FileUpload_clear(uid)
{
  $id(uid+'Field').value='';
  $id(uid+'File').value='';
  $id(uid+'InputField').value='';
  Button_disable(uid+'Clear');
}

function FileUpload_onChange(uid)
{
  var path=$id(uid+'File').value.match(/(?:\\|\/|^)([^\\\/]*)$/);
  $id(uid+'InputField').value=path[1];
  Button_enable(uid+'Clear');
}

function Button_onConfirm(action,msg)
{
  if (confirm(msg))
    doAction(action);
}

function Button_enable(id) {
  var button=$id(id);
  if (!Button_isEnabled(id)) {
    button.disabled=false;
    button.className=button.className.replace(/ ?disabled/,"");
  }
}

function Button_disable(id) {
  var button=$id(id);
  if (Button_isEnabled(id)) {
    button.disabled=true;
    button.className=button.className+=" disabled";
  }
}

function Button_isEnabled(id) {
  return !$id(id).disabled;
}

function saveScrollPos(form) {
  form.elements.scroll.value=document.getElementsByTagName("HTML")[0].scrollTop
  + document.body.scrollTop;
}

function scroll(y) {
  if (y==undefined) y=9999;
  setTimeout(function(){
    document.getElementsByTagName("HTML")[0].scrollTop = y;
    if (document.getElementsByTagName("HTML")[0].scrollTop != y)
      document.body.scrollTop = y;
  },1);
}

function ActiveElement_init() {
  this.onmousedown=Active_down;
  this.onmouseup=Active_up;
}

function Active_down() {
  this.className+=" active";
  this.setCapture();
}

function Active_up() {
  this.releaseCapture();
  this.className=this.className.replace(/ ?active/,"");
}

function Button_init() {
  this.hideFocus=true;
  this.onmousedown=Button_down;
  this.onmouseup=Button_up;
  this.onkeydown=Button_keyDown;
  this.onkeyup=Button_keyUp;
  this.onblur=Button_blur;
  this.onfocus=Button_focus;
}

function Button_down() {
  this.firstChild.className=this.firstChild.className+=" active";
  this.setCapture();
}

function Button_up() {
  this.releaseCapture();
  this.firstChild.className=this.firstChild.className.replace(/ ?active/,"");
}

function Button_keyDown() {
  if(event.keyCode==13||event.keyCode==32) Button_down.call(this);
}

function Button_keyUp() {
  if(event.keyCode==13||event.keyCode==32) {
    Button_up.call(this);
    this.click();
  }
}

function Button_focus() {
  this.firstChild.className=this.firstChild.className+=" focus";
}

function Button_blur() {
  this.firstChild.className=this.firstChild.className.replace(/ ?focus/,"");
}

function Tab_init() {
  this.onmouseover=Tab_over;
  this.onmouseout=Tab_out;
}

function Tab_over() {
  if (this.className.indexOf("selected")<0)
    this.className+=" hover";
}

function Tab_out() {
  this.className=this.className.replace(" hover","");
}

function Tab_change(tabFld,tabsId,url) {
  var hasPages = false;
  Tabs_enumerate(tabsId,function(i,tab,tabFld,page) {
    tab.className=tab.className.replace(/ ?selected| ?hover/g,"");
    if (page) hasPages = true;
  });
  if (tabFld)
    addClass(tabFld.parentNode,'selected');
  if (url) go(url);
  else if (hasPages) //page display may take some time
    setTimeout(function() {
      Tabs_enumerate(tabsId,function(i,tab,tabFld,page) {
        if (!tabFld.checked) Tab_hidePage(page);
      });
      Tabs_enumerate(tabsId,function(i,tab,tabFld,page) {
        if (tabFld.checked) {
          if (Tab_showPage(page) && tabsId)
            callListeners('onTabChange',tabsId,[i]);
          return 1;
        }
      });
    },1);
}

function Tab_showPage(page) {
  if (page && page.className.indexOf('TabPage_selected') < 0) {
    addClass(page,'TabPage_selected');
    if (!page.firstChild) {
      ++evDepth;
      var html = window[page.id + 'Content'];
      page.innerHTML = html;
      var match,r = new RegExp('<script.*?>([\\s\\S]*?)<\\/script>','g'),f=false;
      while (match = r.exec(html))
        globalEval(match[1]);
      if (evDepth == 1) callListeners('onLoad',page.id);
      --evDepth;
    }
    return true;
  }
  return false;
}

function Tab_hidePage(page) {
  if (page && page.className.indexOf('TabPage_selected') >= 0) {
    //callListeners('onUnload',null,false);
    removeClass(page,'TabPage_selected');
  //page.innerHTML = '';
  }
}

function Tabs_getSelectedIndex(tabsId) {
  return Tabs_enumerate(tabsId,function(idx,tab,tabFld) {
    if (tabFld.checked) return parseInt(tabFld.value);
  });
}

function Tabs_setSelectedIndex(tabsId,i) {
  return Tabs_enumerate(tabsId,function(idx,tab,tabFld) {
    if (i == idx) {
      tabFld.checked = true;
      Tab_change(tabFld,tabsId);
      return;
    }
  });
}

function Tabs_enumerate(tabsId,fn) {
  for (var i = 0;;++i) {
    tab = $id(tabsId + 'Tab' + i + "Field");
    if (!tab) break;
    else {
      var r = fn(i,tab.parentNode,tab,NthChild($id(tabsId + 'Pages'),i));
      if (r != undefined) return r;
    }
  }
  return null;
}

function NthChild(e,n) {
  if (e) {
    var c = e.firstChild;
    while (c) {
      if (c.nodeType == 1 && n-- == 0) return c;
      c = c.nextSibling;
    }
  }
}

function getPathToSelf() {
  var myName=/^(.*?)engine\.js/;
  var scripts=document.getElementsByTagName("script");
  for (var i=0;i<scripts.length;i++) {
    var src=scripts[i].getAttribute("src");
    if (src) {
      var m=src.match(myName);
      if (m) return m[1];
    }
  }
}

function FCKeditor_OnComplete(fck) {
  fck.EditorWindow.parent.document.body.style.visibility = '';
}

function number(n,defaultValue) {
  n = parseFloat(n);
  if (isNaN(n)) return defaultValue;
  return n;
}

function addClass(e,className) {
  removeClass(e,className);
  e.className += ' ' + className;
}

function removeClass(e,className) {
  e.className=e.className.replace(new RegExp(' ?' + className),'');
}

function setBookmark(url,title) {
  if (window.sidebar) // Mozilla Firefox Bookmark
    window.sidebar.addPanel(title,url,"");
  else if( window.external ) // IE Favorite
    window.external.AddFavorite(url,title);
  else alert('To bookmark this site you need to do so manually through your browser.');
}

function checkKeybAction(event,action) {
  if (event.keyCode == 13) setTimeout(function(){
    doAction(action);
  },1);
}

function nop() {}

var LIdragging = false,LIx,LIy,LItem;

function startLIDrag(ev) {
  LIdragging = true;
  onEvent(document.body,'mousemove',LIDrag);
  onEvent(document.body,'mouseup',stopLIDrag);
  LItem = ev.target.parentNode.parentNode.parentNode;
  for (var n = 0; n < LItem.cells.length; ++n) {
    LItem.cells[n].style.width = (LItem.cells[n].firstChild.clientWidth ? LItem.cells[n].firstChild.clientWidth : LItem.cells[n].clientWidth) + 'px';
    LItem.cells[n].style.height = LItem.cells[n].clientHeight + 'px';
  }
  LItem.style.position = 'absolute';
  LIx = ev.x;
  LIy = ev.y;
  moveLI(LIx,LIy);
  preventDefault(ev);
  stopPropagation(ev);
}

function stopLIDrag(ev) {
  LIdragging = false;
  document.body.removeEventListener('mousemove',LIDrag);
  document.body.removeEventListener('mouseup',stopLIDrag);
  moveLI(0,0);
  LItem.style.position = '';
  LItem = null;
  preventDefault(ev);
  stopPropagation(ev);
}

function LIDrag(ev) {
  if (LIdragging)
    moveLI(ev.x,ev.y);
}

function moveLI(x,y) {
  //LItem.style.marginLeft = (x - LIx) + 'px';
  LItem.style.marginTop = (y - LIy) + 'px';
}

function skipWhitespace(elem) {
  while (elem && elem.nodeType == 3)
    elem = elem.nextSibling;
  return elem;
}

function getChild(elem,index) {
  if (!elem)
    return null;
  var e = skipWhitespace(elem.firstChild);
  while (index-- && e)
    e = skipWhitespace(e.nextSibling);
  return e;
}

function textValue(elem) {
  return elem.innerText ? elem.innerText : elem.text;
}

function openSelector(id) {
  if (currentSelector != id)
    setTimeout(function() {
      currentSelector = id
      },1);
  var selBody = $id(id);
  selBody.style.zIndex = 9999;
  var list = getChild(selBody,2);
  list.style.display = 'block';
  var li = getChild(list,0);
  var i = getChild(li,0);
  while (i) {
    if (i.className == 'selected') {
      var y = i.offsetTop - 1;
      li.scrollTop = y > list.offsetHeight - i.offsetHeight ? y : 0;
      break;
    };
    i = skipWhitespace(i.nextSibling);
  }
}

function closeSelector() {
  if (currentSelector) {
    var selBody = $id(currentSelector);
    selBody.style.zIndex = '';
    var list = getChild(selBody,2);
    list.style.display = '';
    currentSelector = null;
  }
}

function setSel(e,value) {
  var label = textValue(e);
  var list = e.parentNode;
  var selector = list.parentNode.parentNode;
  var id = selector.getAttribute('id');
  setSelValue(id,value,label);
}

function setSelValue(id,value,label,noChangeEvent) {
  var selector = $id(id);
  var inputDiv = getChild(selector,1);
  var input = getChild(getChild(inputDiv,0),0);
  var li = findSelectorItem(id,value);
  input.value = li ? label : (selectors[id].prompt ? selectors[id].prompt : selectors[id].emptyLabel);
  var hidden = getChild(selector,3);
  hidden.value = li ? value : '';
  unselectSelectorList(id);
  if (li)
    li.className = 'selected';
  if (selectors[id]) {
    if (!noChangeEvent && selectors[id].onChange)
      eval(selectors[id].onChange);
    var l = selectors[id].linkedSelector;
    if (l)
      loadSelector(l,value,selectors[id].autoOpenLinked);
  }
}

function getSelValue(id) {
  var hidden = getChild($id(id),3);
  return hidden && hidden.value;
}

function getSelLabelFor(id,value) {
  var e = findSelectorItem(id,value);
  if (e) return e.innerHTML;
  return null;
}

function enableSelector(id,enabled) {
  var selector = $id(id);
  if (enabled)
    removeClass(selector,'disabled');
  else addClass(selector,'disabled');
  var inputDiv = getChild(selector,1);
  if (enabled)
    removeClass(inputDiv,'disabled');
  else addClass(inputDiv,'disabled');
  var input = getChild(getChild(inputDiv,0),0);
  input.disabled = !enabled;
  var button = getChild(selector,0);
  if (enabled)
    removeClass(button,'disabled');
  else addClass(button,'disabled');
  button.disabled = !enabled;
  if (enabled)
    return;
  var l = selectors[id].linkedSelector;
  if (l)
    enableSelector(l,false);
}

function findSelectorItem(id,value) {
  var selector = $id(id);
  var list = getChild(getChild(selector,2),0);
  var li = getChild(list,0);
  while (li) {
    var v = li.getAttribute('value');
    if (v == value)
      return li;
    li = skipWhitespace(li.nextSibling);
  }
  return null;
}

function unselectSelectorList(id) {
  var selector = $id(id);
  var list = getChild(getChild(selector,2),0);
  var li = getChild(list,0);
  while (li) {
    li.className = null;
    li = skipWhitespace(li.nextSibling);
  }
}

function setSelectorList(id,content) {
  var selector = $id(id);
  var list = getChild(getChild(selector,2),0);
  list.innerHTML = content;
  var v = selectors[id].value;
  if (v) {
    selectors[id].value = null;
    setSelValue(id,v,getSelLabelFor(id,v),true);
  }
}

function loadSelector(id,value,autoOpen) {
  setSelValue(id,'',selectors[id].prompt ? selectors[id].prompt : selectors[id].emptyLabel,true);
  enableSelector(id,false);
  var exp = selectors[id].sourceUrl;
  var url = evalFormBindingExpr(exp); //returns null if required values are missing
  if (url != null) {
    if (exp == url)
      url += value;
    jQuery.get(url,null,function(data) {
      data = eval(data);
      if (!(data instanceof Array))
        return;
      var html = !selectors[id].emptySelection ? '' :
      '<a onclick="setSel(this,\'\')" title="' +selectors[id].emptyLabel + '" href="javascript:nop()">' + selectors[id].emptyLabel + '</a>';
      var label,value;
      for (var i = 0; i < data.length; ++i) {
        if (data[i] instanceof Array) {
          value = data[i][0];
          label = data[i][1];
        }
        else label = value = data[i];
        html += '<a value="' + value + '" onclick="setSel(this,\'' + value + '\')" title="' + label + '" href="javascript:nop()">' + label + '</a>';
      }
      setSelectorList(id,html);
      if (data.length) {
        enableSelector(id,true);
        if (autoOpen)
          openSelector(id);
      }
      else enableSelector(id,false);
    });
  }
}

function preventOpeningCalendar(cal) {
  var f = cal.openCalendar;
  cal.openCalendar = nop;
  setTimeout(function() {
    cal.openCalendar = f;
  },1);
}

//for nogray
function onPopupCalPrePick(name) {
  pinCalendar = true;
  setTimeout(function() {
    $f(name).focus();
    pinCalendar = false;
  },1);
}

function onInputCalSelect(id,name) {
  if (exists(id))
    window[id].closeCalendar();

}

function spinUp(name,max) {
  var f = $f(name);
  if (f.disabled || f.readOnly)
    return;
  var v = parseInt(f.value);
  var o = v;
  if (isNaN(v)) v = 0;
  v += 1;
  if (max != '' && v > max)
    v = max;
  f.value = v;
  if (v != o && f.onchange)
    f.onchange();
  spinStop(name);
}

function spinDown(name,min) {
  var f = $f(name);
  if (f.disabled || f.readOnly)
    return;
  var v = parseInt(f.value);
  var o = v;
  if (isNaN(v)) v = 0;
  v -= 1;
  if (min != '' && v < min)
    v = min;
  f.value = v;
  if (v != o && f.onchange)
    f.onchange();
  spinStop(name);
}

function spinStart(name) {
  var f = $f(name);
  addClass(f,'focus');
}

function spinStop(name) {
  var f = $f(name);
  removeClass(f,'focus');
  f.focus();
}

function spinCheck(name,min,max) {
  var f = $f(name);
  if (f.disabled || f.readOnly)
    return;
  var v = parseInt(f.value);
  var o = v;
  if (isNaN(v)) v = 0;
  if (max != '' && v > max)
    v = max;
  if (min != '' && v < min)
    v = min;
  f.value = v;
  if (v != o && f.onchange)
    f.onchange();
}

var globalEval = function globalEval(src) {
  if (window.execScript)
    window.execScript(src);
  else window.eval(src);
};

function loadScript(src) {
  var script = document.createElement("script");
  script.type = "text/javascript";
  script.src = src
  document.body.appendChild(script);
}

function onInlineImageInsert(obj,data) {
  var path = typeof data == "string" ?
               data.match(/src="([^"]*)"/)[1]
             : data.filelink,
      file = path.substr(path.lastIndexOf('/')),
      img = obj.$editor.find('img[src*="'+file+'"]'),
      w = img.width(),
      h = img.height(),
      vw = obj.$editor.innerWidth();
  if (w > vw)
    img.css('height',Math.floor(vw * h / w));
}
