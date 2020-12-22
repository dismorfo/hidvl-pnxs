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

function render($args) {
  $template = $args['template'];
  $data = $args['data'];
  $loader = new FilesystemLoader(__DIR__ . '/../templates');
  // Instantiate our Twig.
  $twig = new Environment($loader);
  $twig->addGlobal('ga', $_ENV['GA']);
  $appRoot = $_ENV['APP_ROOT'];
  if ($appRoot == '/') {
    $appRoot = '';
  }
  $twig->addGlobal('media_service', $_ENV['media_service']);
  $twig->addGlobal('appRoot', $appRoot);
  $twig->addGlobal('ga_ua', $_ENV['GAUA']);
  $twig->addGlobal('bobcat_url', $_ENV['bobcat_url']);
  return $twig->render($template, $data);
}
