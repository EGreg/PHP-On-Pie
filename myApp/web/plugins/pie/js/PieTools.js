/**
 * Opens a dialog
 */ 
$.fn.dialog = function(options) {
	var o = $.extend({
		'url': null,
		'afterActivate': null
	}, options);
	if (!o.url) {
		alert('Please provide a url for the dialog')
		return false;
	}
	return this.each(function(index) {
		$this = $(this);
		if (!$this.data('dialog')) {
			$this.overlay({
				oneInstance: false, 
				api: true
			});
			$this.data('dialog', 'overlay');
		};
		var ocs = $('.content_slot', $this);
		var api = $this.overlay();
		api.load();
		$this.addClass('pie_loading');
		ocs.empty().addClass('pie_throb');
		var url = Pie.ajaxExtend(o.url, 'dialog');
		$.getJSON(url, function(data) {
			if ('errors' in data) {
				api.close();
				alert(data.errors[0].message);
				return;
			}
			ocs.html(data.slots.dialog);
			ocs.removeClass('pie_throb');
			$this.removeClass('pie_loading');
			if (('stylesheets' in data) && ('dialog' in data.stylesheets)) {
				Pie.addStylesheet(data.stylesheets.dialog);
			}
			var afterLoad = function(alreadyLoaded) {
				Pie.activate(ocs.get(0));
				Pie.handle(o.afterActivate);
				if (('scriptLines' in data) && ('dialog' in data.scriptLines)) {
					eval(data.scriptLines.dialog);
				}
			};
			if (('scripts' in data) && ('dialog' in data.scripts)) {
				Pie.addScript(data.scripts.dialog, afterLoad);
			} else {
				afterLoad();
			}
		});
	});
}

/**
 * Zoomer tool
 *
 * Generates an image and zooms it
 */

$.fn.zoomer = function(options) {

	var options2 = $.extend({
		"overlayWidth": 75, 
		"overlayHeight": 75,
		"zoomedWidth": null,
		"zoomedHeight": null,
		"widthRatio": null,
		"heightRatio": null,
		"overlayClass": "zoomer"
	}, options);

	return this.each(function(index) {
		// they should all be images
		var $this = $(this);
		if (!$this.is('img')) {
			return;
		}
		
		var data = $this.data('zoomer');
		if (data) {
			$this.unbind('mousemove', data.onMouseMove);
			$this.unbind('mouseleave', data.onMouseLeave);

			data.o_div.unbind('mousemove', data.onZoomerMouseMove);
			data.o_div.unbind('mouseleave', data.onZoomerMouseLeave);
			data.o_div.remove();
			$this.data('zoomer', null);
		}
		if (options === 'remove') {
			return;
		}
		
		var o_div = $('<div />')
			.css('position', 'absolute')
			.css('display', 'none')
			.css('overflow', 'hidden')
			.addClass('zoomer')
			.addClass(options2.overlayClass);
		var z_img = $('<img />')
			.css('position', 'absolute');
		if (options2.zoomedWidth) {
			z_img.css('width', zoomedWidth);
		} else if (options2.widthRatio) {
			z_img.width((options2.widthRatio * $this.width()));
		}
		if (options2.zoomedHeight) {
			z_img.css('height', zoomedHeight);
		} else if (options2.heightRatio) {
			z_img.height((options2.heightRatio * $this.height()));
		}
		z_img.attr('src', $this.attr('src'));
		if (options2.overlayWidth) {
			o_div.width(options2.overlayWidth);
		}
		if (options2.overlayHeight) {
			o_div.height(options2.overlayHeight);
		}

		var onMouseMove = function(e) {
			var offset = $this.offset();
			var x = e.pageX - offset.left;
			var y = e.pageY - offset.top;
			var iw = $this.width(); // not inner, because it's an img
			var ih = $this.height();
			var xf = x/iw;
			var yf = y/ih;
			var ow = o_div.width();
			var oh = o_div.height();
			var zw = z_img.width();
			var zh = z_img.height();
			var o_bw = o_div.css('borderWidth');
			var o_bh = o_div.css('borderHeight');
			
			var o_left = offset.left + xf * (iw - ow);
			var o_top = offset.top + yf * (ih - oh);
			var z_left = offset.left + xf * (iw - zw) - o_left;
			var z_top = offset.top + yf * (ih - zh) - o_top;

			o_div.css('left', o_left+'px')
				.css('top', o_top+'px');
			z_img.css('left', z_left+'px')
				.css('top', z_top+'px');
			o_div.css('position', 'absolute');
			o_div.css('display', 'block');
		};

		var onMouseLeave = function(e) {
			var offset = $this.offset();
			if (e.pageX < offset.left
				|| e.pageY < offset.top 
				|| e.pageX > offset.left + $this.width()
				|| e.pageY > offset.top + $this.height()) {
				o_div.css('display', 'none');
			}
		};
		
		var onZoomerMouseMove = function(e) {
			var offset = $this.offset();
			if (e.pageX < offset.left
				|| e.pageY < offset.top 
				|| e.pageX > offset.left + $this.width()
				|| e.pageY > offset.top + $this.height()) {
				onMouseLeave(e);	
			} else {
				onMouseMove(e);
			}
		};
		var onZoomerMouseLeave = onZoomerMouseMove;

		//this.css('height', '200px');
		$this.mousemove(onMouseMove);
		$this.mouseleave(onMouseLeave);
		
		o_div.mousemove(onZoomerMouseMove);
		o_div.mouseleave(onZoomerMouseLeave);
		
		o_div.html(z_img);
		o_div.appendTo('body');
		
		$this.data('zoomer', {
			'onMouseMove': onMouseMove,
			'onMouseLeave': onMouseLeave,
			'onZoomerMouseMove': onZoomerMouseMove,
			'onZoomerMouseLeave': onZoomerMouseLeave,
			'o_div': o_div
		});
	});
	
};

$.fn.autogrow = function(options) {

       o = $.extend({
           maxWidth: 1000,
           minWidth: 0,
           comfortZone: 10,
		onResize: null
       }, options);

       this.filter('input:text').each(function(){

           var minWidth = o.minWidth || $(this).width(),
               val = '',
               input = $(this),
               testSubject = $('<tester/>').css({
                   position: 'absolute',
                   top: -9999,
                   left: -9999,
                   width: 'auto',
                   fontSize: input.css('fontSize'),
                   fontFamily: input.css('fontFamily'),
                   fontWeight: input.css('fontWeight'),
                   letterSpacing: input.css('letterSpacing'),
                   whiteSpace: 'nowrap'
               });
               check = function() {
                   if (val === (val = input.val())) {return;}

                   // Enter new content into testSubject
                   var escaped = val.replace(/&/g, '&amp;').replace(/\s/g,'&nbsp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                   testSubject.html(escaped);

                   // Calculate new width + whether to change
                   var testerWidth = testSubject.width(),
                       newWidth = (testerWidth + o.comfortZone) >= minWidth ? testerWidth + o.comfortZone : minWidth,
                       currentWidth = input.width(),
                       isValidWidthChange = (newWidth < currentWidth && newWidth >= minWidth)
                                            || (newWidth > minWidth && newWidth < o.maxWidth);

                   // Animate width
                   if (isValidWidthChange) {
                       input.width(newWidth);
					if (o.onResize) {
						o.onResize(newWidth);
					}
                   }

               };

           testSubject.insertAfter(input);

           $(this).bind('keyup keydown blur update autogrowCheck', check);

       });

       return this;

   };

// utility functions we can use

function setSelRange(inputEl, selStart, selend) { 
 if ('setSelectionRange' in inputEl) { 
  inputEl.focus(); 
  inputEl.setSelectionRange(selStart, selend); 
 } else if (inputEl.createTextRange) { 
  var range = inputEl.createTextRange(); 
  range.collapse(true); 
  range.moveEnd('character', selend); 
  range.moveStart('character', selStart); 
  range.select(); 
 } 
}

if (!('Tools' in Pie)) {
	Pie.Tools = {};
}

Pie.constructors['pie_inplace_tool'] = 
Pie.Tools.InPlace = function(prefix) {

	// constructor & private declarations
	var me = this;
	var blurring = false;
	var focusedOn = null;
	var dialogMode = false;
	var previousValue = null;
	var noCancel = false;

	me.init = function() {

	};

	me.ready = function() {
		var tool_div = $('#'+prefix+'tool');
		var container_span = $('.pie_inplace_tool_container', tool_div);
		var static_span = $('.pie_inplace_tool_static', tool_div);
		if (!static_span.length) {
			static_span = $('.pie_inplace_tool_blockstatic', tool_div);
		}
		var edit_button = $('button.pie_inplace_tool_edit', tool_div);
		var save_button = $('button.pie_inplace_tool_save', tool_div);
		var cancel_button = $('button.pie_inplace_tool_cancel', tool_div);
		var fieldinput = $('.pie_inplace_tool_fieldinput', tool_div);
		var undermessage = $('#'+prefix+'undermessage');
		var throbber_img = $('<img />')
			.attr('src', Pie.info.baseUrl+'/plugins/pie/img/throbbers/bars16.gif');
		if (container_span.hasClass('pie_editing')) {
			noCancel = true;
		}
		fieldinput.autogrow({
			"maxWidth": tool_div.parent().width()
		});
		var onClick = function() {
			var field_width = static_span.width();
			var field_height = static_span.height();
			if (fieldinput.is('select')) {
				field_width += 40;
			} else if (fieldinput.is('input[type=text]')) {
				field_width += 5;
				field_height = static_span.css('line-height');
			} else if (fieldinput.is('textarea')) {
				field_height = Math.max(field_height, 100);
			}
			fieldinput.css({
				fontSize: static_span.css('fontSize'),
               	fontFamily: static_span.css('fontFamily'),
               	fontWeight: static_span.css('fontWeight'),
               	letterSpacing: static_span.css('letterSpacing')
			});
			if (!fieldinput.is('select')) {
				fieldinput.width(Math.max(field_width, 16));
				try {
					fieldinput.height(Math.max(field_height, 16));
				} catch (e) {
					
				}
			}
			previousValue = fieldinput.val();
			container_span.addClass('pie_editing');
			undermessage.empty().css('display', 'none').addClass('pie_error');
			focusedOn = 'fieldinput';
			fieldinput.focus();
			if ('select' in fieldinput) {
				if (fieldinput.attr('type') == 'text') {
					fieldinput.select();
				} else if (fieldinput.is('textarea')) {
					setSelRange(fieldinput[0], fieldinput.val().length, fieldinput.val().length);
				}
			}
		};
		var onSave = function() {
			undermessage.html(throbber_img)
				.css('display', 'block')
				.removeClass('pie_error');
			focusedOn = 'fieldinput';
			var form = $('.pie_inplace_tool_form', tool_div);
			var url = form.attr('action');
			$.ajax({
				url: Pie.ajaxExtend(url, 'pie_inplace'),
				type: 'POST',
				data: form.serialize(),
				dataType: 'json',
				error: function(xhr, status, except) {
					onSaveErrors('ajax status: ' + status + '... try again');
				},
				success: function(data) {
					if (('errors' in data) && data.errors.length) {
						onSaveErrors(data.errors[0].message);
						return;
					}
					onSaveSuccess(data);
				}
			});
		};
		var onSaveErrors = function(message) {
			alert(message);
			fieldinput.focus();
			undermessage.css('display', 'none');
			/*
				.html(message)
				.css('whiteSpace', 'nowrap')
				.css('bottom', (-undermessage.height()-3)+'px');
			*/
		}
		var onSaveSuccess = function(data) {
			var newval = fieldinput.val();
			if ('slots' in data) {
				if ('pie_inplace' in data.slots) {
					newval = data.slots.pie_inplace;
				}
			}
			static_span.html(newval);
			undermessage.empty().css('display', 'none').addClass('pie_error');
			container_span.removeClass('pie_editing');
			noCancel = false;
		}
		var onCancel = function() {
			if (noCancel) {
				return;
			}
			if (fieldinput.val() != previousValue) {
				dialogMode = true;
				var continueEditing = confirm(
					"Would you like to save your changes?"
				);
				dialogMode = false;
				if (continueEditing) {
					onSave();
					return;
				}
			}
			fieldinput.val(previousValue);
			fieldinput.blur();
			focusedOn = null;
			container_span.removeClass('pie_editing');
		};
		var onBlur = function() {
			if (focusedOn 
			 || dialogMode
			 || !container_span.hasClass('pie_editing')) {
				return;
			}
			if (fieldinput.val() == previousValue) {
				onCancel(); return;
			}
			onCancel();
		};
		container_span.mouseover(function() { 
			container_span.addClass('pie_hover'); 
		});
		container_span.mouseout(function() { 
			container_span.removeClass('pie_hover'); 
		});
		static_span.click(onClick);
		edit_button.click(onClick);
		cancel_button.click(function() { onCancel(); return false; });
		cancel_button.focus(function() { focusedOn = 'cancel_button'; });
		cancel_button.blur(function() { focusedOn = null; setTimeout(onBlur, 100); });
		save_button.click(function() { onSave(); return false; });
		save_button.focus(function() { focusedOn = 'save_button'; });
		save_button.blur(function() { focusedOn = null; setTimeout(onBlur, 100); });
		fieldinput.keyup(function() {
			var invisible_span = $('.pie_inplace_tool_invisible_span', tool_div);
			invisible_span
				.css('font-family', fieldinput.css('font-family'))
				.css('font-size', fieldinput.css('font-size'));
			invisible_span.text(fieldinput.val());
			save_button.attr('display', (fieldinput.val() == previousValue) ? 'none' : 'inline');
		});
		fieldinput.focus(function() { focusedOn = 'fieldinput'; });
		fieldinput.blur(function() { focusedOn = null; setTimeout(onBlur, 100); });
		fieldinput.change(function() { fieldinput.attr(fieldinput.val().length.toString() + 'em;') });
		fieldinput.keydown(function(event) {
			if (!focusedOn) {
				return false;
			}
			if (event.keyCode == 13) {
				if (! fieldinput.is('textarea')) {
					onSave(); return false;
				}
			} else if (event.keyCode == 27) {
				onCancel(); return false;
			}
		});
	};

};

Pie.constructors['pie_form_tool'] =
Pie.Tools.Form = function(prefix) {

	// constructor & private declarations
	var me = this;

	me.onSubmit = {};
	me.onResponse = {};
	me.onSuccess = {};
	me.slotsToRequest = 'form';

	me.init = function() {

	};

	me.ready = function() {
		var tool_div = $('#'+prefix+'tool');
		var form = tool_div.closest('form');
		if (!form.length) return;
		if (form.data('pie_form_tool')) return;
		form.submit(function() {
			var onResponse = function(data, status, xhr) {
				$('button', tool_div).closest('td').removeClass('pie_throb');
				if (0 === Pie.handle(me.onResponse, me, arguments)) {
					return;
				}
				$('div.pie_form_undermessagebubble', tool_div).empty();
				$('tr.pie_error', tool_div).removeClass('pie_error');
				if ('errors' in data) {
					me.applyErrors(data.errors)
				} else {
					Pie.handle(me.onSuccess, me, arguments);
				}
			};
			$('button', tool_div).closest('td').addClass('pie_throb');
			var url = Pie.ajaxExtend(form.attr('action'), me.slotsToRequest);
			if (0 === Pie.handle(me.onSubmit, me, [form])) {
				return false;
			}
			$.post(url, form.serialize(), onResponse, 'json');
			return false;
		});
		form.data('pie_form_tool', true);
	};
	
	me.applyErrors = function(errors) {
		var tool_div = $('#'+prefix+'tool');
		var err = null;
		for (var i=0; i<errors.length; ++i) {
			if (!('fields' in errors[i]) || Pie.typeOf(errors[i].fields) === 'array') {
				err = errors[i];
				continue;
			}
			for (k in errors[i].fields) {
				var td = $("td[data-fieldname='"+k+"']", tool_div);
				if (!td.length) {
					err = errors[i];
				}
				var tr = td.closest('tr').next();
				tr.addClass('pie_error');
				$('div.pie_form_undermessagebubble', tr)
					.html(errors[i].message);
			}
		}
		if (err) {
			alert(err.message);
		}
	};
	
	me.updateValues = function(newContent) {
		var tool_div = $('#'+prefix+'tool');
		if (Pie.typeOf(newContent) == 'string') {
			tool_div.html(newContent);
			Pie.activate(tool_div.children().get());
		} else if ('fields' in newContent) {
			// enumerate the fields
			alert("An array was returned. Need to implement that.");
			for (k in newContent.fields) {
				switch (newContent.fields[k].type) {
				 case 'date':
					break;
				 case 'select':
					break;
				 case 'checkboxes':
					break;
				 case 'radios':
				 	break;
				 default:
					break;
				}
			}
		}
	};

};

Pie.constructors['pie_panel_tool'] =
Pie.Tools.Panel = function(prefix) {

	// constructor & private declarations
	var me = this;
	var form_val = null;
	var tool_div, container;
	Pie.Tool.apply(me, arguments);

	me.init = function() {
		var tool_div = $('#'+prefix+'tool');
		var form = $('form', tool_div);
		var form_tool_prefix = prefix+'pie_form_';
		var static_tool_prefix = prefix+'idstatic_pie_form_';
		var container = $('.pie_panel_tool_container', tool_div);
		if (form_tool_prefix in Pie.tools) {
			var form_tool = Pie.tools[form_tool_prefix];
			form_tool.onSuccess[prefix] = function() {
				form_val = form.serialize();
				container.removeClass('pie_modified');
				container.removeClass('pie_editing');
			};
			if (static_tool_prefix in Pie.tools) {
				var static_tool = Pie.tools[static_tool_prefix];
			}
			form_tool.onResponse[prefix] = function(data) {
				var buttons = $('.pie_panel_tool_buttons', tool_div);
				buttons.removeClass('pie_throb');
				if ('slots' in data) {
					if ('form' in data.slots) {
						form_tool.updateValues(data.slots.form);
					}
					if (('static' in data.slots) && static_tool) {
						static_tool.updateValues(data.slots.static);
					}
				}
			}
			form_tool.onSubmit[prefix] = function() {
				var buttons = $('.pie_panel_tool_buttons', tool_div);
				buttons.addClass('pie_throb');
			}
			form_tool.slotsToRequest = 'form,static';
		}
	};

	me.ready = function() {
		tool_div = $('#'+prefix+'tool');
		container = $('.pie_panel_tool_container', tool_div);
		var form = $('form', tool_div);
		var edit_button = $('.pie_panel_tool_edit', tool_div);
		var cancel_button = $('button.pie_panel_tool_cancel', tool_div);
		form_val = form.serialize();
		form.bind('change keyup keydown blur', function() {
			var new_val = form.serialize();
			if (form_val !== new_val) {
				container.addClass('pie_modified');
			} else {
				container.removeClass('pie_modified');
			}
		});	
		if (container.hasClass('pie_panel_tool_toggle_onclick')) {
			var header = $('.pie_panel_tool_header', container);
			header.click(toggleExpand);
		} else if (container.hasClass('pie_panel_tool_toggle_move')) {
			var header = $('.pie_panel_tool_header', container);
			header.mouseenter(function() {
				container.removeClass('pie_collapsed');
				container.addClass('pie_expanded');
			});
			container.mouseleave(function() {
				container.addClass('pie_collapsed');
				container.removeClass('pie_expanded');
			});
		}
		edit_button.click(function() {
			container.addClass('pie_editing');
			container.removeClass('pie_collapsed');
			container.addClass('pie_expanded');
			return false;
		});
		cancel_button.click(function() {
			container.removeClass('pie_editing');
			container.removeClass('pie_modified');
			return true; // really cancel the form
		});
	};
	
	var toggleExpand = function() {
		if (container.hasClass('pie_collapsed')) {
			container.removeClass('pie_collapsed');
			container.addClass('pie_expanded');
		} else {
			container.addClass('pie_collapsed');
			container.removeClass('pie_expanded');
		}
	};
};
