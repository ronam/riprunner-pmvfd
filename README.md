riprunner
=========

A Firehall dispatching communication suite.

Description:

This application suite was designed by a volunteer fire fighter (whose full time job is software development) to enhance the experience of First Responders during an emergency 911 callout. The main goal of this application is to provide a completely free suite of applications which help fire fighters receive timely information and communicate activities with one another as incidents progress.

Key Features:
-------------
- Email polling to check for an emergency 911 callout (or page) received from your FOCC (Fire Operations Command Center)
- Pluggable support for SMS gateway providers to send SMS information to fire fighters. 
  Currently providers implemented include (all offer free acounts with limited SMS / month):
  - Twilio (twilio.com)
  - Sendhub (sendhub.com)
  - EzTexting (eztexting.com)
  - TextBelt (textbelt.com)
- Self Installation
- User Account management
- Callout history with responding members
- Google Maps showing Distance from Firehall to Incident
- Ability for members to respond to callout, thus letting other members know who is responding
- Experimental Native Android App which interfaces to the web application (does not require SMS Gateway)

System Requirements:
--------------------
- An email account that recieves Callout information during a 911 page
- A webserver that can run PHP 5.x
- A MySQL database to install the Rip Runner Schema
- A Registered Account on an SMS Gateway Provider (Twilio,Sendhub,EzTexting,TextBelt)
- A Google Maps API key
- Optional: If using the experimental Android app, you need a Google Apps Engine (GAE) Project # (see http://developer.android.com/google/gcm/gs.html)

Screenshots:
------------
Call out example (SMS sent to responders with a link to this page):
![Callout Example](/screenshots/riprunner-callou1.png?raw=true "Callout Example")

System administration:
![Login](/screenshots/riprunner-admin1.png?raw=true "Login")

![Main Menu](/screenshots/riprunner-admin2.png?raw=true "Main Menu")


Installation:
-------------
- Edit values in config-default.php to suit your environment. (see Configuration section below)
- Rename the file config-default.php to config.php
- Upload the files in the php folder to a location on your webserver.
- Open the url: http://www.yourwebserver.com/uploadlocation/install.php
- If everything was done correctly you should see an install page offering to install one the firehall's 
  you configured in config.php (we support more than 1 firehall if desired). Select the firehall and click install.
- If successful the installation will display the admin user's password. Click the link to login using it.
- Add firehall members to the user accounts page. Users given admin access can modify user accounts.
- You will need something that will trigger the email trigger checker. If your server offers a 'cron' or 
  scheduler process, configure it to visit http://www.yourwebserver.com/uploadlocation/email_trigger_check.php
  every minute. If your server does not have cron or limits the frequency, you can use Google App Engine's 
  cron service to call your email trigger every minute. (see files in php/googleae folder as a reference)
- Send a test email to the trigger email address in order to see if you get notified of a callout.

Configuration:
--------------
The most important information that you require to configure is located in config.php. 
You must create this file (or rename config-default.php to config.php) and supply configuration values.
The following explains the main sections in config.php. The structures used in coinfig.php are
defined in config_interfaces.php if you are interested to see their definitions.

 Config.php:
 -----------

	// ----------------------------------------------------------------------
	// Email Settings
	
	// Below is the email address that we expect to receive callouts from.
	// This can be a full email address as shown below  or just the domain
	// example: focc.mycity.ca (this would allow all email addresses from this domain)
  	define( 'DEFAULT_EMAIL_FROM_TRIGGER', 'donotreply@focc.mycity.ca');
	
	// Below we create an email account structure for our firehall.
	// See the class FireHallEmailAccount in config_interfaces.php
	// for details
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount(
	    true, 
	    DEFAULT_EMAIL_FROM_TRIGGER,
	    '{pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX',
	    'my-email-trigger@my-email-host.com',
	    'my-email-password',
	    true);
				
	// ----------------------------------------------------------------------
	// MySQL Database Settings
	
	// Below we create a MySQL structure for our firehall.
	// See the class FireHallMySQL in config_interfaces.php
	// for details
	$LOCAL_DEBUG_MYSQL = new FireHallMySQL(
	    'localhost',
	    'riprunner', 
	    'riprunner', 
	    'riprunner');

	// -----------------------------------------------------------------------
	// SMS Settings
	
	// Below is the URL if you are using SendHub to send SMS messages.
	// username=X - replace X with your sendhub Username
	// api_key=X  - replace X with your sendhub API Key
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	
	// Below is the URL if you are using TextBelt to send SMS messages.
	// Ensure that you use the correct url for your country
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	
	// Below is the URL if you are using EzTexting to send SMS messages.
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	// Below is the EzTexting account username
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	// Below is the EzTexting account password
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	
	// Below is the URL if you are using Twilio to send SMS messages.
	// https://api.twilio.com/2010-04-01/Accounts/X/Messages.xml - replace X with your Twilio account name
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/2010-04-01/Accounts/X/Messages.xml');
	// Below is the Twilio account authentication token
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'X:X');
	// Below is the Twilio account From mobile phone #
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+12505551212');

	// Below we create an SMS structure for our firehall.
	// See the class FireHallSMS in config_interfaces.php
	// for details
	$LOCAL_DEBUG_SMS = new FireHallSMS(
		true,
		//SMS_GATEWAY_TEXTBELT,
		//SMS_GATEWAY_EZTEXTING,
		SMS_GATEWAY_SENDHUB,
		//SMS_GATEWAY_TWILIO, 
		//'2505551212', false, true,		// TEXTBELT
		//'svvfd', true, false, 				// EZTEXTING
		'103740731333333333', true, false, 	// SENDHUB (The sendhub group id)
		//'2505551212', false, true, 		// TWILIO
		DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL, 
		DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME,
		DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD, 
		DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL,
		DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN,
		DEFAULT_SMS_PROVIDER_TWILIO_FROM);

	// ----------------------------------------------------------------------
	// Mobile App Settings
	
	// Below is the Google Cloud Messaging API Key
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	// Below is the Google Cloud Messaging Project Id (aka sender id)
	define( 'DEFAULT_GCM_PROJECTID','X');

	// Below we create a Mobile structure for our firehall.
	// See the class FireHallMobile in config_interfaces.php
	// for details
	$LOCAL_DEBUG_MOBILE = new FireHallMobile(
	    true, 
	    true,
	    DEFAULT_GCM_SEND_URL,
	    DEFAULT_GCM_API_KEY,
	    DEFAULT_GCM_PROJECTID);
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	
	// Below is the Google Maps API Key
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 						'X' );
	// A ; delimited list of original_city_name|new_city_name city names to swap for google maps 
	// This list changes city names fro mthe item on the left to that on the right and is only
	// used when drawing google maps. In the example below all callouts with the city name
	// SALMON VALLEY, will be changed into PRINCE GEORGE, when google maps are used
	define( 'DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION', 	'SALMON VALLEY,|PRINCE GEORGE,;' );

	// Below we create a Website structure for our firehall.
	// See the class FireHallWebsite in config_interfaces.php
	// for details
	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite(
	    'Local Test Fire Department',
	    '5155 Salmon Valley Road, Prince George, BC',
	    'http://yourwebsite.com/riprunner/',
	    DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY, 
	    DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION);
	
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	
	// Below we create a Firehall config structure for our firehall.
	// See the class FireHallConfig in config_interfaces.php
	// for details
	$LOCAL_DEBUG_FIREHALL = new FireHallConfig(	
	        true, 
		0,
		$LOCAL_DEBUG_MYSQL,
		$LOCAL_DEBUG_EMAIL,
		$LOCAL_DEBUG_SMS,
		$LOCAL_DEBUG_WEBSITE,
		$LOCAL_DEBUG_MOBILE);
	
	// Add as many firehalls to the array as you desire to support
	// This array is used through Rip Runner and lookups are done using the firehall id
	// to find the firehall configuration to use for a given request
	$FIREHALLS = array($LOCAL_DEBUG_FIREHALL);
