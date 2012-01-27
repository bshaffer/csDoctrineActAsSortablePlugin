<?php

require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test();

$categories = Doctrine_Core::getTable('SortableArticleCategory')->findAll();

$t->info('Create Sortable Sample Set');

    Doctrine_Core::getTable('SortableArticle')
        ->createQuery()->delete()->execute();

    $a1 = new SortableArticleUniqueBy();
    $a1->name = 'First Article';
    $a1->Category = $categories[0];
    $a1->save();

    $a2 = new SortableArticleUniqueBy();
    $a2->name = 'Second Article';
    $a2->Category = $categories[0];
    $a2->save();

    $a3 = new SortableArticleUniqueBy();
    $a3->name = 'Third Article';
    $a3->Category = $categories[0];
    $a3->save();

    $a4 = new SortableArticleUniqueBy();
    $a4->name = 'Fourth Article';
    $a4->Category = $categories[0];
    $a4->save();

    $a5 = new SortableArticleUniqueBy();
    $a5->name = 'Fifth Article';
    $a5->Category = $categories[0];
    $a5->save();

$t->info('Fetch articles and delete them like in batchDelete');

    $articles = Doctrine_Core::getTable('SortableArticleUniqueBy')
        ->createQuery()->execute();

    try {
      foreach($articles as $article) {
        $article->delete();
        $t->pass(sprintf('Successfully deleted article %s', $article['id']));
      }
    } catch (Exception $e) {
      $t->fail('Failure while batch-deleting ' . $e->getMessage());
    }
