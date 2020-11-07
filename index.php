<?php

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/include/common.php';

$router = new \Bramus\Router\Router();

$menu = array();

$menu['/hidvl/(\w+)'] = array(
  'label' => 'View resource and metadata',
  'verbs' => array(
    'GET' => array(
      'file' => './routes/hidvl-metadata-player.php',
      'callback' => 'init',
      'delivery' => 'render',
    ),
  ),
);

// Custom 404 Handler
$router->set404(function () {
  header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
  echo '404, route not found!';
});

// register routes
foreach ($menu as $route => $leaf) {
  if (
    isset($leaf['verbs']) &&
    isset($leaf['verbs'][$_SERVER['REQUEST_METHOD']])
  ) {
    $verb = strtolower($_SERVER['REQUEST_METHOD']);
    $router->$verb($route, function (...$params) use ($leaf) {
      include_once $leaf['verbs'][$_SERVER['REQUEST_METHOD']]['file'];
      if (
        function_exists($leaf['verbs'][$_SERVER['REQUEST_METHOD']]['callback']) 
      ) {
        call_user_func(
          $leaf['verbs'][$_SERVER['REQUEST_METHOD']]['delivery'],
          call_user_func(
            $leaf['verbs'][$_SERVER['REQUEST_METHOD']]['callback'], 
            $params
          )
        );
      }
    });
  }
}

$router->run();
