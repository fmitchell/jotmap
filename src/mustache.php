<?php

/**
 * JotMap
 *
 * @author Fredric Mitchell
 * @link http://jotmap.jotform.io
 * @copyright 2013 Fredric Mitchell
 */

/**
 * Class JotMapMustache
 *
 * A simple extension of Mustache so it can be auto-loaded with composer
 * and used everywhere.
 *
 */
class JotMapMustache extends Mustache_Engine {

  public function __construct() {
    // Mustache setup.
    $mustache_options = array(
      'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views'),
      'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views/partials'),
    );

    parent::__construct($mustache_options);
  }
}