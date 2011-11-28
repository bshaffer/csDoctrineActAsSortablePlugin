<?php

require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test();

$categories = Doctrine_Core::getTable('SortableArticleCategory')->findAll();

$t->info('Create Sortable Sample Set');

    Doctrine_Core::getTable('SortableArticleUniqueBy')
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
    $a3->Category = $categories[1];
    $a3->save();

    $a4 = new SortableArticleUniqueBy();
    $a4->name = 'Fourth Article';
    $a4->Category = $categories[1];
    $a4->save();

    $a5 = new SortableArticleUniqueBy();
    $a5->name = 'Fifth Article';
    $a5->Category = $categories[1];
    $a5->save();

$t->info('Assert articles have the correct position');

    $t->is($a1['position'], 1, 'First item saved has position of 1 (first in category 1)');
    $t->is($a2['position'], 2, 'Second item saved has position of 2 (second in category 1)');
    $t->is($a3['position'], 1, 'Third item saved has position of 1 (first in category 2)');
    $t->is($a4['position'], 2, 'Fourth item saved has position of 2 (second in category 2)');
    $t->is($a5['position'], 3, 'Fifth item saved has position of 3 (third in category 2)');

$t->info('Test Demote and Promote');

    $a1->demote(); doctrine_refresh($a2);
    $t->is($a1['position'], 2, 'First item now has position of 2');
    $t->is($a2['position'], 1, 'Second item now has position of 1');

    $a3->demote(); doctrine_refresh($a4);
    $t->is($a3['position'], 2, 'Third item now has position of 2');
    $t->is($a4['position'], 1, 'Fourth item now has position of 1');
    $t->is($a5['position'], 3, 'Fifth item still has a position of 3');
    
$t->info('Test Removing an item - items after it should be promoted');

    $a2->delete(); doctrine_refresh($a1);
    $t->is($a1['position'], 1, '"First item" has been promoted to "1" from "2"');
    
    $a4->delete(); doctrine_refresh($a3);
    $t->is($a3['position'], 1, '"Third item" has been promoted to "1" from "2"');
    $t->is($a5['position'], 2, '"Fifth item" has been promoted to "2" from "3"');
    
$t->info('Test Moving an item to a different category with an item already at the same rank');
    
    try {
      $a1->Category = $categories[1];
      $a1->save();
    } catch (Doctrine_Connection_Sqlite_Exception $e) {
      $t->info('WARNING: Doctrine_Connection_Sqlite_Exception caught.');
    }
    doctrine_refresh($a1);
    $t->is($a1['category_id'], $categories[1]['id'], sprintf('"First item" has been moved to %s', $a3['Category']['name']));
    $t->is($a1['position'], 3, '"First item" has been moved to "3" from "1"');

$t->info('Test deleting a collection of sortable items');
    
    $d1 = new SortableArticleUniqueBy();
    $d1->name = 'ArticleUniqueBy To Delete 1';
    $d1->Category = $categories[2];
    $d1->save();

    $d2 = new SortableArticleUniqueBy();
    $d2->name = 'ArticleUniqueBy To Delete 2';
    $d2->Category = $categories[2];
    $d2->save();
    
    $d3 = new SortableArticleUniqueBy();
    $d3->name = 'ArticleUniqueBy To Delete 3';
    $d3->Category = $categories[2];
    $d3->save();

    $d4 = new SortableArticleUniqueBy();
    $d4->name = 'ArticleUniqueBy To Delete 4';
    $d4->Category = $categories[2];
    $d4->save();
    
    $collection = Doctrine_Core::getTable('SortableArticleUniqueBy')
        ->createQuery()
        ->where('category_id = ?', $categories[2]['id'])
        ->execute();

    $t->is($collection->count(), 4, 'Three items exist in the Doctrine Collection to be deleted');
    
    $collection->delete();
    
    $t->is($collection->count(), 0, 'No items in collection - they have been deleted');
    
    $t->ok(!$d1->exists(), '"ArticleUniqueBy To Delete 1" has been removed');
    $t->ok(!$d2->exists(), '"ArticleUniqueBy To Delete 2" has been removed');
    $t->ok(!$d3->exists(), '"ArticleUniqueBy To Delete 3" has been removed');
    $t->ok(!$d4->exists(), '"ArticleUniqueBy To Delete 4" has been removed');
