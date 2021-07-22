<?php
// Easy FAQs Welcome Page template

ob_start();
$learn_more_url = 'https://goldplugins.com/special-offers/upgrade-to-easy-faqs-pro/?utm_source=easy_faqs_free&utm_campaign=welcome_screen_upgrade&utm_content=col_1_learn_more';
$settings_url = menu_page_url('easy-faqs-settings', false);
$pro_registration_url = menu_page_url('easy-faqs-license-information', false);
$utm_str = '?utm_source=easy_faqs_free&utm_campaign=welcome_screen_help_links';
?>

<p class="aloha_intro">Thank you for installing Easy FAQs! These links will help you get up and running.</p>

<div class="three_col">
	<div class="col">
		<?php if ($is_pro): ?>
			<h3>Easy FAQs Pro: Active</h3>
			<p class="plugin_activated">Easy FAQs Pro is licensed and active.</p>
			<a href="<?php echo $pro_registration_url; ?>">Registration Settings</a>
		<?php else: ?>
			<h3>Upgrade To Pro</h3>
			<p>Easy FAQs Pro is the Professional, fully-functional version of Easy FAQs, which features technical support and access to all features and themes.</p>
			<a class="button" href="<?php echo $learn_more_url; ?>">Click Here To Learn More</a>
			<br>
			<br>
			<p><strong>Already upgraded to Easy FAQs Pro?</strong></p>
			<a href="<?php echo $pro_registration_url; ?>">Click here to enter your Easy FAQs Pro API Key</a>			
		<?php endif; ?>
	</div>
	<div class="col">
		<h3>Getting Started</h3>
		<ul>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/easy-faqs-installation-and-usage-instructions/<?php echo $utm_str; ?>">Getting Started With Easy FAQs</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/easy-faqs-installation-and-usage-instructions/<?php echo $utm_str; ?>#add_a_new_faq">How To Create Your First FAQ</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/easy-faqs-accordion-style-example/<?php echo $utm_str; ?>">Accordion Style FAQs</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/frequently-asked-questions/<?php echo $utm_str; ?>">Frequently Asked Questions (FAQs)</a></li>
			<li><a href="https://goldplugins.com/contact/<?php echo $utm_str; ?>">Contact Technical Support</a></li>
		</ul>
	</div>
	<div class="col">
		<h3>Further Reading</h3>
		<ul>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/<?php echo $utm_str; ?>">Easy FAQs Documentation</a></li>
			<li><a href="https://wordpress.org/support/plugin/easy-faqs/<?php echo $utm_str; ?>">WordPress Support Forum</a></li>
			<li><a href="https://goldplugins.com/documentation/easy-faqs-documentation/<?php echo $utm_str; ?>">Recent Changes</a></li>
			<li><a href="https://goldplugins.com/<?php echo $utm_str; ?>">Gold Plugins Website</a></li>
		</ul>
	</div>
</div>

<p class="aloha_tip"><strong>Tip:</strong> You can always access this page via the <strong>Easy FAQs Settings &raquo; About Plugin</strong> menu.</p>

<div class="continue_to_settings">
	<p><a href="<?php echo $settings_url; ?>">Continue to Basic Settings &raquo;</a></p>
</div>

<?php 
$content =  ob_get_contents();
ob_end_clean();
return $content;