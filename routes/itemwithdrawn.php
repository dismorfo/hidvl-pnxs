<?php

function itemwithdrawn($args) {
  try {
    return [
      'template' => 'item-withdrawn.html',
      'data' => [
        'title' => 'Item Withdrawn: Hemispheric Institute Digital Video Library',
        'body' => [
          'label' => 'This item has been withdrawn from the collection.',
          'content' => 'Hemispheric Institute Digital Video Library.'
        ],
      ],
    ];
  }
  catch (Exception $e) {
    return [
      'template' => 'error.html',
      'data' => [
        'title' => 'Error',
        'body' => $e->getMessage(),
      ],
    ];
  }
}
