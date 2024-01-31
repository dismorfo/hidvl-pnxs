<?php

/**
 * @file
 * home.php
 */

/**
 * Home function.
 */
function home() {
  try {
    return [
      'template' => 'home.html',
      'data' => [
        'title' => 'Home',
        'items' => [],
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
