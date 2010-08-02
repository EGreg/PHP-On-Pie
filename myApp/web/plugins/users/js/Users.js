Pie.Users = function() {
	var me = {
		facebookApps: {} // this info gets added by the server, on the page
	};
	
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
			'perms': 'email,publish_stream' // the permissions to ask for
		};
		var o = $.extend(defaults, options);
		
		var dest;
		function onConnect(response) {
			if (!o.accountStatusUrl) {
				onComplete(response);
				return;
			}
			var url = Pie.ajaxExtend(o.accountStatusUrl, 'accountStatus');
			$.getJSON(url, {}, function(response) {
				// DEBUGGING: For debugging purposes
				if (!response.slots) {
					if (response.errors && response.errors[0].message) {
						alert(response.errors[0].message);
					}
					return;
				}

				if ((!o.onRequireComplete)
				 || response.slots.accountStatus === 'complete') {
					onComplete(response);
				} else if (response.slots.accountStatus === 'refresh') {
					// we are logged in, refresh the page
					document.location.reload(true);
					return;
				} else {
					// take the user to the profile page
					// which will show the account process,
					// until completed -- and then the profile!
					if (typeof(o.onRequireComplete) == 'function') {
						o.onRequireComplete(response);
						return;
					}
					if (!o.onRequireComplete
					 || typeof(o.onRequireComplete) == 'string') {
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
			if (o.onCancel) {
				switch (typeof(o.onCancel)) {						
				case 'string':
					document.location = o.onCancel;
					break;
				case 'function':
					o.onCancel(response);
					break;
				}
			}
		}
		
		function onComplete(response) {
			if (typeof(o.onSuccess) == 'function') {
				o.onSuccess(response);
				return;
			}
			if (!o.onSuccess || typeof(o.onSuccess) !== 'string') {
				alert('Need a url in the onSuccess option');
				return;
			}
			dest = o.onSuccess;
			if (window.location != dest) {
				window.location = dest;
			} else {
				window.location.reload(true);
			}
		}
		
		me.initFacebook();
		if (o.tryQuietly) {
			FB.getLoginStatus(function(response) {
				if (response.session) {
					onConnect(response);
				} else {
					onCancel(response);
				}
			});
		} else {
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
			'onSuccess': urls[Pie.app+'/welcome']
		};
		var o = Pie.extend(defaults, options);
		if (!o.url) {
			return false;
		}
		
		var callback = function(response) {
			if (response.slots && response.slots.script) {
				// This script is coming from our server - it's safe.
				try {
					eval(response.slots.script);
				} catch (e) {
					alert(e);
				}
			}
			me.initFacebook();
			FB.logout(function(response) {
				if (typeof o.onSuccess === 'function') {
					o.onSuccess();
				} else if (typeof o.onSuccess === 'string') {
					document.location = o.onSuccess;
				}
			});
		}
		$.post(Pie.ajaxExtend(o.url, 'script'), {"logout": true}, callback, "json");
		return true;
	};
	
	return me;
}();









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
