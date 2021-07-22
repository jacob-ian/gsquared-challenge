  </main>

<?php get_sidebar("footer"); ?>
<footer class="footer">
  <?php $logo =
    get_template_directory_uri() . "/assets/images/corporate-logo.png"; ?>
  <img class="footer-logo" alt="Corporate Credit Cards" src="<?php echo $logo; ?>" />
</footer>

<?php wp_footer(); ?>
</body>
</html>
