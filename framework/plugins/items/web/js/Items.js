Pie.Items = function() {
	return {
		urls: {}
	};
}();

Pie.constructors['items_addPhoto_tool'] = function(prefix) {
	
	// constructor
	var me = this;
	
	me.onPhotoClick = {};
	me.onPhotoClickAction = null;
	me.onPhotoAdded = {};
	me.onPhotoExists = {};
	
	me.init = function() {
		
	};
	
	me.ready = function() {	
		var photos_div = $('#'+prefix+'photos');
		var onPhotoClick = function(evt) {
			var $this = $(this);
			if (Pie.handle(me.onPhotoClick)) {
				return;
			}
			// Otherwise, do the default behavior, which is
			// basically to send a POST request  to items/addPhoto
			if (!me.onPhotoClickAction) {
				me.onPhotoClickAction = Pie.Items.urls['items/addPhoto'];
			}
			var url = Pie.ajaxExtend(me.onPhotoClickAction, 'result');
			$.post(
				url, 
				$this.closest('form').serialize(),
				function (data) {
					if ('errors' in data) {
						alert(data.errors[0].message);
						return;
					}
					var parts = data.slots.result.split(' ', 3);
					var callback = null, message = null;
					switch (parts[0]) {
						case 'added':
							callback = me.onPhotoAdded;
							message = "The photo already exists in the system.";
							break;
						case 'exists':
						default:
							callback = me.onPhotoExists;
							message = "The photo has been added.";
							break;
					}
					// Photo has been processed
					if (typeof(callback) === 'string') {
						if (document.location == callback) {
							document.location.reload(true);
						} else {
							document.location = callback;
						}
					} else {
						if (Pie.handle(callback, this, parts) === 0) {
							alert(message);
						}
					}
				},
				'json'
			);
				
		};
		$('img', photos_div).click(onPhotoClick);
		
		var albums_select = $('#'+prefix+'albums');
		albums_select.selectedIndex = 0;
		albums_select.bind('change', function(e) {
			var form = $('#'+prefix+'form');
			photos_div.html($('#'+prefix+'throbber_html').html());
			var url = Pie.ajaxExtend(form.attr('action'), 'photo_list');
			var ondone = function(data) {
				if ('errors' in data) {
					alert(data.errors[0].message);
					return;
				}
				photos_div.html(data.slots.photo_list);
				$('img', photos_div).click(onPhotoClick);
				
			};
			$.getJSON(url, {"aid": albums_select.val()}, ondone);
		});

		var upload_input = $('#'+prefix+'upload');
		upload_input.bind('change', function(e) {
			var ext = upload_input.val().split('.').pie().toLowerCase();
			switch (ext) {
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
					$('#'+prefix+'form').submit();
					break;
				default:
					alert("You can only upload files ending in .jpg, .jpeg, .gif, .png");
					upload_input.val('');
					return;
			}	
		});		
	};
}



/*

function removeArticle(articleId) {
     var dialog = new Dialog(Dialog.DIALOG_CONTEXTUAL);
     dialog.setContext(document.getElementById("removeSpan"+articleId));
     dialog.showChoice('Confirm Removal', articles["id"+articleId], 'Yes', 'Cancel');
     dialog.onconfirm = function() {
          document.setLocation('http://apps.facebook.com/facebookdocs/removearticle.php?article='+articleId);
     };
}

*/
