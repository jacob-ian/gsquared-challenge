<?php

class EasyFAQs_Config
{
	public static function all_themes()
	{
		return apply_filters('easy_faqs_themes', self::free_themes());
	}

	public static function free_themes()
	{
		//array of free themes that are available
		//includes names
		return array(
			'free_themes' => array(
				'free_themes' => 'Basic Themes',
				'default_style' => 'Default Theme',
				'no_style' 		=> 'No Theme'
			),
			'office' => array(
				'office' => 'Office Theme',
				'office-gray' => 'Office Theme - Gray',
				'office-red' => 'Office Theme - Red',
				'office-blue' => 'Office Theme - Blue',
				'office-green' => 'Office Theme - Green',
				'office-skyblue' => 'Office Theme - Sky Blue',
				'office-teal' => 'Office Theme - Teal',
				'office-purple' => 'Office Theme - Purple',
				'office-gold' => 'Office Theme - Gold',
				'office-manilla' => 'Office Theme - Manilla',
				'office-orange' => 'Office Theme - Orange',
			)
		);		
	}
	
	public static function output_theme_selector($field_id, $field_name, $current = '')
	{
?>		
		<select class="widefat" id="<?php echo $field_id ?>" name="<?php echo $field_name; ?>">
			<?php
				$themes = self::all_themes();
				foreach ($themes as $group_slug => $group_themes)
				{
					$skip_next = true;
					foreach ($group_themes as $theme_slug => $theme_name) {
						$disabled = is_array($theme_name) && isset($theme_name['disabled']) ? $theme_name['disabled'] : false;
						$theme_name = is_array($theme_name) ? $theme_name['name'] : $theme_name;
						$disabled_attr = ($disabled ? 'disabled="disabled"' : 'xx');
						if ($skip_next) {
							printf('<optgroup label="%s">', $theme_name);
							$skip_next = false;
							continue;
						}
						$selected_attr = ( strcmp($theme_slug, $current) == 0 ) ? 'selected="selected"' : '';
						printf('<option value="%s" %s %s>%s</option>', $theme_slug, $selected_attr, $disabled_attr, $theme_name);
					}
					echo '</optgroup>';
				}
			?>
		</select>
<?php
	}
	
	public static function output_upgrade_link()
	{
		if ( !isValidFAQKey() ) {
			echo '<em><a target="_blank" href="https://goldplugins.com/our-plugins/easy-faqs-details/upgrade-to-easy-faqs-pro/?utm_source=wp_widgets&utm_campaign=widget_themes">Upgrade To Unlock All 100+ Pro Themes!</a></em>';
		}
	}
}