/**
 * Pie namespace/module/singleton
 *
 * Used to access functionality for Pie front ends.
 * Some methods of this module were taken from Douglas Crockford's website.
 */
var Pie = function () {
	
	// private properties
	var m_isReady = false;
	var me = {};
	
	// private methods
	
	// constructor
	
	// public:
	me.tools = {};	
	me.constructors = {};
	me.afterActivate = {};
	me.afterReady = {};
	
	/**
	 * Call from the "ready" event from your favorite JS framework.
	 * @param options
	 *  An object containing any options
	 */
	me.ready = function(options) {
		var body = document.getElementsByTagName('body')[0];
		Pie.activate(body);
		// Call the functions meant to be called after the tools are activated
		me.handle(me.afterActivate);
		for (var prefix in this.tools) {
			try {
				if (!('ready' in this.tools[prefix])) {
					throw new Exception(
						"Tool with prefix " + prefix 
						+ "lacks ready(options) function."
					);
				}
				this.tools[prefix].ready(options);
			} catch (e) {
				// don't let errors escape, but log them
				if (typeof(console) != 'undefined') {
					console.warn(e);
				}
			}
		}
		// Call the functions meant to be called after ready() is done
		me.handle(me.afterReady);
		m_isReady = true;
	};
	
	/**
	 * Returns whether Pie.ready() has been called
	 */ 
	me.isReady = function() {
		return m_isReady;
	};
	
	/**
	 * Extends a string or object to be used with AJAX
	 * @param what
	 *  If a string, then treats it as a URL and
	 *  appends ajax fields to the end of the querystring.
	 *  If an object, then adds properties to it.
	 * @param slots
	 *  If a string, expects a comma-separated list of slots
	 *  If an object, converts it to a comma-separated list
	 * @param echo
	 *  Optional. A string to echo back. Used to keep track
	 *  of responses, for example in conjunction with timestamps.
	 * @return String|Object
	 *  Returns the extended string or object
	 */
	me.ajaxExtend = function(what, slots, echo) {
		if (!what) {
			if (console && ('warn' in console)) {
				console.warn('Pie.ajaxExtend received empty url');
			}
			return;
		}
		var slots2, k;
		if (typeof(slots) == 'string') {
			slots2 = slots;
		} else {
			slots2 = "";
			for (k in slots) {
				if (slots2 > "") {
					slots2 += ",";
				}
				slots2 += k;
			}
		}
		var timestamp = (new Date()).getTime();
		if (typeof(what) == 'string') {
			if (what.indexOf('?') < 0) {
				what2 = what + '?';
			} else {
				what2 = what;
			}
			what2 += encodeURI('&_pie[ajax]=JSON')
			 + encodeURI('&_pie[timestamp]=') + encodeURIComponent(timestamp)
			 + encodeURI('&_pie[slotNames]=') + encodeURIComponent(slots2);
			if (typeof(echo) != 'undefined') {
				what2 += encodeURI('&_pie[echo]=') + encodeURIComponent(echo);
			}
			if ('nonce' in Pie) {
				what2 += encodeURI('&_pie[nonce]=') + encodeURIComponent(Pie.nonce);
			}
		} else {
			// assume it's an object
			what2 = {};
			for (k in what) {
				what2[k] =  what[k];
			}
			what2._pie = {
				"ajax": "JSON",
				"timestamp": timestamp,
				"slotNames": slots2
			};
			if (typeof(echo) != 'undefined') {
				what2._pie['echo'] = echo;
			}
			if ('nonce' in Pie) {
				what2._pie['nonce'] = Pie.nonce;
			}
		}
		return what2;
	}
	
	/**
	 * Replaces an element in the DOM with some other content.
	 * The replaced element will be completely mangled and
	 * removed from the DOM, so don't use it afterward.
	 * 
	 * @param String|DOMNode|DOMNode element
	 *  This can either be an HTML or FBML node, or 
	 *  If this is a string, the element is obtained via 
	 *  document.getElementById on that string.
	 *
	 * @param String|DomNode|FBMLString|FBDOMNode replacement
	 *  The HTML or FBML string -- obtained, for example, from an AJAX call.
	 *  If this DOM Node is already in the DOM, then this function
	 *  will remove it from the DOM before replacing the target.
	 * 
	 * @param boolean|String|RegExp activate
	 *  If true, will traverse the children of the replacement content,
	 *  searching for elements with a class ending in '_tool'. Based on the
	 *  class name of those elements, it will construct objects for the tools.
	 *  Note: the Javascript for these tools must already be loaded.
	 *  Also note: no initialization parameters will be passed to the constructor,
	 *    so make sure the tools activated by this method don't rely on
	 *    passing parameters to the constructor. (Use hidden elements instead.)
	 *  If this is a string, then it will search for elements which contain
	 *  a class matching the string exactly.
	 *  If a RegExp, it will search for elements which contain a class
	 *  matching the regular expression.
	 */
	me.replace = function(element, replacement, activate) {
		if (typeof(element) == 'string') {
			element = document.getElementById(element);
		}
		var eParent = element.parentNode;
		var insertedElements = []; var i=0;
		if (typeof(replacement) !== 'string') {
			// assume replacement is an HTML node
			if ('replaceChild' in eParent) {
				eParent.replaceChild(replacement, element);
			} else {
				// fallback
				eParent.insertBefore(replacement, element);
				eParent.removeChild(element);
			}
			insertedElements[i++] = replacement;
		} else {
			// replacement is a string
			var div = document.createElement('div');
			if (typeof(jQuery) !== 'undefined') {
				jQuery(div).html(replacement); // jquery
			} else {
				div.innerHTML = replacement; // otherwise
			}
			var eChild = div.firstChild;
			var eNext = element.nextSibling;
			eParent.removeChild(element);
			while (eChild) {
				eNextChild = eChild.nextSibling;
				eParent.insertBefore(eChild, eNext);
				insertedElements[i++] = eChild;
				eChild = eNextChild;
			}
		}
			
		if (activate) {
			me.find(insertedElements, activate, me.construct, me.init);
		}
	};
	
	/**
	 * Unleash this on an element to activate all the tools within it.
	 * If the element is itself an outer div of a tool, that tool is activated too.
	 * @param HtmlN
	 */
	me.activate = function(elem) {
		Pie.find(elem, true, Pie.construct, Pie.init);
	};
	
	/**
	 * Adds a reference to a javascript, if it's not already there
	 * @param string src
	 * @param string type
	 * @param Function onload
	 */
	me.addScript = function(src, type, onload) {
		if (Pie.typeOf(src) === 'array') {
			if (typeof(type) === 'function') {
				onload = type;
			}
			var ret = [];
			for (var i=0; i<src.length; ++i) {
				ret.push(
					me.addScript(src[i].src, src[i].type, onload)
				);
			}
			return ret;
		}
		if (typeof(type) === 'function') {
			onload = type; type = null;
		}
		if (!type) type = 'text/javascript';
		var scripts = document.getElementsByTagName('script');
		for (var i=0; i<scripts.length; ++i) {
			if (scripts[i].getAttribute('src') === src) {
				if (onload) { onload(true) }
				return; // don't add
			}
		}
		// Create the script tag and insert it into the document
		var script = document.createElement('script');
		script.setAttribute('type', type);
		if (onload) {
			script.onload = onload;
			script.onreadystatechange = onload; // for IE6
		}
		document.getElementsByTagName('head')[0].appendChild(script);
		script.setAttribute('src', src);
		return script;
	}
	
	/**
	 * Adds a reference to a stylesheet, if it's not already there
	 * @param string href
	 * @param string media
	 * @param string type
	 * @param Function onload
	 */
	me.addStylesheet = function(href, media, type, onload) {
		if (Pie.typeOf(href) === 'array') {
			if (typeof(media) === 'function'){
				onload = media;
			}
			var ret = [];
			for (var i=0; i<href.length; ++i) {
				ret.push(me.addStylesheet(
					href[i].href, href[i].media, href[i].type, onload
				));
			}
			return ret;
		}
		if (typeof(media) === 'function') {
			type = media; media = null;
		}
		if (typeof(type) === 'function') {
			onload = type; type = null;
		}
		if (!media) media = 'screen, print';
		if (!type) type = 'text/css';
		var sheets = document.styleSheets;
		for (var i=0; i<sheets.length; ++i) {
			if (sheets[i].href === href) {
				return; // don't add
			}
		}
		// Create the stylesheet's tag and insert it into the document
		var link = document.createElement('link');
		link.setAttribute('rel', 'stylesheet');
		link.setAttribute('type', type);
		link.setAttribute('href', href);
		if (onload) {
			link.onload = onload;
			link.onreadystatechange = onload; // for IE6
		}
		document.getElementsByTagName('head')[0].appendChild(link);
		return link;
	}
	
	/**
	 * Finds all elements that contain a class matching the filter,
	 * and calls the callback for each of them.
	 * 
	 * @param DOMNode|Array elem
	 *  An element, or an array of elements, within which to search
	 * @param String|RegExp|true filter
	 *  The name of the class to match
	 * @param Function callbackBefore
	 *  A function to run when a match is found (before the children)
	 * @param Function callbackAfter
	 *  A function to run when a match is found (after the children)
	 */
	me.find = function(elem, filter, callbackBefore, callbackAfter) {
		if (filter === true) {
			filter = 'pie_tool';
		}
		// Arrays are accepted
		if (me.typeOf(elem) === 'array' || 
		 (typeof(HTMLCollection) !== 'undefined' && (elem instanceof HTMLCollection))) {
			for (i=0; i<elem.length; ++i) {
				me.find(elem[i], filter, callbackBefore, callbackAfter);
			}
			return;
		}
		// Do a depth-first search and call the constructors
		var found = false;
		if ('className' in elem) {
			var classNames = elem.className.split(' ');
			var i;
			for (i=0; i<classNames.length; ++i) {
				if (((typeof(filter) === 'string') && (filter === classNames[i]))
				 || ((filter instanceof RegExp) && filter.test(classNames[i]))) {
					found = true;
					break;
				}
			}
		}
		if (found && typeof(callbackBefore) == 'function') {
			callbackBefore(elem);
		}
		var children;
		if ('children' in elem) {
			children = elem.children;
		} else {
			children = elem.childNodes; // more tedious search
		}
		var c = [];
		for (i=0; i<children.length; ++i) {
			c[i] = children[i];
		}
		me.find(c, filter, callbackBefore, callbackAfter);
		if (found && typeof(callbackAfter) == 'function') {
			callbackAfter(elem);
		}
	};
	
	/**
	 * Given a tool's generated container div, constructs the 
	 * corresponding JS tool object.
	 * This basically calls the tool's constructor, passing it
	 * the correct prefix.
	 * Note: to communicate with the constructor, you can use
	 * attributes and hidden fields.
	 * Note: don't forget to add the entry to Pie.constructors
	 * when you define your tool's constructor.
	 * 
	 * @param toolElement
	 *  A tool's generated container div.
	 */
	me.construct = function(toolElement) {
		var classNames = toolElement.className.split(' ');
		for (var i=0; i<classNames.length; ++i) {
			if (!(classNames[i] in Pie.constructors)) {
				continue;
			}
			var ctr = Pie.constructors[classNames[i]];
			if (typeof(ctr) !== 'function') {
				continue;
			}
			var newTool = {};
			var teId = toolElement.id;
			var prefix = teId.substring(0, teId.length-4);
			Pie.Tool.call(newTool, prefix);
			ctr.call(newTool, prefix);
			break;
		}
	};
	
	/**
	 * Calls the init method of a tool. Used mostly internally.
	 * @param toolElement
	 *  A tool's generated container div.
	 * @param options
	 *  Optional. An object containing options to pass.
	 */
	me.init = function(toolElement, options) {
		var teId = toolElement.id;
		var prefix = teId.substring(0, teId.length-4);
		var pie_tool = me.tools[prefix];
		if (typeof(pie_tool) !== 'object')
			return;
		if ('init' in pie_tool) {
			pie_tool.init(options || {});
		}
		if (m_isReady && ('ready' in pie_tool)) {
			// call "ready" on this tool, too, and then inform everyone else
			pie_tool.ready();
			Pie.handle(me.afterReady, this, [prefix, toolElement]);
		}
	}
	
	/**
	 * Clones an existing object, creating a new object
	 * which you can extend.
	 */
	me.clone = function (original) {
		function F() {};
		F.prototype = original;
		return new F();
	};
	
	/**
	 * Returns the type of a value
	 * @param value
	 *  
	 * return 
	 */
	me.typeOf = function (value) {
		var s = typeof value;
		if (s === 'object') {
			if (value === null) {
				return 'null';
			}
            if (value instanceof Array) {
                s = 'array';
			} else if (typeof(value.typename) != 'undefined' ) {
				return value.typename;
            } else if (typeof(value.constructor) != 'undefined'
			 && typeof(value.constructor.name) != 'undefined') {
				if (value.constructor.name == 'Object') {
					return 'object';
				}
				return value.constructor.name;
			} else {
				return 'object';
			}
		}
		return s;
	};
	
	/**
	 * Binds a method to an object, so "this" inside the method
	 * refers to that object when it is called.
	 * @param method
	 *  A reference to the function to call
	 * @param obj
	 *  The object to bind to
	 * @param options
	 *  Optional. If supplied, binds these options and passes
	 *  them during invocation.
	 */
	me.bind = function (method, obj, options) {
		if (options) {
			return function () {
				return method.apply(obj, arguments, options);
			}
		} else {
			return function () {
				return method.apply(obj, arguments);
			}
		}
	};
	
	/**
	 * Used for handling callbacks, whether they come as functions,
	 * strings referring to functions (if evaluated), arrays or hashes.
	 * @param callables
	 *  The callables to call
	 * @param context
	 *  The context in which to call them
	 * @param args
	 *  Any arguments to pass to them 
	 * @return Number
	 *  The number of handlers executed
	 */
	me.handle = function(callables, context, args) {
		var i=0, count=0;
		switch (me.typeOf(callables)) {
		 case 'function':
			if (context) {
				if (typeof(args) !== 'undefined') {
					callables.apply(context, args);
				} else {
					callables.apply(context);
				}
			} else {
				callables();
			}
			return 1;
		 case 'array':
			for (i=0; i<callables.length; ++i) {
				count += me.handle(callables[i], context, args);
			}
			return count;
		 case 'object':
			for (k in callables) {
				count += me.handle(callables[k], context, args);
			}
			return count;
		 case 'string':
			var c;
			try {
				eval('c = ' + callables);
			} catch (ex) {
				// absorb and do nothing, if possible
			}
			if (typeof(c) !== 'function') {
				return 0;
			}
			return me.handle(c, context, args);
		 default:
			return 0;
		}
	};
	
	/**
	 * Tests whether a variable contains a false value,
	 * or an empty object or array.
	 * @param o
	 *  The object to test.
	 */
	me.isEmpty = function (o) {
		if (!o) {
			return true;
		}
		var i, v, t;
		t = Pie.typeOf(o);
		if (t === 'object') {
		    for (i in o) {
		        v = o[i];
		        if (v !== undefined) {
		            return false;
		        }
		    }
			return true;
		} else if (t === 'array') {
			return (o.length === 0);
		}
		return false;
	};
	
	/**
	 * Extends an object with other objects. Similar to the jQuery method.
	 * @param Object target
	 *  This is the first object.
	 * @param Object anotherObj
	 *  Put as many objects here as you want, and they will extend the original one.
	 * @return
	 *  The extended object.
	 */
	me.extend = function() {
		if (arguments.length === 0) {
			return {};
		}
		var result = {};
		for (var i=0; i<arguments.length; ++i) {
			for (k in arguments[i]) {
				result[k] = arguments[i][k];
			}
		}
		return result;
	};
	
	return me;
}();

/**
 * Pie.Tool Class
 *
 * All JS classes for tools should extend this class using
 * Pie.Tool.apply(this, arguments)
 */
Pie.Tool = function(prefix) {

	// private properties
	var me = this;
	
	// private methods
	
	// constructor
	if (prefix in Pie.tools) {
		// remove the tool, notifying it
		Pie.tools[prefix].remove(this);
	}
	Pie.tools[prefix] = this;
	
	// public:
	me.typename = 'Pie.Tool';
	me.prefix = prefix;
	
	/**
	 * Gets the children of a particular tool
	 * based on the prefix of the tool.
	 */
	me.children = function () {
		var result = {};
		var key;
		for (key in Pie.tools) {
			if (key.length > this.prefix.length
			 && key.substr(0, prefix.length) == this.prefix) {
				result[key] = Pie.tools[key];
			}
		}
		return result;
	}
	
	/**
	 * Called when a tool instance is removed, possibly
	 * being replaced by another.
	 * Typically happens after an AJAX call which returns
	 * markup for the new instance tool.
	 * Also can be used for removing a tool instance
	 * and all of its children.
	 * Calls onRemove before replacing.
	 *
	 * @param Pie.Tool newTool
	 *  The tool that is supposed to be replacing it.
	 *  If null, the original tool is just removed.
	 */
	me.remove = function (newTool) {
		
		if (newTool 
		&& ('prefix' in newTool) 
		&& newTool.prefix == this.prefix) {
			// We are just "replacing the tool with itself",
			// so we should do nothing.
			// The real replacing happened during the construction
			// of the new instance of the tool, and onRemove
			// was called on the old instance from within Pie.Tool().
			return false;
		}

		var children = this.children();
		for (key in children) {
			children[key].remove();
		}
		if ('onRemove' in this) {
			// Handle this event
			if ('onRemove' in this) {
				this.onRemove({"newTool": newTool});
			}
		}
		
		if (me.prefix in Pie.tools) {
			delete Pie.tools[me.prefix];
		}
			
		return true;
	};
};

Pie.Session = function() {
	// TODO: Set a timer for when session expires?
	return {};
}


/**
 * Extend some built-in prototypes
 */

String.prototype.htmlentities = function () {
    return this.replace(/&/g, "&amp;").replace(/</g,
        "&lt;").replace(/>/g, "&gt;");
};

String.prototype.quote = function () {
    var c, i, l = this.length, o = '"';
    for (i = 0; i < l; i += 1) {
        c = this.charAt(i);
        if (c >= ' ') {
            if (c === '\\' || c === '"') {
                o += '\\';
            }
            o += c;
        } else {
            switch (c) {
            case '\b':
                o += '\\b';
                break;
            case '\f':
                o += '\\f';
                break;
            case '\n':
                o += '\\n';
                break;
            case '\r':
                o += '\\r';
                break;
            case '\t':
                o += '\\t';
                break;
            default:
                c = c.charCodeAt();
                o += '\\u00' + Math.floor(c / 16).toString(16) +
                    (c % 16).toString(16);
            }
        }
    }
    return o + '"';
};

String.prototype.supplant = function (o) {
    return this.replace(/\{([^{}]*)\}/g,
        function (a, b) {
            var r = o[b];
            return typeof r === 'string' || typeof r === 'number' ? r : a;
        }
    );
};

String.prototype.trim = function () {
    return this.replace(/^\s+|\s+$/g, "");
};
