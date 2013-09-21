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

$jotaddress = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotaddresschoice']);
$jotlabel = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotlabelchoice']);
$key = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotformapi']);
$choice = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotformchoice']);
$user = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['user']);

// Mongo URI.
$mongo_uri = "mongodb://jotmap:_j0tm4p_@ds043338.mongolab.com:43338/jotmap";

// Setup Mongo for storing already geocoded submissions.
$uriParts = explode("/", $mongo_uri);
$dbName = $uriParts[3];
$client = new MongoClient($mongo_uri);
$db = $client->$dbName;
$mongo_submissions = $db->submissions;

// Jotform setup.
$jotformAPI = new JotForm($key);

// Get latest form submissions.
$submissions = $jotformAPI->getFormSubmissions($choice);

$submission_geocodes = $display_markers = $marker_ids = $addresses = array();

// Go through each submission to geocode.
foreach ($submissions as $submission) {

  // Setup variables.
  $id = $submission['id'];
  $form_id = $submission['form_id'];
  $address = implode(', ', $submission['answers'][$jotaddress]['answer']);
  $name = implode(' ', $submission['answers'][$jotlabel]['answer']);

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
    'address' => $address,
    'label' => $label,
    'coordinates' => $address_display,
  );

  // Unset variables to reduce chance of duplicates.
  unset($address, $name, $longitude, $latitude);
}

// If no new geocodes, no need to insert into Mongo.
if (!empty($submission_geocodes)) {
  $mongo_submissions->batchInsert($submission_geocodes);
}

// Mustache hashes.
$hash = array(
  'title' => 'Foo Bar',
  'tabledata' => $addresses,
  'cloudmade_api_key' => $cloudmade_api_key,
  'markerdata' => $display_markers,
  'marker_ids' => implode(', ', $marker_ids),
  'mapview' => true,
);

// Mustache template loading.
$m = new JotMapMustache;
$page = $m->loadTemplate('page');
echo $page->render($hash);
