<?php
/*
This file is part of Easy FAQs.

Easy FAQs is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Easy FAQs is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Easy FAQs.  If not, see <http://www.gnu.org/licenses/>.

Shout out to http://www.makeuseof.com/tag/how-to-create-wordpress-widgets/ for the help
*/

class searchFAQsWidget extends WP_Widget
{
	function __construct(){
		$widget_ops = array('classname' => 'searchFAQsWidget', 'description' => 'Displays an FAQs Search Form.' );
		parent::__construct('searchFAQsWidget', 'Easy FAQs Search', $widget_ops);
	}
	
	function searchFAQsWidget(){		
		$this->__construct();
	}

	function form($instance){
		global $easy_faqs;
		
		if($easy_faqs->is_pro){
			$instance = wp_parse_args( 
				(array) $instance,
				array( 
					'title' => '',
					'show_category_select' => 0
				)
			);
			$title = $instance['title'];
			$show_category_select = $instance['show_category_select'];
			?>
			<div class="gp_widget_form_wrapper">
				<p>
					<label for="<?php echo $this->get_field_id('title'); ?>"><span class="hide_in_popup">Widget </span>Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label>
				</p>
				<h4 class="show_in_popup" style="display:none">Search Form Options</h4>
				<p>
					<label for="<?php echo $this->get_field_id('show_category_select'); ?>">
						<input  name="<?php echo $this->get_field_name('show_category_select'); ?>" type="hidden" value="0" />
						<input  id="<?php echo $this->get_field_id('show_category_select'); ?>" name="<?php echo $this->get_field_name('show_category_select'); ?>" type="checkbox" value="1" <?php if ( !empty($show_category_select) ): ?>checked="checked"<?php endif; ?> /> 
						<?php _e('Allow the user to select a category'); ?>
					</label>
				</p>
			</div>
			<?php
		} else {
			?>
			<div class="gp_widget_form_wrapper">
				<p><strong>Please Note:</strong><br/> This Feature Requires Easy FAQs Pro.</p>
				<p><a href="https://goldplugins.com/our-plugins/easy-faqs-details/upgrade-to-easy-faqs-pro/?utm_source=submit_faqs_widget&utm_campaign=upgrade" target="_blank"><?php echo $easy_faqs->get_str('FAQ_UPGRADE_TEXT'); ?></a></p>
			</div>
			<?php
		}
		
	}

	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['show_category_select'] = $new_instance['show_category_select'];
		return $instance;
	}

	function widget($args, $instance)
	{
		global $easy_faqs;
		global $easy_faqs_in_widget;
		$easy_faqs_in_widget = true;
		
		extract($args, EXTR_SKIP);

		echo $before_widget;
		$title = !empty($instance['title'])
				 ? apply_filters('widget_title', $instance['title'])
				 : '';
		$show_category_select = !empty($instance['show_category_select'])
								? $instance['show_category_select']				 
								: 0;

		if (!empty($title)) {
			echo $before_title . $title . $after_title;
		}
		
		//currently accepts no arguments, but expects this empty array
		$atts = compact(
			'show_category_select'
		);
		
		if($easy_faqs->is_pro){		
			echo $easy_faqs->SearchFAQs->outputSearchForm($atts);
		}

		echo $after_widget;
	} 
}
?>