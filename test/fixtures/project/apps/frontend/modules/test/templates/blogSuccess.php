<div class="test_title">
  <?php echo editable_content_tag('h1', $blog, 'title') ?>
</div>

<div class="test_link">
  <?php echo editable_content_tag('div', $blog, array('link_url', 'link_text'), array('partial' => 'test/link', 'mode' => 'fancybox')) ?>
</div>

<div class="test_body">
  <?php echo editable_content_tag('div', $blog, null, array('partial' => 'test/body', 'form' => 'BlogBodyForm', 'form_partial' => 'test/bodyForm', 'class' => 'body')) ?>
</div>