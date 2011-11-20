(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('aqtwe');
	
	tinymce.create('tinymce.plugins.aqtwePlugin', {
		createControl: function(n, cm) {
			switch (n) {
				case 'aqtwe':
					var aqtwe_nr_of_buttons = typeof(aqtwe_button_text);
					var i = 0;
					var aqtwe_options ="";
					var mlb = cm.createListBox('aqtwe', {
						title : 'aqtwe.aqtwe_select_header',
						onselect : function(value) {
							var selection = tinyMCE.activeEditor.selection.getContent();
							var markiere = true;
							
							switch (value) {
								case 'nix' :
								case 'nixnix' :
									var markiere = false;
								break;
								default :			
							}
							
							if ( markiere == true ) {
								tinyMCE.activeEditor.selection.setContent(aqtwe_start_tag[value]+selection+aqtwe_end_tag[value]);
							} 
						}
					});
					
					// add values to the listbox
					if (aqtwe_nr_of_buttons != "undefined") {
						for (i = 0; i < aqtwe_button_text.length; i++) {
							mlb.add(aqtwe_button_text[i], String(i));
						}
					} else {
						mlb.add('aqtwe.aqtwe_select_error', 'nixnix');
					}
				
					// Return the new listbox instance
					return mlb;
				break;
			}
			return null;
		},
		
		getInfo : function() {
			return {
				longname : 'aqtwe Plugin for tinyMCE 3+ in Wordpress',
				author : 'Tim Berger',
				authorurl : 'http://undeuxoutrois.de/',
				infourl : 'http://undeuxoutrois.de/',
				version : '1'
			};
		} 		
	});

	// Register plugin
	tinymce.PluginManager.add('aqtwe', tinymce.plugins.aqtwePlugin);
})();