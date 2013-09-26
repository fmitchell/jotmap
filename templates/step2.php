<?php

/**
 * JotMap
 *
 * @author Fredric Mitchell
 * @link http://jotmap.jotform.io
 * @copyright 2013 Fredric Mitchell
 */

// Clean the API key before printing.
$key = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotformapi']);

// Test the key.
try {
    $jotformAPI = new JotForm($key);
} catch (Exception $e) {
}

// Check if any forms return.
if ($forms = $jotformAPI->getForms()) {
    // Get user data.
    $single = reset($forms);
    $user = $single['username'];

    // Grab the list of forms.
    $list = array();
    foreach ($forms as $form) {
        if ($form['status'] == 'ENABLED') {
            $list[] = array(
                'id' => $form['id'],
                'title' => $form['title']
            );
        }
    }

    // Hash
    $hash = array(
        'key' => $key,
        'user' => $user,
        'list' => $list,
        'pagetitle' => 'Pick the Form',
        'step' => 2,
        'button' => 'Next',
    );
} else {
    $hash = array(
        'error' => TRUE,
        'errormessage' => 'No forms could be found.',
    );
}

// Mustache template loading.
$m = new JotMapMustache;
$page = $m->loadTemplate('page');
echo $page->render($hash);