<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

//define( 'INCLUSION_PERMITTED', true );
//require_once( 'config.php' );

if ( !defined('INCLUSION_PERMITTED') || 
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) { 
	die( 'This file must not be invoked directly.' ); 
}

include('third-party/apache-log4php/Logger.php');

// Tell log4php to use our configuration file.
Logger::configure('config-logging.xml');
// Fetch a logger, it will inherit settings from the root logger
$log = Logger::getLogger('myLogger');