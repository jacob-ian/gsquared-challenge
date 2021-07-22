  </main>

<?php get_template_part("template-parts/footer/footer-widgets.php"); ?>

<footer class="footer">
  <?php $logo =
    get_template_directory_uri() . "/assets/images/corporate-logo.png"; ?>
  <img class="footer-logo" alt="Corporate Credit Cards" src="<?php echo $logo; ?>" />

</footer>

<?php wp_footer(); ?>
</body>
</html>
