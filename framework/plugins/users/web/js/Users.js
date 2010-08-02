Pie.Users = function($) {
	var me = {
		facebookApps: {} // this info gets added by the server, on the page
	};
	var priv = {};
	
	me.initFacebook = function(options) {
		options = options || {};
		FB.init(Pie.extend({
			appId: Pie.Users.facebookApps[Pie.app].appId,
			status: true,
			cookie: true,
			xfbml: true
		}));
	};
	
	me.login = function(options) {
		var defaults = {
			'onCancel': null,
			'onSuccess': null, // gets passed session
			"accountStatusUrl": null,
			'onRequireComplete': false,
			'tryQuietly': false,
			'using': 'native', // can also be 'facebook'
			'perms': 'email,publish_stream' // the permissions to ask for
		};
		var o = $.extend(defaults, options);
		
		var dest;
		function onConnect(response) {
			if (!o.accountStatusUrl) {
				onComplete(response);
				return;
			}
			Pie.jsonRequest(o.accountStatusUrl, 'accountStatus', function(response) {
				// DEBUGGING: For debugging purposes
				if (!response.slots) {
					if (response.errors && response.errors[0].message) {
						alert(response.errors[0].message);
					}
					return;
				}

				if (!o.onRequireComplete || response.slots.accountStatus === 'complete') {
					onComplete(response);
				} else if (response.slots.accountStatus === 'refresh') {
					// we are logged in, refresh the page
					document.location.reload(true);
					return;
				} else {
					// take the user to the profile page
					// which will show the account process,
					// until completed -- and then the profile!
					if (typeof(o.onRequireComplete) !== 'function'
					 && typeof(o.onRequireComplete) !== 'string') {
						alert('Need a url in the onRequireComplete option');
						return;
					}
					dest = o.onRequireComplete;
					if (document.location != dest) {
						document.location = dest;
					} else {
						document.location.reload(true);
					}
				}
			});
		};
		
		function onCancel(response) {
			Pie.handle(o.onCancel, tris, [response]);
		}
		
		function onComplete(response) {
			if (!o.onSuccess 
			&& typeof(o.onSuccess) !== 'function'
			&& typeof(o.onSuccess) !== 'string') {
				alert('Need a url in the onSuccess option');
				return;
			}
			Pie.handle(o.onSuccess, this, [response]);
		}

		if (o.using === 'native') {
			login_setupOverlay();
		}

		if (o.tryQuietly) {
			if (o.using === 'facebook') {
				me.initFacebook();
				FB.getLoginStatus(function(response) {
					if (response.session) {
						onConnect(response);
					} else {
						onCancel(response);
					}
				});
			}
			return false;
		}

		if (o.using === 'native') {
			function showOverlay() {
				$('#users_login_overlay').data('overlay').load();
			}
			priv.login_onConnect = onConnect;
			priv.login_onCancel = onCancel;
			if ($.fn.overlay) {
				showOverlay();
			} else {
				Pie.addScript('http://cdn.jquerytools.org/1.2.3/full/jquery.tools.min.js', showOverlay);
			}
		} else if (o.using === 'facebook') {
			me.initFacebook();
			FB.login(function(response) {
				if (response.session) {
					onConnect(response);
				} else {
					onCancel(response);
				}
			}, o);
		}
		
		// you can now require login and do FQL queries:
		return false;
	};
	
	me.logout = function(options) {
		var urls = Pie.urls || {};
		var defaults = {
			'url': urls[Pie.app+'/logout'],
			'onSuccess': urls[Pie.app+'/welcome'],
			'using': 'native' 
		};
		var o = Pie.extend(defaults, options);
		if (!o.url) {
			return false;
		}
		
		var callback = function(response) {
			var onSuccess = function() {
				if (typeof o.onSuccess === 'function') {
					o.onSuccess();
				} else if (typeof o.onSuccess === 'string') {
					document.location = o.onSuccess;
				}
			}
			
			if (response.slots && response.slots.script) {
				// This script is coming from our server - it's safe.
				try {
					eval(response.slots.script);
				} catch (e) {
					alert(e);
				}
			}
			if (o.using === 'facebook') {
				me.initFacebook();
				FB.logout(function(response) {
					onSucess();
				});
			} else {
				onSuccess();
			}
		}
		var url = o.url + (o.url.indexOf('?') < 0 ? '?' : '') + '&logout=1';
		Pie.jsonRequest(url, 'script', callback, {"post": "true"});
		return true;
	};
	
	
	me.setEmail = function(options) {
		var defaults = {
			'onCancel': null,
			'onSuccess': null, // gets passed session
		};
		var o = $.extend(defaults, options);
		
		function onSuccess(response) {
			Pie.handle(o.onSuccess, this, [response]);	
		}
		
		function onCancel(response) {
			Pie.handle(o.onCancel, this, [response]);
		}
		
		priv.setEmail_onSuccess = onSuccess;
		priv.setEmail_onCancel = onCancel;
		
		setEmail_setupOverlay();
		$('#users_setEmail_overlay').data('overlay').load();
	}
	
	/**
	 * Private functions
	 */
	function login_callback(data) {
		var email_input = $('#users_login_email');
		var form = $('#users_login_step1_form');
		email_input.css('background-image', 'none');

		if (data.errors) {
			// There were errors
			form.data("validator").invalidate(Pie.ajaxErrors(data.errors));
			email_input.focus();
			return;
		}

		// Remove any errors we may have displayed
		form.data('validator').reset();

		var json = data.slots.json;
		var src = json.entry[0].photos && json.entry[0].photos.length
			? json.entry[0].photos[0].value
			: json.entry[0].thumbnailUrl;
		var table = $('<table />').append(
			$('<tr />').append(
				$('<td style="width: 80px; padding: 5px;" />').append($('<img />')
					.attr('src', src)
					.attr('title', 'You can change this picture later')
					.tooltip()
				)
			).append(
				$('<td style="padding: 5px;" />').append(
					'<label for="users_login_username">Choose a username:</label><br>'
				).append(
					$('<input id="users_login_username" name="username" class="text" placeholder="username">')
					.val(json.entry[0].preferredUsername || json.entry[0].displayName)
				)
			)
		);

		var step2_form = $('<form id="users_login_step2_form" method="post" />')
		.attr('action', Pie.info.baseUrl + "/action.php/users/register")
		.append($('<div class="users_login_appear" />')
		.append(table))
		.append($('<input type="hidden" name="email_address" />').val(email_input.val()))
		.append($('<input type="hidden" name="icon" />').val(src))
		.append($('<div class="users_login_get_started" />').append(
			'&nbsp;<button type="submit" class="users_login_start pie_main_button">Get Started</button>'
		));
		$('#users_login_step2').html(step2_form);
		$('#users_login_step1').animate({"opacity": 0.5}, 'fast');
		$('#users_login_step1 button').attr('disabled', 'disabled');
		$('#users_login_step2').slideDown('fast');
		var username_input = $('#users_login_username');
		username_input.focus().select();
		step2_form.validator().submit(function() {
			username_input.css({
				'background-image': 'url(' +Pie.info.baseUrl 
					+ '/plugins/pie/img/throbbers/bars.gif)',
				'background-repeat': 'no-repeat'
			});
			var url = step2_form.attr('action')+'?'+step2_form.serialize();
			Pie.jsonRequest(url, 'json', function (data) {
				username_input.css('background-image', 'none');
				if (data.errors) {
					// there were errors
					step2_form.data("validator").invalidate(Pie.ajaxErrors(data.errors));
					username_input.focus();
					return;
				}
				// success!
				username_input.css('background-image', 'none');
				$('button', step2_form).html('Welcome, '+data.slots.json.user.fields.username+'!')
				.attr('disabled', 'disabled');

				if (priv.login_onConnect) {
					priv.login_onConnect(data.slots.json.user);
				}
			}, {"post": true});
			return false;
		});
	}
	
	function login_setupOverlay() {
		if (login_setupOverlay.overlay) {
			return;
		}
		var step1_form = $('<form id="users_login_step1_form" />');
		var step1_div = $('<div id="users_login_step1" class="pie_big_prompt" />').html(step1_form);
		var step2_div = $('<div id="users_login_step2" class="pie_big_prompt" />');
		step1_form.html(
			'<label for="users_login_email">Enter your email address:</label><br>' +
			'<input id="users_login_email" type="email" name="email_address" class="text" placeholder="email">&nbsp;' +
			'<button type="submit" class="users_login_go pie_main_button">Go</button>'
		).submit(function() {
			$('#users_login_email').css({
				'background-image': 'url(' +Pie.info.baseUrl 
					+ '/plugins/pie/img/throbbers/bars.gif)',
				'background-repeat': 'no-repeat'
			});
			var url = Pie.info.baseUrl+'/action.php/users/user' + '?' + $(this).serialize();
			Pie.jsonRequest(url, 'json', login_callback);
			return false;
		}).bind('keyup keydown change click', function() {
			if ($('#users_login_step1').next().is(':visible')) {
				$('#users_login_step1').animate({"opacity": 1}, 'fast');
				$('#users_login_step1 button').removeAttr('disabled');
			}
			$('#users_login_step1').nextAll().slideUp('fast').each(function() {
				var v = $('form', $(this)).data('validator');
				if (v) {
					v.reset();
				}
			});
		});
		step1_form.validator();
		var overlay = $('<div class="users_login_overlay pie_overlay" id="users_login_overlay" />');
		overlay.overlay({
			// some mask tweaks suitable for modal dialogs
			onBeforeLoad: function() {
				$('#users_login_step1').css('opacity', 1).nextAll().hide();	
				$('input', overlay).val('');
			},
			onLoad: function() {
				$('input', overlay).eq(0).val('').focus();
			},
			onClose: function() {
				$('#users_login_step1 button').removeAttr('disabled');
				$('form', overlay).each(function() {
					var v = $(this).data('validator');
					if (v) {
						v.reset();
					}
				});
				$('#users_login_step1').nextAll().hide();
				if (priv.login_onCancel) {
					priv.login_onCancel();
				}
			}
		});
		overlay.append('<h2 class="users_dialog_title pie_dialog_title">Welcome</h2>')
			.append(step1_div)
			.append (step2_div)
			.appendTo('body');
			
		FM.activatePlaceholders(overlay);
		/*
		.mouseenter(function() {
			prev_opacity = $(this).css('opacity');
			$(this).animate({'opacity': 1}, 'fast');
		}).mouseleave(function() {
			$(this).animate({'opacity': prev_opacity}, 'fast');
		});
		*/
		login_setupOverlay.overlay = overlay;
	}
	
	function setEmail_callback(data) {
		var email_input = $('#users_login_email');
		var form = $('#users_setEmail_step1_form');
		email_input.css('background-image', 'none');

		if (data.errors) {
			// There were errors
			form.data("validator").invalidate(Pie.ajaxErrors(data.errors));
			email_input.focus();
			return;
		}

		// Remove any errors we may have displayed
		form.data('validator').reset();

		setEmail_setupOverlay.overlay.data('overlay').close();
	}
	
	function setEmail_setupOverlay() {
		if (setEmail_setupOverlay.overlay) {
			return;
		}
		var step1_form = $('<form id="users_setEmail_step1_form" />');
		var step1_div = $('<div id="users_setEmail_step1" class="pie_big_prompt" />').html(step1_form);
		step1_form.html(
			'<label for="users_setEmail_email">Enter your email address:</label><br>' +
			'<input id="users_setEmail_email" type="email" name="email_address" class="text" placeholder="email">' +
			'<div class="pie_buttons">' +
			'<button type="submit" class="users_setEmail_go pie_main_button">Send Confirmation Message</button>' +
			'</div>'
		).submit(function() {
			$('#users_setEmail_email').css({
				'background-image': 'url(' +Pie.info.baseUrl 
					+ '/plugins/pie/img/throbbers/bars.gif)',
				'background-repeat': 'no-repeat'
			});
			var url = Pie.info.baseUrl+'/action.php/users/contact' + '?' + $(this).serialize();
			Pie.jsonRequest(url, 'json', setEmail_callback, {"post": true});
			return false;
		});
		if (Pie.nonce) {
			step1_form.append(
				$('<input type="hidden" name="_pie[nonce]">').attr('value', Pie.nonce)
			);
		}
		step1_form.validator();
		var overlay = $('<div class="users_setEmail_overlay pie_overlay" id="users_setEmail_overlay" />');
		overlay.overlay({
			// some mask tweaks suitable for modal dialogs
			onBeforeLoad: function() {
				$('input', overlay).val('');
			},
			onLoad: function() {
				$('input', overlay).eq(0).val('').focus();
			},
			onClose: function() {
				$('form', overlay).each(function() {
					var v = $(this).data('validator');
					if (v) {
						v.reset();
					}
				});
				if (priv.setEmail_onCancel) {
					priv.setEmail_onCancel();
				}
			}
		});
		overlay.append('<h2 class="users_dialog_title pie_dialog_title">My Account</h2>')
			.append(step1_div)
			.appendTo('body');
			
		FM.activatePlaceholders(overlay);
		setEmail_setupOverlay.overlay = overlay;
	}
	
	return me;
}(jQuery);









/*
 * users/contact tool
 */

Pie.constructors['users_contact_tool'] =
Pie.Users.Contact = function(prefix) {
	
	// constructor
	var me = this;
	
	me.attachValidation = function() {
		var tool_div = $('#'+prefix+'tool');
		/*
		return $("form", tool_div).validate({
			rules: {
				"email_address": {
					required: true,
					email: true
				}
			},
			messages: {
				"email_address": {
					required: "invalid email",
					minlength: "invalid email",
					email: "invalid email",
					remote: jQuery.format("{0} is already in use")
				}
			},
			submitHandler: function() {
				var callback = function(result) {
					if ('errors' in result) {
						alert (result.errors[0].message);
						return;
					}
					Pie.replace(
						prefix+'tool',
						result.slots.replace
					);
					me.attachValidation();
				}
				//Pie.Dategame.addEmail();
				$.post(
					Pie.ajaxExtend(Pie.Users.urls['contact'], 'replace'), 
					$("form.askEmail").serialize(), 
					callback, 
					"json"
				);
			},
			// set this class to error-labels to indicate valid fields
			success: function(label) {
				// set &nbsp; as text for IE
				label.html("&nbsp;").addClass("checked");
			}
		});
		*/
	}
	
	me.init = function(initFields) {
		
	}
	
	me.ready = function() {		
		// validate signup form on keyup and submit
		var validator = me.attachValidation();
	}
}

/*
 * users/account tool
 */

Pie.constructors['users_account_tool'] =
Pie.Users.Account = function(prefix) {
	
	// constructor
	var me = this;
	var overlay = null;
	
	me.ready = function() {
		FB.Event.subscribe('auth.login', function(response) {
			if (!response.session) {
				return false;
			}
			FB.api('/me', function(response) {
				var row =  ('length' in rows) ? rows[0] : rows;
				var show = false;
				if ($('#'+prefix+'first_name').attr('value')
				 && $('#'+prefix+'last_name').attr('value')
				 && $('#'+prefix+'birthday_month').attr('value') > '0'
				 && $('#'+prefix+'birthday_day').attr('value') > '0'
				 && $('#'+prefix+'birthday_year').attr('value') > '0') {
					show = false;
				} else {
					show = true;
				}
				if (show) {
					me.showOverlay(row);
				}
			});
		});
	};
	
	me.showOverlay = function(row) {
		var tool_div = $('#'+prefix+'tool');
		var overlay_div = $('.users_account_overlay', tool_div);
		var form_div = $('.users_account_form', tool_div);
		form_div.css('display', 'none');
		overlay_div.css('display', 'block');
/*		
		if (!overlay) {
			overlay = $('<div class="users_account_overlay" />')
				.html($('.users_account_overlay', tool_div).html())
				.css('position', 'absolute')
				.appendTo('body');
		}
		overlay.css('left', tool_div.offset().left+'px');
		overlay.css('top', tool_div.offset().top+'px');
		overlay.width(tool_div.width());
		overlay.height(tool_div.height());
		overlay.css('display', 'block');
*/		
		var import_button = $('.import_from_facebook', tool_div);
		import_button.click(function(event) { 
			$('#'+prefix+'first_name').attr('value', row.first_name);
			$('#'+prefix+'last_name').attr('value', row.last_name);
			$('#'+prefix+'gender').val(row.sex);
			if ('length' in row.meeting_sex) {
				if (row.meeting_sex.length > 1) {
					$('#'+prefix+'desired_gender').val('either');
				} else {
					$('#'+prefix+'desired_gender').val(row.meeting_sex[0]);
				}
			}
			var parts = row.birthday_date.split('/');
			$('#'+prefix+'birthday_month').val(parseInt(parts[0]));
			$('#'+prefix+'birthday_day').val(parseInt(parts[1]));
			$('#'+prefix+'birthday_year').val(parseInt(parts[2]));
			/*overlay.css('display', 'none');*/
			overlay_div.css('display', 'none');
			form_div.css('display', 'block');
			return false;
		});
		var myself_button = $('.enter_it_myself', tool_div);
		myself_button.click(function(event) { 
			overlay_div.css('display', 'none');
			form_div.css('display', 'block');
			return false;
		});
	}
}

/*
 * users/avatar tool
 */

Pie.constructors['users_avatar_tool'] =
Pie.Users.Avatar = function(prefix) {

	// constructor & private declarations
	var me = this;
	
	me.init = function() {
		
	};
	
	me.ready = function() {
		
	};
	
};
