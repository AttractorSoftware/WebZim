/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.dialog.add( 'link', function( editor ) {
	var plugin = CKEDITOR.plugins.link;
	// Handles the event when the "Target" selection box is changed.
	var targetChanged = function() {
			var dialog = this.getDialog(),
				popupFeatures = dialog.getContentElement( 'target', 'popupFeatures' ),
				targetName = dialog.getContentElement( 'target', 'linkTargetName' ),
				value = this.getValue();

			if ( !popupFeatures || !targetName )
				return;

			popupFeatures = popupFeatures.getElement();
			popupFeatures.hide();
			targetName.setValue( '' );

			switch ( value ) {
				case 'frame':
					targetName.setLabel( editor.lang.link.targetFrameName );
					targetName.getElement().show();
					break;
				case 'popup':
					popupFeatures.show();
					targetName.setLabel( editor.lang.link.targetPopupName );
					targetName.getElement().show();
					break;
				default:
					targetName.setValue( value );
					targetName.getElement().hide();
					break;
			}

		};

	// Handles the event when the "Type" selection box is changed.
	var linkTypeChanged = function() {
			var dialog = this.getDialog(),
				partIds = [ 'urlOptions', 'anchorOptions', 'emailOptions' ],
				typeValue = this.getValue(),
				uploadTab = dialog.definition.getContents( 'upload' ),
				uploadInitiallyHidden = uploadTab && uploadTab.hidden;

			if ( typeValue == 'url' ) {
				if ( editor.config.linkShowTargetTab )
					dialog.showPage( 'target' );
				if ( !uploadInitiallyHidden )
					dialog.showPage( 'upload' );
			} else {
				dialog.hidePage( 'target' );
				if ( !uploadInitiallyHidden )
					dialog.hidePage( 'upload' );
			}

			for ( var i = 0; i < partIds.length; i++ ) {
				var element = dialog.getContentElement( 'info', partIds[ i ] );
				if ( !element )
					continue;

				element = element.getElement().getParent().getParent();
				if ( partIds[ i ] == typeValue + 'Options' )
					element.show();
				else
					element.hide();
			}

			dialog.layout();
		};

	// Loads the parameters in a selected link to the link dialog fields.
	var javascriptProtocolRegex = /^javascript:/,
		emailRegex = /^mailto:([^?]+)(?:\?(.+))?$/,
		emailSubjectRegex = /subject=([^;?:@&=$,\/]*)/,
		emailBodyRegex = /body=([^;?:@&=$,\/]*)/,
		anchorRegex = /^#(.*)$/,
		urlRegex = /^((?:http|https|ftp|news):\/\/)?(.*)$/,
		selectableTargets = /^(_(?:self|top|parent|blank))$/,
		encodedEmailLinkRegex = /^javascript:void\(location\.href='mailto:'\+String\.fromCharCode\(([^)]+)\)(?:\+'(.*)')?\)$/,
		functionCallProtectedEmailLinkRegex = /^javascript:([^(]+)\(([^)]+)\)$/;

	var popupRegex = /\s*window.open\(\s*this\.href\s*,\s*(?:'([^']*)'|null)\s*,\s*'([^']*)'\s*\)\s*;\s*return\s*false;*\s*/;
	var popupFeaturesRegex = /(?:^|,)([^=]+)=(\d+|yes|no)/gi;

	var parseLink = function( editor, element ) {
			var href = ( element && ( element.data( 'cke-saved-href' ) || element.getAttribute( 'href' ) ) ) || '',
				javascriptMatch, emailMatch, anchorMatch, urlMatch,
				retval = {};

			if ( ( javascriptMatch = href.match( javascriptProtocolRegex ) ) ) {
				if ( emailProtection == 'encode' ) {
					href = href.replace( encodedEmailLinkRegex, function( match, protectedAddress, rest ) {
						return 'mailto:' +
							String.fromCharCode.apply( String, protectedAddress.split( ',' ) ) +
							( rest && unescapeSingleQuote( rest ) );
					});
				}
				// Protected email link as function call.
				else if ( emailProtection ) {
					href.replace( functionCallProtectedEmailLinkRegex, function( match, funcName, funcArgs ) {
						if ( funcName == compiledProtectionFunction.name ) {
							retval.type = 'email';
							var email = retval.email = {};

							var paramRegex = /[^,\s]+/g,
								paramQuoteRegex = /(^')|('$)/g,
								paramsMatch = funcArgs.match( paramRegex ),
								paramsMatchLength = paramsMatch.length,
								paramName, paramVal;

							for ( var i = 0; i < paramsMatchLength; i++ ) {
								paramVal = decodeURIComponent( unescapeSingleQuote( paramsMatch[ i ].replace( paramQuoteRegex, '' ) ) );
								paramName = compiledProtectionFunction.params[ i ].toLowerCase();
								email[ paramName ] = paramVal;
							}
							email.address = [ email.name, email.domain ].join( '@' );
						}
					});
				}
			}

			if ( !retval.type ) {
				if ( ( anchorMatch = href.match( anchorRegex ) ) ) {
					retval.type = 'anchor';
					retval.anchor = {};
					retval.anchor.name = retval.anchor.id = anchorMatch[ 1 ];
				}
				// Protected email link as encoded string.
				else if ( ( emailMatch = href.match( emailRegex ) ) ) {
					var subjectMatch = href.match( emailSubjectRegex ),
						bodyMatch = href.match( emailBodyRegex );

					retval.type = 'email';
					var email = ( retval.email = {} );
					email.address = emailMatch[ 1 ];
					subjectMatch && ( email.subject = decodeURIComponent( subjectMatch[ 1 ] ) );
					bodyMatch && ( email.body = decodeURIComponent( bodyMatch[ 1 ] ) );
				}
				// urlRegex matches empty strings, so need to check for href as well.
				else if ( href && ( urlMatch = href.match( urlRegex ) ) ) {
					retval.type = 'url';
					retval.url = {};
					retval.url.protocol = urlMatch[ 1 ];
					retval.url.url = urlMatch[ 2 ];
				} else
					retval.type = 'url';
			}

			// Load target and popup settings.
			if ( element ) {
				var target = element.getAttribute( 'target' );
				retval.target = {};
				retval.adv = {};

				// IE BUG: target attribute is an empty string instead of null in IE if it's not set.
				if ( !target ) {
					var onclick = element.data( 'cke-pa-onclick' ) || element.getAttribute( 'onclick' ),
						onclickMatch = onclick && onclick.match( popupRegex );
					if ( onclickMatch ) {
						retval.target.type = 'popup';
						retval.target.name = onclickMatch[ 1 ];

						var featureMatch;
						while ( ( featureMatch = popupFeaturesRegex.exec( onclickMatch[ 2 ] ) ) ) {
							// Some values should remain numbers (#7300)
							if ( ( featureMatch[ 2 ] == 'yes' || featureMatch[ 2 ] == '1' ) && !( featureMatch[ 1 ] in { height:1,width:1,top:1,left:1 } ) )
								retval.target[ featureMatch[ 1 ] ] = true;
							else if ( isFinite( featureMatch[ 2 ] ) )
								retval.target[ featureMatch[ 1 ] ] = featureMatch[ 2 ];
						}
					}
				} else {
					var targetMatch = target.match( selectableTargets );
					if ( targetMatch )
						retval.target.type = retval.target.name = target;
					else {
						retval.target.type = 'frame';
						retval.target.name = target;
					}
				}

				var me = this;
				var advAttr = function( inputName, attrName ) {
						var value = element.getAttribute( attrName );
						if ( value !== null )
							retval.adv[ inputName ] = value || '';
					};
				advAttr( 'advId', 'id' );
				advAttr( 'advLangDir', 'dir' );
				advAttr( 'advAccessKey', 'accessKey' );

				retval.adv.advName = element.data( 'cke-saved-name' ) || element.getAttribute( 'name' ) || '';
				advAttr( 'advLangCode', 'lang' );
				advAttr( 'advTabIndex', 'tabindex' );
				advAttr( 'advTitle', 'title' );
				advAttr( 'advContentType', 'type' );
				CKEDITOR.plugins.link.synAnchorSelector ? retval.adv.advCSSClasses = getLinkClass( element ) : advAttr( 'advCSSClasses', 'class' );
				advAttr( 'advCharset', 'charset' );
				advAttr( 'advStyles', 'style' );
				advAttr( 'advRel', 'rel' );
			}

			// Find out whether we have any anchors in the editor.
			var anchors = retval.anchors = [],
				i, count, item;

			// For some browsers we set contenteditable="false" on anchors, making document.anchors not to include them, so we must traverse the links manually (#7893).
			if ( CKEDITOR.plugins.link.emptyAnchorFix ) {
				var links = editor.document.getElementsByTag( 'a' );
				for ( i = 0, count = links.count(); i < count; i++ ) {
					item = links.getItem( i );
					if ( item.data( 'cke-saved-name' ) || item.hasAttribute( 'name' ) )
						anchors.push({ name: item.data( 'cke-saved-name' ) || item.getAttribute( 'name' ), id: item.getAttribute( 'id' ) } );
				}
			} else {
				var anchorList = new CKEDITOR.dom.nodeList( editor.document.$.anchors );
				for ( i = 0, count = anchorList.count(); i < count; i++ ) {
					item = anchorList.getItem( i );
					anchors[ i ] = { name: item.getAttribute( 'name' ), id: item.getAttribute( 'id' ) };
				}
			}

			if ( CKEDITOR.plugins.link.fakeAnchor ) {
				var imgs = editor.document.getElementsByTag( 'img' );
				for ( i = 0, count = imgs.count(); i < count; i++ ) {
					if ( ( item = CKEDITOR.plugins.link.tryRestoreFakeAnchor( editor, imgs.getItem( i ) ) ) )
						anchors.push({ name: item.getAttribute( 'name' ), id: item.getAttribute( 'id' ) } );
				}
			}

			// Record down the selected element in the dialog.
			this._.selectedElement = element;
			return retval;
		};

	var setupParams = function( page, data ) {
			if ( data[ page ] )
				this.setValue( data[ page ][ this.id ] || '' );
		};

	var setupPopupParams = function( data ) {
			return setupParams.call( this, 'target', data );
		};

	var setupAdvParams = function( data ) {
			return setupParams.call( this, 'adv', data );
		};

	var commitParams = function( page, data ) {
			if ( !data[ page ] )
				data[ page ] = {};

			data[ page ][ this.id ] = this.getValue() || '';
		};

	var commitPopupParams = function( data ) {
			return commitParams.call( this, 'target', data );
		};

	var commitAdvParams = function( data ) {
			return commitParams.call( this, 'adv', data );
		};

	function unescapeSingleQuote( str ) {
		return str.replace( /\\'/g, '\'' );
	}

	function escapeSingleQuote( str ) {
		return str.replace( /'/g, '\\$&' );
	}

	var emailProtection = editor.config.emailProtection || '';

	// Compile the protection function pattern.
	if ( emailProtection && emailProtection != 'encode' ) {
		var compiledProtectionFunction = {};

		emailProtection.replace( /^([^(]+)\(([^)]+)\)$/, function( match, funcName, params ) {
			compiledProtectionFunction.name = funcName;
			compiledProtectionFunction.params = [];
			params.replace( /[^,\s]+/g, function( param ) {
				compiledProtectionFunction.params.push( param );
			});
		});
	}

	function protectEmailLinkAsFunction( email ) {
		var retval,
			name = compiledProtectionFunction.name,
			params = compiledProtectionFunction.params,
			paramName, paramValue;

		retval = [ name, '(' ];
		for ( var i = 0; i < params.length; i++ ) {
			paramName = params[ i ].toLowerCase();
			paramValue = email[ paramName ];

			i > 0 && retval.push( ',' );
			retval.push( '\'', paramValue ? escapeSingleQuote( encodeURIComponent( email[ paramName ] ) ) : '', '\'' );
		}
		retval.push( ')' );
		return retval.join( '' );
	}

	function protectEmailAddressAsEncodedString( address ) {
		var charCode,
			length = address.length,
			encodedChars = [];
		for ( var i = 0; i < length; i++ ) {
			charCode = address.charCodeAt( i );
			encodedChars.push( charCode );
		}
		return 'String.fromCharCode(' + encodedChars.join( ',' ) + ')';
	}

	function getLinkClass( ele ) {
		var className = ele.getAttribute( 'class' );
		return className ? className.replace( /\s*(?:cke_anchor_empty|cke_anchor)(?:\s*$)?/g, '' ) : '';
	}

	var commonLang = editor.lang.common,
		linkLang = editor.lang.link;

	return {
		title: linkLang.title,
		minWidth: 350,
		minHeight: 230,
		contents: [
			{
			id: 'info',
			label: linkLang.info,
			title: linkLang.info,
			elements: [
                {
                    type: 'text',
                    id: 'url',
                    label: commonLang.url,
                    required: true,
                    onLoad: function() {
                        this.allowOnChange = true;
                    },
                    onKeyUp: function() {

                    },
                    onChange: function() {
                        if ( this.allowOnChange ) // Dont't call on dialog load.
                            this.onKeyUp();
                    },
                    validate: function() {
                        var dialog = this.getDialog();

                        if ( (/javascript\:/).test( this.getValue() ) ) {
                            alert( commonLang.invalidValue );
                            return false;
                        }

                        if ( this.getDialog().fakeObj ) // Edit Anchor.
                            return true;

                        var func = CKEDITOR.dialog.validate.notEmpty( linkLang.noUrl );
                        return func.apply( this );
                    },
                    setup: function( data ) {
                        this.allowOnChange = false;
                        if ( data.url )
                            this.setValue( data.url.url );
                        this.allowOnChange = true;

                    },
                    commit: function( data ) {
                        // IE will not trigger the onChange event if the mouse has been used
                        // to carry all the operations #4724
                        this.onChange();

                        if ( !data.url )
                            data.url = {};

                        data.url.url = this.getValue();
                        this.allowOnChange = false;
                    }
                }
            ]
            }
		],

		onShow: function() {
			var editor = this.getParentEditor(),
				selection = editor.getSelection(),
				element = null;

			// Fill in all the relevant fields if there's already one link selected.
			if ( ( element = plugin.getSelectedLink( editor ) ) && element.hasAttribute( 'href' ) )
				selection.selectElement( element );
			else
				element = null;

			this.setupContent( parseLink.apply( this, [ editor, element ] ) );
		},
		onOk: function() {
			var attributes = {},
				removeAttributes = [],
				data = {},
				me = this,
				editor = this.getParentEditor();

			this.commitContent( data );

			// Compose the URL.
			switch ( data.type || 'url' ) {
				case 'url':
					var protocol = ( data.url && data.url.protocol != undefined ) ? data.url.protocol : 'http://',
						url = ( data.url && CKEDITOR.tools.trim( data.url.url ) ) || '';
					attributes[ 'data-cke-saved-href' ] = url;
					break;
				case 'anchor':
					var name = ( data.anchor && data.anchor.name ),
						id = ( data.anchor && data.anchor.id );
					attributes[ 'data-cke-saved-href' ] = '#' + ( name || id || '' );
					break;
				case 'email':

					var linkHref,
					email = data.email,
						address = email.address;

					switch ( emailProtection ) {
						case '':
						case 'encode':
							{
								var subject = encodeURIComponent( email.subject || '' ),
									body = encodeURIComponent( email.body || '' );

								// Build the e-mail parameters first.
								var argList = [];
								subject && argList.push( 'subject=' + subject );
								body && argList.push( 'body=' + body );
								argList = argList.length ? '?' + argList.join( '&' ) : '';

								if ( emailProtection == 'encode' ) {
									linkHref = [ 'javascript:void(location.href=\'mailto:\'+',
																															protectEmailAddressAsEncodedString( address ) ];
									// parameters are optional.
									argList && linkHref.push( '+\'', escapeSingleQuote( argList ), '\'' );

									linkHref.push( ')' );
								} else
									linkHref = [ 'mailto:', address, argList ];

								break;
							}
						default:
							{
								// Separating name and domain.
								var nameAndDomain = address.split( '@', 2 );
								email.name = nameAndDomain[ 0 ];
								email.domain = nameAndDomain[ 1 ];

								linkHref = [ 'javascript:', protectEmailLinkAsFunction( email ) ];
							}
					}

					attributes[ 'data-cke-saved-href' ] = linkHref.join( '' );
					break;
			}

			// Popups and target.
			if ( data.target ) {
				if ( data.target.type == 'popup' ) {
					var onclickList = [ 'window.open(this.href, \'',
																			data.target.name || '', '\', \'' ];
					var featureList = [ 'resizable', 'status', 'location', 'toolbar', 'menubar', 'fullscreen',
																			'scrollbars', 'dependent' ];
					var featureLength = featureList.length;
					var addFeature = function( featureName ) {
							if ( data.target[ featureName ] )
								featureList.push( featureName + '=' + data.target[ featureName ] );
						};

					for ( var i = 0; i < featureLength; i++ )
						featureList[ i ] = featureList[ i ] + ( data.target[ featureList[ i ] ] ? '=yes' : '=no' );
					addFeature( 'width' );
					addFeature( 'left' );
					addFeature( 'height' );
					addFeature( 'top' );

					onclickList.push( featureList.join( ',' ), '\'); return false;' );
					attributes[ 'data-cke-pa-onclick' ] = onclickList.join( '' );

					// Add the "target" attribute. (#5074)
					removeAttributes.push( 'target' );
				} else {
					if ( data.target.type != 'notSet' && data.target.name )
						attributes.target = data.target.name;
					else
						removeAttributes.push( 'target' );

					removeAttributes.push( 'data-cke-pa-onclick', 'onclick' );
				}
			}

			// Advanced attributes.
			if ( data.adv ) {
				var advAttr = function( inputName, attrName ) {
						var value = data.adv[ inputName ];
						if ( value )
							attributes[ attrName ] = value;
						else
							removeAttributes.push( attrName );
					};

				advAttr( 'advId', 'id' );
				advAttr( 'advLangDir', 'dir' );
				advAttr( 'advAccessKey', 'accessKey' );

				if ( data.adv[ 'advName' ] )
					attributes[ 'name' ] = attributes[ 'data-cke-saved-name' ] = data.adv[ 'advName' ];
				else
					removeAttributes = removeAttributes.concat( [ 'data-cke-saved-name', 'name' ] );

				advAttr( 'advLangCode', 'lang' );
				advAttr( 'advTabIndex', 'tabindex' );
				advAttr( 'advTitle', 'title' );
				advAttr( 'advContentType', 'type' );
				advAttr( 'advCSSClasses', 'class' );
				advAttr( 'advCharset', 'charset' );
				advAttr( 'advStyles', 'style' );
				advAttr( 'advRel', 'rel' );
			}


			var selection = editor.getSelection();

			// Browser need the "href" fro copy/paste link to work. (#6641)
			attributes.href = attributes[ 'data-cke-saved-href' ];

			if ( !this._.selectedElement ) {
				var range = selection.getRanges( 1 )[ 0 ];

				// Use link URL as text with a collapsed cursor.
				if ( range.collapsed ) {
					// Short mailto link text view (#5736).
					var text = new CKEDITOR.dom.text( data.type == 'email' ? data.email.address : attributes[ 'data-cke-saved-href' ], editor.document );
					range.insertNode( text );
					range.selectNodeContents( text );
				}

				// Apply style.
				var style = new CKEDITOR.style({ element: 'a', attributes: attributes } );
				style.type = CKEDITOR.STYLE_INLINE; // need to override... dunno why.
				style.applyToRange( range );
				range.select();
			} else {
				// We're only editing an existing link, so just overwrite the attributes.
				var element = this._.selectedElement,
					href = element.data( 'cke-saved-href' ),
					textView = element.getHtml();

				element.setAttributes( attributes );
				element.removeAttributes( removeAttributes );

				if ( data.adv && data.adv.advName && CKEDITOR.plugins.link.synAnchorSelector )
					element.addClass( element.getChildCount() ? 'cke_anchor' : 'cke_anchor_empty' );

				// Update text view when user changes protocol (#4612).
				if ( href == textView || data.type == 'email' && textView.indexOf( '@' ) != -1 ) {
					// Short mailto link text view (#5736).
					element.setHtml( data.type == 'email' ? data.email.address : attributes[ 'data-cke-saved-href' ] );
				}

				selection.selectElement( element );
				delete this._.selectedElement;
			}
		},
		onLoad: function() {
			if ( !editor.config.linkShowAdvancedTab )
				this.hidePage( 'advanced' ); //Hide Advanded tab.

			if ( !editor.config.linkShowTargetTab )
				this.hidePage( 'target' ); //Hide Target tab.
		},
		// Inital focus on 'url' field if link is of type URL.
		onFocus: function() {
			var linkType = this.getContentElement( 'info', 'linkType' ),
				urlField;
			if ( linkType && linkType.getValue() == 'url' ) {
				urlField = this.getContentElement( 'info', 'url' );
				urlField.select();
			}
		}
	};
});

/**
 * The e-mail address anti-spam protection option. The protection will be
 * applied when creating or modifying e-mail links through the editor interface.
 *
 * Two methods of protection can be choosed:
 *
 * 1. The e-mail parts (name, domain and any other query string) are
 *     assembled into a function call pattern. Such function must be
 *     provided by the developer in the pages that will use the contents.
 * 2. Only the e-mail address is obfuscated into a special string that
 *     has no meaning for humans or spam bots, but which is properly
 *     rendered and accepted by the browser.
 *
 * Both approaches require JavaScript to be enabled.
 *
 *		// href="mailto:tester@ckeditor.com?subject=subject&body=body"
 *		config.emailProtection = '';
 *
 *		// href="<a href=\"javascript:void(location.href=\'mailto:\'+String.fromCharCode(116,101,115,116,101,114,64,99,107,101,100,105,116,111,114,46,99,111,109)+\'?subject=subject&body=body\')\">e-mail</a>"
 *		config.emailProtection = 'encode';
 *
 *		// href="javascript:mt('tester','ckeditor.com','subject','body')"
 *		config.emailProtection = 'mt(NAME,DOMAIN,SUBJECT,BODY)';
 *
 * @since 3.1
 * @cfg {String} [emailProtection='' (empty string = disabled)]
 * @member CKEDITOR.config
 */
