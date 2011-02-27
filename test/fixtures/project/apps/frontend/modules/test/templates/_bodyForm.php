<?php echo $form['body']->renderError() ?>
<?php echo $form['body']->render() ?>

<?php foreach ($form->getErrorSchema()->getErrors() as $key => $error): ?>
  <?php echo $key ?>
<?php endforeach; ?>