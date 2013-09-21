<?php

/**
 * Main code for Jotmap, including PHP and HTML
 *
 * @author Fredric Mitchell
 */

// Load composer libraries.
require 'vendor/autoload.php';

$app = new \Slim\Slim(array(
  'debug' => true
));

$app->get('/', function(){
  echo "Home Page";
});

$app->get('/map', function() use ($app) {
  $app->render('map.php');
});

$app->run();

