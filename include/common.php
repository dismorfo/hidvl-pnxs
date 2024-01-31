<?php

/**
 * @file
 * common.php
 */

use Dotenv\Dotenv;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');

$dotenv->load();

function add_common_headers() {
  $date = gmdate('D, d M Y H:i:s');
  header('Access-Control-Allow-Methods: GET');
  header('Access-Control-Allow-Origin: *');
  header("Last-Modified: $date GMT");
  header('Content-Language: en-US');
  header('Cache-Control: public, max-age=0, s-maxage=300', FALSE);
}

function json($data) {
  add_common_headers();
  header('Content-Type: application/json');
  echo json_encode($data), PHP_EOL;
}

function html($args) {
  add_common_headers();
  header('Content-Type: text/html; charset=utf-8');
  echo render($args);
}

function args($arg = null) {
  // Get the value from REDIRECT_QUERY_STRING
  $redirectQueryString = (isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : false);

  if ($redirectQueryString ) {
    // URL decode the values
    $decodedQueryString = urldecode($redirectQueryString);
    // Parse the query string into an array
    parse_str($decodedQueryString, $queryParams);

  }

  if ($arg && isset($queryParams[$arg])) {
    return $queryParams[$arg];
  }

}

function render($args) {

  $data = $args['data'];

  // Get the value from REDIRECT_QUERY_STRING
  $redirectQueryString = (isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : false);

  if ($redirectQueryString ) {
    // URL decode the values
    $decodedQueryString = urldecode($redirectQueryString);
    // Parse the query string into an array
    parse_str($decodedQueryString, $queryParams);

  }

  // Validate and sanitize individual parameters
  if ((isset($queryParams['wt']) && $queryParams['wt'] == 'json') || (isset($_GET) && isset($_GET['wt']) && $_GET['wt'] == 'json')) {
    unset($data['ld']);
    return json($data);
  }

  $template = $args['template'];
  
  $loader = new FilesystemLoader(__DIR__ . '/../templates');
  
  // Instantiate our Twig.
  $twig = new Environment($loader);

  $ga = (isset($_ENV['GA'])) ? $_ENV['GA'] : '';
  $ga_ua = (isset($_ENV['GAUA'])) ? $_ENV['GAUA'] : '';
  $twig->addGlobal('ga', $ga);
  $appRoot = $_ENV['APP_ROOT'];
  
  if ($appRoot == '/') {
    $appRoot = '';
  }

  $twig->addGlobal('media_service', $_ENV['media_service']);
  $twig->addGlobal('appRoot', $appRoot);
  $twig->addGlobal('ga_ua', $ga_ua);
  $twig->addGlobal('bobcat_url', $_ENV['bobcat_url']);

  return $twig->render($template, $data);

}
