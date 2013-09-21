<?php

/**
 * JotMap
 *
 * @author Fredric Mitchell
 * @link http://jotmap.jotform.io
 * @copyright 2013 Fredric Mitchell
 */

// API Keys.
$cloudmade_api_key = '41339be4c5064686b781a5a00678de62';
$jotform_api_key = '6c8a6d9bad5a660e7a76f53de0cbb065';

// Mongo URI.
$mongo_uri = "mongodb://jotmap:_j0tm4p_@ds043338.mongolab.com:43338/jotmap";

// Jotform setup.
$jotformAPI = new JotForm($jotform_api_key);
$forms = $jotformAPI->getForms();

// Get latest form submissions.
$form = reset($forms);
$submissions = $jotformAPI->getFormSubmissions($form['id']);

// Setup Mongo for storing already geocoded submissions.
$uriParts = explode("/", $mongo_uri);
$dbName = $uriParts[3];
$client = new MongoClient($mongo_uri);
$db = $client->$dbName;
$mongo_submissions = $db->submissions;

$submission_geocodes = $display_markers = $marker_ids = $addresses = array();

// Go through each submission to geocode.
foreach ($submissions as $submission) {

  // Setup variables.
  $id = $submission['id'];
  $form_id = $submission['form_id'];
  $address = implode(', ', $submission['answers'][5]['answer']);
  $name = implode(' ', $submission['answers'][4]['answer']);

  // Build Mongo query parameters.
  $query = array(
    'id' => $submission['id'],
    'form_id' => $submission['form_id']
  );

  // See if record is already in db.
  $existing = $mongo_submissions->findOne($query);

  // If already in Mongo, no need to geocode
  if (!isset($existing)) {
    // Setup geocoder.
    $adapter = new \Geocoder\HttpAdapter\CurlHttpAdapter();
    $geocoder = new \Geocoder\Geocoder();
    $chain = new \Geocoder\Provider\ChainProvider(
      array(
        new \Geocoder\Provider\CloudMadeProvider($adapter, $cloudmade_api_key),
      )
    );
    $geocoder->registerProvider($chain);

    // Try to geocode.
    try {
      $geocode = $geocoder->geocode($address);
      $longitude = $geocode->getLongitude();
      $latitude = $geocode->getLatitude();

      // Save longitude, latitude, submission id, and form id.
      $submission_geocodes[] = array(
        'long' => $longitude,
        'lat' => $latitude,
        'id' => $id,
        'form_id' => $form_id,
      );
    } catch (Exception $e) {
      echo $e->getMessage();
    }

  } else {
    $latitude = $existing['lat'];
    $longitude = $existing['long'];
  }

  // Check to see if geocode is 'center of world', i.e. could not find.
  if (($latitude != '-3.37232') && ($longitude != '36.85787')) {
    $display_markers[] = array(
      'marker_id' => 'marker' . $id,
      'lat' => $latitude,
      'long' => $longitude,
    );

    $address_display = " ($latitude, $longitude)";

    $marker_ids[] = 'marker' . $id;
    $label = 'success';
  } else {
    $address_display = " (Could not find location)";
    $label = 'warning';
  }

  $addresses[] = array(
    'name' => $name,
    'address' => $address . $address_display,
    'label' => $label,
  );

  // Unset variables to reduce chance of duplicates.
  unset($address, $name, $longitude, $latitude);
}

// If no new geocodes, no need to insert into Mongo.
if (!empty($submission_geocodes)) {
  $mongo_submissions->batchInsert($submission_geocodes);
}

// Mustache setup.
$m = new JotMapMustache;

// Mustache hashes.
$hash = array(
  'title' => 'Foo Bar',
  'tabledata' => $addresses,
);

$mapdata_hash = array(
  'cloudmade_api_key' => $cloudmade_api_key,
  'markerdata' => $display_markers,
  'marker_ids' => implode(', ', $marker_ids),
);

// Mustache template loading.
$map = $m->loadTemplate('map');
$table = $m->loadTemplate('table');
$mapdata = $m->loadTemplate('mapdata');

?>

<!DOCTYPE html>
<html>

<head>
  <title>Jotmap on Bootstrap</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="//cdn.leafletjs.com/leaflet-0.6.4/leaflet.css" />
  <link rel="stylesheet" href="../css/jotmap.css">
  <script src="//cdn.leafletjs.com/leaflet-0.6.4/leaflet.js"></script>
</head>

<body>

<div class="container">

  <div class="row">

    <div class="col-lg-12"><h1>JotMap on Bootstrap</h1></div>

    <?php echo $map->render($hash); // Render the map. ?>
    <?php echo $table->render($hash); // Render the table. ?>

  </div>

</div>

<script src="//code.jquery.com/jquery.js"></script>
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
<script>
  <?php echo $mapdata->render($mapdata_hash); // Render mapdata. ?>
</script>
</body>

</html>