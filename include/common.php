<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');

$dotenv->load();

function add_common_headers() {
  header('Access-Control-Allow-Methods: GET');
  header('Access-Control-Allow-Origin: *');
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  header('Content-Language: en-US');
  header('Cache-Control: public, max-age=0, s-maxage=300', false);
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

  // Specify our Twig templates location
  $loader = new Twig\Loader\FilesystemLoader( __DIR__ . '/../templates');

  // Instantiate our Twig
  $twig = new Twig\Environment($loader);

  $twig->addGlobal('appRoot', getenv('APP_ROOT'));

  return $twig->render($template, $data);

}
