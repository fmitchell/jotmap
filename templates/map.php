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

// Attempt to connect to mongo database.
try {
  $client = new MongoClient($mongo_uri);
  $db = $client->$dbName;
  $mongo_submissions = $db->submissions;
} catch (Exception $e) {
}

// Jotform setup.
try {
    $jotformAPI = new JotForm($key);
} catch (Exception $e) {
}

// Get latest form submissions.
// Limit to 20.
if ($submissions = $jotformAPI->getFormSubmissions($choice, 0, 20)) {
    $submission_geocodes = $display_markers = $marker_ids = $addresses = array();

// Go through each submission to geocode.
    foreach ($submissions as $submission) {

        // Setup variables.
        $id = $submission['id'];
        $form_id = $submission['form_id'];

        $address_answer = $submission['answers'][$jotaddress]['answer'];
        $label_answer = $submission['answers'][$jotlabel]['answer'];

        $address = (is_array($address_answer))
          ? implode(', ', $submission['answers'][$jotaddress]['answer'])
          : $address_answer;
        $name = (is_array($label_answer))
          ? implode(' ', $submission['answers'][$jotlabel]['answer'])
          : $label_answer;

        // Build Mongo query parameters.
        $query = array(
            'id' => $submission['id'],
            'form_id' => $submission['form_id']
        );

        // See if record is already in db.
        $existing = (isset($mongo_submissions) && is_object($mongo_submissions))
          ? $mongo_submissions->findOne($query)
          : null;

        // If already in Mongo, no need to geocode
        if (!isset($existing)) {
            // Setup geocoder.
            $adapter = new \Geocoder\HttpAdapter\CurlHttpAdapter();
            $geocoder = new \Geocoder\Geocoder();
            $chain = new \Geocoder\Provider\ChainProvider(
                array(
                    new \Geocoder\Provider\GoogleMapsProvider($adapter),
                )
            );
            $geocoder->registerProvider($chain);

            // Try to geocode.
            try {
                $geocode = $geocoder->geocode($address);
                $longitude = $geocode->getLongitude();
                $latitude = $geocode->getLatitude();

                // Save longitude, latitude, submission id, and form id.
                date_default_timezone_set('US/Central');
                $submission_geocodes[] = array(
                    'long' => $longitude,
                    'lat' => $latitude,
                    'id' => $id,
                    'form_id' => $form_id,
                    'date' => date('m-d-y H:m:s'),
                );
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }

        } else {
            $latitude = $existing['lat'];
            $longitude = $existing['long'];
        }

        // Check to see if geocode is 'center of world', i.e. could not find.
        if (isset($latitude) && ($latitude != '-3.37232') && isset($longitude) && ($longitude != '36.85787')) {
            $display_markers[] = array(
                'marker_id' => 'marker' . $id,
                'lat' => $latitude,
                'long' => $longitude,
                'label' => $name,
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
    if (!empty($submission_geocodes) && isset($mongo_submissions)) {
        $mongo_submissions->batchInsert($submission_geocodes);
    }

// Mustache hashes.
    $hash = array(
      'tabledata' => $addresses,
      'cloudmade_api_key' => $cloudmade_api_key,
      'markerdata' => $display_markers,
      'marker_ids' => implode(', ', $marker_ids),
      'mapview' => TRUE,
      'key' => $key,
      'user' => $user,
      'choice' => $choice,
      'embed' => TRUE,
      'leaflet' => TRUE,
      'errormessage' => isset($error_message) ? $error_message : NULL,
    );
} else {
    $hash = array(
        'error' => TRUE,
        'errormessage' => isset($error_message) ? $error_message : 'No addresses could be geo-coded.',
    );
}

// Mustache template loading.
$m = new JotMapMustache;

// If embedding the map, use the embed template.
// Otherwise use regular page template.
if (isset($_GET['embed']) && $_GET['embed'] == 'iframe') {
  $page = $m->loadTemplate('iframe');
} else {
  $page = $m->loadTemplate('page');
}

echo $page->render($hash);
