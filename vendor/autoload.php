<?php

/**
 * This file is used in loading the class loader
 *
 * @param $class name of the class to be loaded
 * @see http://php.net/manual/en/function.spl-autoload-register.php
 */

require 'Psr/Autoloader.php'; 

// instantiate the loader
$loader = new \Psr\Autoloader;

// register the autoloader
$loader->register();

//add the Psr namespace
$loader->addNamespace('Psr', realpath(dirname(__FILE__)) . '/Psr');
$loader->addNamespace('Ssg', realpath(dirname(__FILE__).'/../') . '/application');
$loader->addNamespace('Fpdf', realpath(dirname(__FILE__)). '/Fpdf');





