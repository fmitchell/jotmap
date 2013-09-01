<?php

// Load composer libraries.
require 'vendor/autoload.php';

// API Keys.
$cloudmade_api_key = '41339be4c5064686b781a5a00678de62';
$jotform_api_key = '6c8a6d9bad5a660e7a76f53de0cbb065';

// Jotform setup.
$jotformAPI = new JotForm($jotform_api_key);
$forms = $jotformAPI->getForms();

// Get latest form submissions.
$form = reset($forms);
$submissions = $jotformAPI->getFormSubmissions($form['id']);

// Setup geocoder.
$adapter = new \Geocoder\HttpAdapter\CurlHttpAdapter();
$geocoder = new \Geocoder\Geocoder();
$chain = new \Geocoder\Provider\ChainProvider(
  array(
    new \Geocoder\Provider\CloudMadeProvider($adapter, $cloudmade_api_key),
  )
);
$geocoder->registerProvider($chain);

// Geocode address.
$geocodes = array();
foreach ($submissions as $submission) {
  $address = implode(', ', $submission['answers'][5]['answer']);
  try {
    $geocode = $geocoder->geocode($address);
    $geocodes[] = array(
      'long' => $geocode->longitude,
      'lat' => $geocode->latitude,
    );
  } catch (Exception $e) {
    echo $e->getMessage();
  }
}

// Mustache setup.
$mustache_options = array(
  'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
  'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
);
$m = new Mustache_Engine($mustache_options);

// Mustache hashes.
$hash = array(
  'title' => 'Foo Bar',
  'cloudmade_api_key' => $cloudmade_api_key,
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
    <link rel="stylesheet" href="//cdn.leafletjs.com/leaflet-0.6.4/leaflet.css" />
    <!--[if lte IE 8]>
        <link rel="stylesheet" href="//cdn.leafletjs.com/leaflet-0.6.4/leaflet.ie.css" />
    <![endif]-->
    <link rel="stylesheet" href="css/jotmap.css">
    <script src="//cdn.leafletjs.com/leaflet-0.6.4/leaflet.js"></script>
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

