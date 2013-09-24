<?php

/**
 * JotMap
 *
 * @author Fredric Mitchell
 * @link http://jotmap.jotform.io
 * @copyright 2013 Fredric Mitchell
 */

// Mustache template loading.
$m = new JotMapMustache;
$page = $m->loadTemplate('page');

// Hash
$hash = array(
  'pagetitle' => 'Enter JotForm API Key',
  'step' => 1,
  'jotformauth' => TRUE,
);
echo $page->render($hash);