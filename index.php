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
  'debug' => FALSE
));

$app->error(function (\Exception $e) use ($app) {
    $app->render('error.php');
});

$app->get('/', function() use ($app) {
  $app->render('step1.php');
});

$app->get('/step2', function() use ($app) {
  $app->render('step2.php');
});

$app->get('/step3', function() use ($app) {
  $app->render('step3.php');
});

$app->get('/map', function() use ($app) {
  $app->render('map.php');
});

$app->run();

