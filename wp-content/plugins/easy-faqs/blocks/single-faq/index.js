( 
	function( wp ) {
	/**
	 * Registers a new block provided a unique name and an object defining its behavior.
	 * @see https://github.com/WordPress/gutenberg/tree/master/blocks#api
	 */
	var registerBlockType = wp.blocks.registerBlockType;
	/**
	 * Returns a new element of given type. Element is an abstraction layer atop React.
	 * @see https://github.com/WordPress/gutenberg/tree/master/element#element
	 */
	var el = wp.element.createElement;
	/**
	 * Retrieves the translation of text.
	 * @see https://github.com/WordPress/gutenberg/tree/master/i18n#api
	 */
	var __ = wp.i18n.__;
	
	var get_theme_group_label = function(theme_group_key) {
		if ( typeof(easy_faqs_admin_single_faq.theme_group_labels[theme_group_key]) !== 'undefined' ) {
			return easy_faqs_admin_single_faq.theme_group_labels[theme_group_key];
		}
		return 'Themes';
	};	

	var build_post_options = function(posts) {
		var opts = [
			{
				label: 'Select a Question',
				value: ''
			}
		];

		// build list of options from goals
		for( var i in posts ) {
			post = posts[i];
			opts.push( 
			{
				label: post.title.rendered,
				value: post.id
			});
		}
		return opts;
	};	

	var get_theme_options = function() {
		var theme_opts = [];
		for( theme_group in easy_faqs_admin_single_faq.themes ) {
			for ( theme_name in easy_faqs_admin_single_faq.themes[theme_group] ) {
				// skip the fields which were meant as optgroup labels
				if ( theme_name == theme_group ) {
					continue;
				}
				theme_opts.push({
					label: easy_faqs_admin_single_faq.themes[theme_group][theme_name],
					value: theme_name,
				});				
			}
		}
		return theme_opts;
	};
	
	var extract_label_from_options = function (opts, val) {
		var label = '';
		for (j in opts) {
			if ( opts[j].value == val ) {
				label = opts[j].label;
				break;
			}										
		}
		return label;
	};
	
	var checkbox_control = function (label, checked, onChangeFn) {
		// add checkboxes for which fields to display
		var controlOptions = {
			checked: checked,
			label: label,
			value: '1',
			onChange: onChangeFn,
		};	
		return el(  wp.components.CheckboxControl, controlOptions );
	};

	var iconGroup = [];
	iconGroup.push(	el(
			'path',
			{ d: "M0 0h24v24H0z", fill: 'none' }
		)
	);
	iconGroup.push(	el(
			'path',
			{ d: "M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"}
		)
	);
	
	var iconEl = el(
		'svg', 
		{ width: 24, height: 24 },
		iconGroup
	);	
	
	/**
	 * Every block starts by registering a new block type definition.
	 * @see https://wordpress.org/gutenberg/handbook/block-api/
	 */
	registerBlockType( 'easy-faqs/single-faq', {
		/**
		 * This is the display title for your block, which can be translated with `i18n` functions.
		 * The block inserter will show this name.
		 */
		title: __( 'Single Question' ),

		/**
		 * Blocks are grouped into categories to help users browse and discover them.
		 * The categories provided by core are `common`, `embed`, `formatting`, `layout` and `widgets`.
		 */
		category: 'easy-faqs',

		/**
		 * Optional block extended support features.
		 */
		supports: {
			// Removes support for an HTML mode.
			html: false,
		},

		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 * @see https://wordpress.org/gutenberg/handbook/block-edit-save/#edit
		 *
		 * @param {Object} [props] Properties passed from the editor.
		 * @return {Element}       Element to render.
		 */
		edit: wp.data.withSelect( function( select ) {
					return {
						posts: select( 'core' ).getEntityRecords( 'postType', 'faq' )
					};
				} ) ( function( props ) {
							var retval = [];
							var inspector_controls = [],
								id = props.attributes.id || '',
								faq_title = props.attributes.faq_title || '',
								theme = props.attributes.theme || '',
								width = props.attributes.width || '',
								show_title = typeof(props.attributes.show_title) != 'undefined' ? props.attributes.show_title : false,
								use_excerpt = typeof(props.attributes.use_excerpt) != 'undefined' ? props.attributes.use_excerpt : false,
								show_thumbs = typeof(props.attributes.show_thumbs) != 'undefined' ? props.attributes.show_thumbs : true,
								show_position = typeof(props.attributes.show_position) != 'undefined' ? props.attributes.show_position : true,
								show_date = typeof(props.attributes.show_date) != 'undefined' ? props.attributes.show_date : true,
								show_other = typeof(props.attributes.show_other) != 'undefined' ? props.attributes.show_other : true,
								hide_view_more = typeof(props.attributes.hide_view_more) != 'undefined' ? props.attributes.hide_view_more : true,
								output_schema_markup = typeof(props.attributes.output_schema_markup) != 'undefined' ? props.attributes.output_schema_markup : true,
								show_rating = typeof(props.attributes.show_rating) != 'undefined' ? props.attributes.show_rating : 'stars',
								focus = props.isSelected;
								
						if ( !! focus || ! id.length ) {
							
							retval.push( el('h3', { className: 'block-heading' }, __('Easy FAQs - Single Question') ) );
							
							// add <select> to choose the faq
							var opts = build_post_options(props.posts);
							var controlOptions = {
								label: __('Select a Question:'),
								value: id,
								onChange: function( newVal ) {
									faq_title = extract_label_from_options(opts, newVal);
									props.setAttributes({
										id: newVal,
										faq_title: faq_title
									});
								},
								options: opts,
							};
						
							retval.push(
									el(  wp.components.SelectControl, controlOptions )
							);
							
							var theme_fields = [];
							
							// add <select> to choose the Theme
							// note: Gutenburg's select control does not currently support optgroups
							var controlOptions = {
								label: __('Select a Theme:'),
								value: theme,
								onChange: function( newVal ) {
									props.setAttributes({
										theme: newVal
									});
								},
								options: get_theme_options(),
							};
						
							theme_fields.push(
								el(  wp.components.SelectControl, controlOptions )
							);

							if ( !easy_faqs_admin_single_faq.is_pro ) {
								theme_fields.push(
									el(  
										'a',
										{ 
											className: 'gp-upgrade-link',
											href: 'http://goldplugins.com/our-plugins/easy-faqs-details/upgrade-to-easy-faqs-pro/?utm_source=gutenburg_inspector&utm_campaign=pro_themes',
											target: '_blank',
										},
										__('Unlock All 100+ Pro Themes!') )
								);
							}
							
							inspector_controls.push(							
								el (
									wp.components.PanelBody,
									{
										title: __('Theme'),
										className: 'gp-panel-body',
										initialOpen: true,
									},
									theme_fields
								)
							);

							retval.push(
								el( wp.editor.InspectorControls, {}, inspector_controls ) 
							);

						}

						else {
							var inner_fields = [];
							inner_fields.push( el('h3', { className: 'block-heading' }, 'Easy FAQs - Single Question') );							
							inner_fields.push( el('blockquote', {}, faq_title) );
							retval.push( el('div', {'className': 'easy-faqs-editor-not-selected'}, inner_fields ) );
						}
						
				return el( 'div', { className: 'easy-faqs-single-faq-editor'}, retval );
			} ),

		/**
		 * The save function defines the way in which the different attributes should be combined
		 * into the final markup, which is then serialized by Gutenberg into `post_content`.
		 * @see https://wordpress.org/gutenberg/handbook/block-edit-save/#save
		 *
		 * @return {Element}       Element to render.
		 */
		save: function() {
			return null;
		},
		attributes: {
			id: {
				type: 'string',
			},
			faq_title: {
				type: 'string',
			},
			theme: {
				type: 'string',
			},
			width: {
				type: 'string',
			},
			show_title: {
				type: 'boolean',
			},
			use_excerpt: {
				type: 'boolean',
			},
			show_thumbs: {
				type: 'boolean',
			},
			show_position: {
				type: 'boolean',
			},
			show_date: {
				type: 'boolean',
			},
			show_other: {
				type: 'boolean',
			},
			hide_view_more: {
				type: 'boolean',
			},
			output_schema_markup: {
				type: 'boolean',
			},
			show_rating: {
				type: 'string',
			},
		},
		icon: iconEl,
	} );
} )(
	window.wp
);
