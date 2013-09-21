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
echo $page->render();