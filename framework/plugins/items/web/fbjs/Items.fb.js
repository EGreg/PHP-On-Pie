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
	me.onPhotoAdded = null;
	me.onPhotoExists = null;
	
	me.init = function() {
		
	};
	
	me.ready = function() {
		var photos_div = document.getElementById(prefix+'photos');
		var onPhotoClick = function(evt) {
			if (!me.onPhotoClickAction) {
				me.onPhotoClickAction = Pie.Items.urls['items/addPhoto'];
			}
			if (!Pie.isEmpty(me.onPhotoClick)) {
				Pie.handle(me.onPhotoClick);
				return;
			}
			// Otherwise, do the default behavior, which is
			// basically to send a POST request  to items/addPhoto
			var ajax = new Ajax();
			ajax.responseType = Ajax.JSON;
			ajax.ondone = function(data) {
				if ('errors' in data) {
					alert(data.errors[0].message);
					return;
				}
				// Photo has been processed
				var parts = data.slots.result.split(' ', 3);
				var callback = null, message = null;
				switch (parts[0]) {
					case 'exists':
						callback = me.onPhotoExists;
						message = "The photo already exists in the system.";
						break;
					case 'added':
					default:
						callback = me.onPhotoAdded;
						message = "The photo has been added.";
						break;
				}
				if (typeof(callback) === 'string') {
					document.setLocation(callback);
				} else {
					if (Pie.handle(callback, this, parts) === 0) {
						var dialog = new Dialog(Dialog.DIALOG_PIEUP);
						dialog.showMessage('Success', message);
					}
				}
			};
			var url = Pie.ajaxExtend(me.onPhotoClickAction, 'result');
			var form = this.getParentNode();
			var data = Pie.hash(form);
			ajax.post(url, data);
		};
		var images = photos_div.getElementsByTagName('img');
		for (var i=0; i < images.length; ++i) {
			images[i].addEventListener('click', onPhotoClick);
		}
		
		var albums_select = document.getElementById(prefix+'albums');
		albums_select.setSelectedIndex(0);
		albums_select.addEventListener('change', function(e) {
			var form = document.getElementById(prefix+'form');
			photos_div.setInnerFBML(fbml[prefix+'throbber']);
			var url = Pie.ajaxExtend(form.getAction(), 'fbml_photo_list');
			var ajax = new Ajax();
			ajax.responseType = Ajax.JSON;
			ajax.ondone = function(data) {
				if ('errors' in data) {
					var dialog = new Dialog(Dialog.DIALOG_PIEUP);
					dialog.showMessage('Error', data.errors[0].message);
				}
				photos_div.setInnerFBML(data.slots.fbml_photo_list);
				var images = photos_div.getElementsByTagName('img');
				for (var i=0; i < images.length; ++i) {
					images[i].addEventListener('click', onPhotoClick);
				}
			};
			ajax.post(url, {"aid": albums_select.getValue()});
		});

		var upload_input = document.getElementById(prefix+'upload');
		upload_input.addEventListener('change', function(e) {
			var ext = upload_input.getValue().split('.').pie().toLowerCase();
			switch (ext) {
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
					var form = document.getElementById(prefix+'form');
					form.submit();
					break;
				default:
					var dialog = new Dialog(Dialog.DIALOG_PIEUP);
					dialog.showMessage(
						'Wrong file type', 
						"You can only upload files ending in .jpg, .jpeg, .gif, .png"
					);
					upload_input.setValue('');
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
