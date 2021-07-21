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

?>
