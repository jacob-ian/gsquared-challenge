<?php

add_theme_support("title-tag");

function gsquared_register_styles()
{
  $version = wp_get_theme()->get("Version");
  wp_enqueue_style(
    "gsquared-style",
    get_template_directory_uri() . "/style.css",
    [],
    $version,
    "all"
  );

  wp_enqueue_style(
    "google-fonts",
    "https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap",
    [],
    null,
    "all"
  );
}

add_action("wp_enqueue_scripts", "gsquared_register_styles");

function gsquared_register_scripts()
{
  wp_enqueue_script(
    "main",
    get_template_directory_uri() . "/assets/js/main.js",
    [],
    "1.0",
    true
  );
}

add_action("wp_enqueue_scripts", gsquared_register_scripts());

add_action("widgets_init", "register_footer_sidebar");
function register_footer_sidebar()
{
  register_sidebar([
    "id" => "footer",
    "name" => __("Footer Sidebar"),
    "description" => __("A Sidebar for the FAQ"),
    "before_widget" =>
      "<h2>Frequently Asked Questions</h2><h3>Fleetwood $50 Cashback Program</h3>",
    "after_widget" => "",
    "before_title" => "",
    "after_title" => "",
  ]);
}

function exists_in_db($invoice_number)
{
  global $wpdb;

  $row_count = $wpdb->get_var(
    "SELECT COUNT(`invoice_number`) FROM `wpcf7_submissions` WHERE invoice_number = $invoice_number"
  );

  if ($row_count >= 1) {
    return true;
  }

  $wpdb->insert("wpcf7_submissions", ["invoice_number" => $invoice_number]);
  return false;
}

add_action(
  "wpcf7_before_send_mail",
  function ($contact_form, &$abort, $submission) {
    $form_id = $contact_form->id();
    if ($form_id !== 24) {
      return;
    }

    $invoice_number = $submission->get_posted_data("invoice-number");

    if (exists_in_db($invoice_number)) {
      $abort = true;
      $submission->set_status("validation_failed");
      $submission->set_response(
        $contact_form->filter_message(
          "This invoice number has already been submitted."
        )
      );
    }
  },
  10,
  3
);
?>
