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
		if ( typeof(easy_faqs_admin_list_faqs.theme_group_labels[theme_group_key]) !== 'undefined' ) {
			return easy_faqs_admin_list_faqs.theme_group_labels[theme_group_key];
		}
		return 'Themes';
	};	

	var build_category_options = function(categories) {
		var opts = [
			{
				label: 'All Categories',
				value: ''
			}
		];

		// build list of options from goals
		for( var i in categories ) {
			cat = categories[i];
			opts.push( 
			{
				label: cat.name,
				value: cat.slug
			});
		}
		return opts;
	};	

	var get_theme_options = function() {
		var theme_opts = [];
		for( theme_group in easy_faqs_admin_list_faqs.themes ) {
			//theme_group_label = get_theme_group_label(theme_group);
			for ( theme_name in easy_faqs_admin_list_faqs.themes[theme_group] ) {
				theme_opts.push({
					label: easy_faqs_admin_list_faqs.themes[theme_group][theme_name],
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
	
	var text_control = function (label, value, className, onChangeFn) {
		var controlOptions = {
			label: label,
			value: value,
			className: className,
			onChange: onChangeFn,
		};
		return el(  wp.components.TextControl, controlOptions );
	};

	var radio_control = function (label, value, options, className, onChangeFn) {
		var controlOptions = {
			label: label,
			onChange: onChangeFn,
			options: options,
			selected: value,
			className: '',
		};
		return el(  wp.components.RadioControl, controlOptions );
	};

	var update_paginate_panel = function () {
		setTimeout( function () {
			var field_groups =  jQuery('.janus_editor_field_group');
			field_groups.each(function () {
				field_group = jQuery(this);
				var val = field_group.find(':checked').val();
				if ( 'max' == val ) {
					field_group.find('.field_per_page').show();
					field_group.find('.field_count').hide();
				}
				else if ( 'paginate' == val ) {
					field_group.find('.field_per_page').hide();
					field_group.find('.field_count').show();
				}
				else {
					field_group.find('.field_per_page').hide();
					field_group.find('.field_count').hide();
				}			
				
				return true;
			});
		}, 100 );
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
	registerBlockType( 'easy-faqs/faqs-by-category', {
		/**
		 * This is the display title for your block, which can be translated with `i18n` functions.
		 * The block inserter will show this name.
		 */
		title: __( 'FAQs by Category' ),

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
						categories: select( 'core' ).getEntityRecords( 'taxonomy', 'easy-faq-category', {
							order: 'asc',
							orderby: 'id'
						})
					};
				} ) ( function( props ) {
							var retval = [];
							var inspector_controls = [],
								display_fields = [],
								view_all_fields = [],
								quick_links_fields = [],
								quicklinks = typeof(props.attributes.quicklinks) != 'undefined' ? props.attributes.quicklinks : false,
								quick_links_columns = typeof(props.attributes.quick_links_columns) != 'undefined' ? props.attributes.quick_links_columns : '',
								id = props.attributes.id || '',
								show_faq_image = props.attributes.id || '',
								use_excerpt = typeof(props.attributes.use_excerpt) != 'undefined' ? props.attributes.use_excerpt : false,
								faq_read_more_link = props.attributes.faq_read_more_link || '',
								faq_read_more_link_text = props.attributes.faq_read_more_link_text || '',
								count = props.attributes.count || '',
								order = props.attributes.order || '',
								orderby = props.attributes.orderby || '',
								theme = props.attributes.theme || '',
								focus = props.isSelected;
								
							
						
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

						if ( !easy_faqs_admin_list_faqs.is_pro ) {
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
									initialOpen: false,
								},
								theme_fields
							)
						);
													
						var category_fields = [];
						
						category_fields.push( 
							text_control( __('Count:'), count, function( newVal ) {
								props.setAttributes({
									count: newVal,
								});
							})
						);

						var orderby_opts = [
							{
								label: 'Title',
								value: 'title',
							},
							{
								label: 'Random',
								value: 'rand',
							},
							{
								label: 'ID',
								value: 'id',
							},
							{
								label: 'Author',
								value: 'author',
							},
							{
								label: 'Name',
								value: 'name',
							},
							{
								label: 'Date',
								value: 'date',
							},
							{
								label: 'Last Modified',
								value: 'last_modified',
							},
							{
								label: 'Parent ID',
								value: 'parent_id',
							},
						];

						// add <select> to choose the Order By Field
						var controlOptions = {
							label: __('Order By:'),
							value: orderby,
							onChange: function( newVal ) {
								props.setAttributes({
									orderby: newVal
								});
							},
							options: orderby_opts,
						};
					
						category_fields.push(
							el(  wp.components.SelectControl, controlOptions )
						);

						var order_opts = [
							{
								label: 'Ascending (A-Z)',
								value: 'asc',
							},
							{
								label: 'Descending (Z-A)',
								value: 'desc',
							},
						];

						// add <select> to choose the Order (asc, desc)
						var controlOptions = {
							label: __('Order:'),
							value: order,
							onChange: function( newVal ) {
								props.setAttributes({
									order: newVal
								});
							},
							options: order_opts,
						};
					
						category_fields.push(
							el(  wp.components.SelectControl, controlOptions )
						);
						
						inspector_controls.push(							
							el (
								wp.components.PanelBody,
								{
									title: __('Filter'),
									className: 'gp-panel-body',
									initialOpen: false,
								},
								category_fields
							)
						);

						display_fields.push( 
							checkbox_control( __('Show FAQ Image'), show_faq_image, function( newVal ) {
								props.setAttributes({
									show_faq_image: newVal,
								});
							})
						);

						display_fields.push( 
							checkbox_control( __('Use Excerpt of Answer'), use_excerpt, function( newVal ) {
								props.setAttributes({
									use_excerpt: newVal,
								});
							})
						);
						
						inspector_controls.push( 
							el (
								wp.components.PanelBody,
								{
									title: __('Display Fields'),
									className: 'gp-panel-body',
									initialOpen: false,
								},
								el('div', { className: 'janus_editor_field_group' }, display_fields)
							)
						);

						quick_links_fields.push( 
							checkbox_control( __('Display Quick Links'), quicklinks, function( newVal ) {
								props.setAttributes({
									quicklinks: newVal,
								});
							})
						);
						
						quick_links_fields.push( 
							text_control( __('Number of Columns'), quick_links_columns, function( newVal ) {
								props.setAttributes({
									quick_links_columns: newVal,
								});
							})
						);
						
						inspector_controls.push( 
							el (
								wp.components.PanelBody,
								{
									title: __('Quick Links'),
									className: 'gp-panel-body',
									initialOpen: false,
								},
								el('div', { className: 'janus_editor_field_group' }, quick_links_fields)
							)
						);

						view_all_fields.push( 
							text_control( __('View All Link URL:'), faq_read_more_link, function( newVal ) {
								props.setAttributes({
									faq_read_more_link: newVal,
								});
							})
						);

						view_all_fields.push( 
							text_control( __('View All Link Text:'), faq_read_more_link_text, function( newVal ) {
								props.setAttributes({
									faq_read_more_link_text: newVal,
								});
							})
						);
						
						inspector_controls.push( 
							el (
								wp.components.PanelBody,
								{
									title: __('View All FAQs Link'),
									className: 'gp-panel-body',
									initialOpen: false,
								},
								el('div', { className: 'janus_editor_field_group' }, view_all_fields)
							)
						);

						retval.push(
							el( wp.editor.InspectorControls, {}, inspector_controls ) 
						);

						var inner_fields = [];
						inner_fields.push( el('h3', { className: 'block-heading' }, 'Easy FAQs - List of FAQs') );
						inner_fields.push( el('blockquote', { className: 'faq-list-placeholder' }, __('A list of your FAQs grouped by category.') ) );
						retval.push( el('div', {'className': 'easy-faqs-editor-not-selected'}, inner_fields ) );

				return el( 'div', { className: 'easy-faqs-faqs-by-category-editor'}, retval );
				
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
			category: {
				type: 'string',
			},
			faq_read_more_link: {
				type: 'string',
			},
			faq_read_more_link_text: {
				type: 'string',
			},
			use_excerpt: {
				type: 'string',
			},
			quicklinks: {
				type: 'string',
			},
			quick_links_columns: {
				type: 'string',
			},
			show_faq_image: {
				type: 'string',
			},
			count: {
				type: 'string',
			},
			theme: {
				type: 'string',
			},
			order: {
				type: 'string',
			},
			orderby: {
				type: 'string',
			},
		},
		icon: iconEl,
	} );
} )(
	window.wp
);
