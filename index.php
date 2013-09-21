<?php

/**
 * JotMap
 *
 * @author Fredric Mitchell
 * @link http://jotmap.jotform.io
 * @copyright 2013 Fredric Mitchell
 */

// Load composer libraries.
require 'vendor/autoload.php';

$app = new \Slim\Slim(array(
  'debug' => true
));

$app->get('/', function() use ($app) {
  $app->render('map.php');
});

$app->run();

