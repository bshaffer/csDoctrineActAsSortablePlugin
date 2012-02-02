<?php

require_once dirname(__FILE__).'/../bootstrap/bootstrap.php';

$t = new lime_test();

$t->info('Create Sortable Sample Set');

    Doctrine::getTable('SortableArticle')
        ->createQuery()->delete()->execute();
    
    $a1 = new SortableArticle();
    $a1->name = 'First Article';
    $a1->save();

    $a2 = new SortableArticle();
    $a2->name = 'Second Article';
    $a2->save();

    $a3 = new SortableArticle();
    $a3->name = 'Third Article';
    $a3->save();

$t->info('Assert articles have thecorrect position');

    $t->is($a1['position'], 1, 'First item saved has position of 1');
    $t->is($a2['position'], 2, 'Second item saved has position of 2');
    $t->is($a3['position'], 3, 'Third item saved has position of 3');

$t->info('Test Demote and Promote');

    $a1->demote(); doctrine_refresh($a2);
    $t->is($a1['position'], 2, 'First item now has position of 2');
    $t->is($a2['position'], 1, 'Second item now has position of 1');

    $a3->promote(); doctrine_refresh($a1);
    $t->is($a1['position'], 3, 'First item now has position of 3');
    $t->is($a3['position'], 2, 'Third item now has position of 2');

$t->info('Test Table Method "sort()"');

    $table = $a1->getTable();
    $sort = array($a1['id'], $a2['id'], $a3['id']);
    $table->sort($sort);
    
    $t->comment('Sort to original position (before promote/demote)');
    $articles = $table->findAllSorted();
    $t->is($articles->count(), 3, 'Three articles returned for "findAllSorted()" method');
    $t->is($articles[0]['id'], $a1['id'], 'First item is now first');
    $t->is($articles[1]['id'], $a2['id'], 'Second item is now second');
    $t->is($articles[2]['id'], $a3['id'], 'Third item is last');

    $t->comment('Sort to previous position (after promote/demote)');
    $sort = array($a2['id'], $a3['id'], $a1['id']);
    $table->sort($sort);
    $articles = $table->findAllSorted();
    $t->is($articles->count(), 3, 'Three articles returned for "findAllSorted()" method');
    $t->is($articles[0]['id'], $a2['id'], 'Second item first (same as position)');
    $t->is($articles[1]['id'], $a3['id'], 'Third item second (same as position)');
    $t->is($articles[2]['id'], $a1['id'], 'First item last (same as position)');
    
$t->info('Test Removing an item - items after it should be promoted');

    $t->is($a2->getFinalPosition(), 3, '"Final Position" is "3" before the item is deleted');
    $a3->delete(); doctrine_refresh($a1);
    $t->is($a1['position'], 2, '"First item" has been promoted to "2" from "3"');
    $t->is($a2['position'], 1, '"Second item" stays at position "1"');
    $t->is($a2->getFinalPosition(), 2, '"Final Position" is now "2"');

$t->info('Test "moveToPosition" method');

    $a4 = new SortableArticle();
    $a4->name = 'Fourth Article';
    $a4->save();

    $t->is($a4['position'], 3, 'The new article is placed at the end');
    $a4->moveToPosition(1); doctrine_refresh($a1);
    $t->is($a1['position'], 3, 'The 2nd-positioned item has been bumped up');

$t->info('Test deleting a collection of sortable items');
    
    $d1 = new SortableArticle();
    $d1->name = 'Article To Delete 1';
    $d1->save();

    $d2 = new SortableArticle();
    $d2->name = 'Article To Delete 2';
    $d2->save();
    
    $d3 = new SortableArticle();
    $d3->name = 'Article To Delete 3';
    $d3->save();
    
    $d4 = new SortableArticle();
    $d4->name = 'Article To Delete 4';
    $d4->save();
    
    $collection = new Doctrine_Collection('SortableArticle');
    $collection[] = $d1;
    $collection[] = $d2;
    $collection[] = $d3;
    $collection[] = $d4;
    
    $collection->delete();
    
    $t->ok(!$d1->exists(), '"Article To Delete 1" has been removed');
    $t->ok(!$d2->exists(), '"Article To Delete 2" has been removed');
    $t->ok(!$d3->exists(), '"Article To Delete 3" has been removed');
    $t->ok(!$d4->exists(), '"Article To Delete 4" has been removed');
