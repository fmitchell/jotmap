<?php

// Load composer libraries.
require 'vendor/autoload.php';

// Jotform setup.
$jotform_api_key = '6c8a6d9bad5a660e7a76f53de0cbb065';
$jotformAPI = new JotForm($jotform_api_key);
$forms = $jotformAPI->getForms();

// Mustache setup.
$mustache_options = array(
  'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
  'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
);
$m = new Mustache_Engine($mustache_options);

// Mustache hashes.
$hash = array(
  'title' => 'Foo Bar',
);

// Mustache template loading.
$map = $m->loadTemplate('map');

?>

<!DOCTYPE html>
<html>

  <head>
    <title>Jotmap on Bootstrap</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">
  </head>

  <body>

    <div class="container">

      <div class="row">

        <h1>JotMap on Bootstrap</h1>
        <?php echo $map->render($hash); // Render the map. ?>

      </div>

    </div>

    <script src="//code.jquery.com/jquery.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
  </body>

</html>

