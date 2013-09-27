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
$choice = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['jotformchoice']);
$user = preg_replace('/[^-a-zA-Z0-9_]/', '', $_GET['user']);

// Grab the form questions.
try {
    $jotformAPI = new JotForm($key);
} catch (Exception $e) {
}

// Grab the list of questions.
if ($questions = $jotformAPI->getFormQuestions($choice)) {
    $list = array();
    foreach ($questions as $question) {
        // Exclude fields we don't need.
        $exclude = array(
            'control_head',
            'control_button',
        );
        if (!in_array($question['type'], $exclude)) {
            $data = array(
                'qid' => $question['qid'],
                'title' => $question['text']
            );
            $list[] = $data;
        }
    }

    // Hash
    $hash = array(
        'key' => $key,
        'user' => $user,
        'choice' => $choice,
        'list' => $list,
        'pagetitle' => 'Pick the Fields',
        'step' => 3,
        'button' => 'Build Map',
        'buttonjs' => TRUE,
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