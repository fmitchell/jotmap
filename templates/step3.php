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
$jotformAPI = new JotForm($key);
$questions = $jotformAPI->getFormQuestions($choice);

// Grab the list of questions.
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

    // Guess address field.
    if ($question['type'] == 'control_address') {
      $data['checked'] = TRUE;
    }
    $list[] = $data;
  }
}

// Mustache template loading.
$m = new JotMapMustache;
$page = $m->loadTemplate('page');

// Hash
$hash = array(
  'key' => $key,
  'user' => $user,
  'choice' => $choice,
  'list' => $list,
);
echo $page->render($hash);