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

/**
 * Logs an exception to the PHP error log (or wherever error_log() is
 * configured to write, e.g. Apache's error log / stderr in the Docker
 * container) so failures are visible server-side even though the user only
 * ever sees a generic error page.
 */
function log_error(Throwable $e, $context = '') {
  $prefix = $context ? "[$context] " : '';
  error_log(sprintf(
    '%s%s: %s in %s:%d',
    $prefix,
    get_class($e),
    $e->getMessage(),
    $e->getFile(),
    $e->getLine()
  ));
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

/**
 * Parses the redirected query string (set by the .htaccess front-controller
 * rewrite) into an associative array. Shared by args() and render() so the
 * REDIRECT_QUERY_STRING parsing logic only lives in one place.
 */
function redirect_query_params() {
  $redirectQueryString = (isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : false);

  if (!$redirectQueryString) {
    return [];
  }

  $decodedQueryString = urldecode($redirectQueryString);
  parse_str($decodedQueryString, $queryParams);

  return $queryParams;
}

function args($arg = null) {
  $queryParams = redirect_query_params();

  if ($arg && isset($queryParams[$arg])) {
    return $queryParams[$arg];
  }

}

function render($args) {

  $data = $args['data'];

  $queryParams = redirect_query_params();

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
