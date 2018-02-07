<?php

use Silex\Provider;
use Waynik\Models\UserManager;
use Waynik\Models\CheckinManager;
use Waynik\Providers as WaynikProviders;
use Waynik\Repository\Mailer as WaynikMailer;
use Waynik\Controllers\User as UserController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Application();

if(getenv('APP_ENV') === "development") {
    $app['debug'] = true;
}

$app['upload_folder'] = __DIR__ . '/images';

$app->register(new Provider\SecurityServiceProvider());
$app->register(new Provider\DoctrineServiceProvider());
$app->register(new Provider\RememberMeServiceProvider());
$app->register(new Provider\SessionServiceProvider());
$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Provider\UrlGeneratorServiceProvider());
$app->register(new WaynikProviders\SwiftmailerServiceProvider());


$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => getenv('DB_HOST'),
    'dbname'   => getenv('DB_NAME'),
    'user'     => getenv('DB_USER'),
    'password' => getenv('DB_PASS')
);

// needs less secure apps enabled on gmail in order to work properly?
// TODO: switch to Amazon SES, simple email service. Credentials:
// email-smtp.us-west-2.amazonaws.com 
// SMTP Username:
// AKIAJ7S5OUQ2DWLCZIXA
// SMTP Password:
// AiZYyJzOt6tfw9sqNUu9rFSdYt4OU40wMyZKDFz7YyRl

$app['swiftmailer.options'] = array(
    'host' => 'smtp.gmail.com',
    'port' => '465',
    'username' => 'development@waynik.com',
    'password' => 'gnzuywwemrqwtfdt',
    'encryption' => 'ssl',
    'auth_mode' => 'login'
);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => '../src/Views',
));

$userServiceProvider = new SimpleUser\UserServiceProvider();

$app->register($userServiceProvider);

$app['user.options'] = array(

    'templates' => array(
        'layout' => 'admin/layout.twig',
        'view' => 'admin/user/view.twig',
        'edit' => 'admin/user/edit.twig',
		'register' => 'admin/user/register.twig',
        
    ),
    'emailConfirmation' => array(
        'required' => true, // Whether to require email confirmation before enabling new accounts.
        'template' => 'email/confirm-email.twig',
    ),
    'emailWelcome' => array(
        'template' => 'email/welcome-email.twig',
    ),

    // Configure the user mailer for sending password reset and email confirmation messages.
    'mailer' => array(
        'enabled' => true, // When false, email notifications are not sent (they're silently discarded).
        'fromEmail' => array(
            'address' => 'do-not-reply@waynik.com',
            'name' => 'Waynik',
        ),
    ),

    'editCustomFields' => array(
        'apiToken' => 'Token',
    	'primaryPhone' => 'Primary Phone Number',
    	'secondaryPhone' => 'Secondary Phone Number',
    	'tertiaryPhone' => 'Tertiary Phone Number',
    	'cellularConnectivity' => 'Does your device have cell network access?',
    	'permanentAddressStreet' => 'Permanent Address Street',
    	'permanentAddressStreet2' => 'Permanent Address Street 2',
    	'permanentAddressCity' => 'Permanent Address City',
    	'permanentAddressState' => 'Permanent Address State/Region',
    	'permanentAddressCountry' => 'Permanent Address Country',	
    	'currentAddressStreet' => 'Current Address Street',
    	'currentAddressStreet2' => 'Current Address Street 2',
    	'currentAddressCity' => 'Current Address City',
    	'currentAddressState' => 'Current Address State/Region',
    	'currentAddressCountry' => 'Current Address Country',
    	'currentAddressNext12Months' => 'Will you be living at your current address for the next 6-12 months?',
    	'primaryEmergencyContactName' => 'Primary Emergency Contact Name/Organization',
    	'primaryEmergencyContactPhone' => 'Primary Emergency Contact Phone Number',
    	'primaryEmergencyContactEmail' => 'Primary Emergency Contact Email',
    	'primaryEmergencyContactRelation' => 'Primary Contact Relation to You',
    	'secondaryEmergencyContactName' => 'Secondary Emergency Contact Name/Organization',
    	'secondaryEmergencyContactPhone' => 'Secondary Emergency Contact Phone Number',
    	'secondaryEmergencyContactEmail' => 'Secondary Emergency Contact Email',
    	'secondaryEmergencyContactRelation' => 'Secondary Contact Relation to You',
    	'travelPlans' => 'Locations you will travel to in the next 6-12 months',
    	'languagesSpoken' => 'Languages Spoken',
    	'height' => 'Height',
    	'eyeColor' => 'Eye Color',
    	'hairColor' => 'Hair Color',
    	'nationality' => 'Nationality',
    	'gender' => 'Gender',
    	'generalInfo' => "Tell us a little more about your organization, nature of work abroad and anything else that may impact your security situation when you’re abroad."
    ),

    'userClass' => '\Waynik\Models\User',
);

// Override default user.manager.
$app['user.manager'] = $app->share(function($app) {
    $app['user.options.init']();

    $userManager = new UserManager($app['db'], $app);
    $userManager->setUserClass($app['user.options']['userClass']);
    $userManager->setUsernameRequired($app['user.options']['isUsernameRequired']);
    $userManager->setUserTableName($app['user.options']['userTableName']);
    $userManager->setUserCustomFieldsTableName($app['user.options']['userCustomFieldsTableName']);

    return $userManager;
});

// Override User mailer.
$app['user.mailer'] = $app->share(function($app) {
	$app['user.options.init']();

	$missingDeps = array();
	if (!isset($app['mailer'])) $missingDeps[] = 'SwiftMailerServiceProvider';
	if (!isset($app['url_generator'])) $missingDeps[] = 'UrlGeneratorServiceProvider';
	if (!isset($app['twig'])) $missingDeps[] = 'TwigServiceProvider';
	if (!empty($missingDeps)) {
		throw new \RuntimeException('To access the SimpleUser mailer you must enable the following missing dependencies: ' . implode(', ', $missingDeps));
	}

	$mailer = new WaynikMailer($app['mailer'], $app['url_generator'], $app['twig']);
	$mailer->setFromAddress($app['user.options']['mailer']['fromEmail']['address'] ?: null);
	$mailer->setFromName($app['user.options']['mailer']['fromEmail']['name'] ?: null);
	$mailer->setConfirmationTemplate($app['user.options']['emailConfirmation']['template']);
	$mailer->setWelcomeTemplate($app['user.options']['emailWelcome']['template']);
	$mailer->setResetTemplate($app['user.options']['passwordReset']['template']);
	$mailer->setResetTokenTtl($app['user.options']['passwordReset']['tokenTTL']);
	if (!$app['user.options']['mailer']['enabled']) {
		$mailer->setNoSend(true);
	}

	return $mailer;
});

$app['checkin.manager'] = $app->share(function($app) {
    $checkinManager = new CheckinManager($app['db'], $app);
    return $checkinManager;
});

// Override controllers by defining the same route
// *before* mounting the controller provider.
$app->get('/admin/user/list', function(Application $app) {
    return new Response('Not available.');
});
//
//// Override controllers by defining the same route
//// *before* mounting the controller provider.
//$app->get('/user/{id}', 'Waynik\\Controllers\\User::view')
//    //->bind('user.view')
//    ->assert('id', '\d+');

$app['user.controller'] = $app->share(function ($app) {
    $app['user.options.init']();

    $controller = new UserController($app['user.manager'], null, $app['checkin.manager']);
    $controller->setUsernameRequired($app['user.options']['isUsernameRequired']);
    $controller->setEmailConfirmationRequired($app['user.options']['emailConfirmation']['required']);
    $controller->setTemplates($app['user.options']['templates']);
    $controller->setEditCustomFields($app['user.options']['editCustomFields']);

    return $controller;
});

// Mount SimpleUser routes.
$app->mount('/admin/user', $userServiceProvider);

$app['security.firewalls'] = array(
    'login_register' => array (
        'pattern' => '^/admin/user/(login|register|confirm-email.*|resend-confirmation|forgot-password|reset-password.*)$'
    ),
    'secured_area' => array(
        'pattern' => '^.*$',
        'anonymous' => false,
        'remember_me' => array(),
        'form' => array(
            'login_path' => '/admin/user/login',
            'check_path' => '/admin/user/login_check',
	        'always_use_default_target_path' => true,
	        'default_target_path' => '/admin'
        ),
        'logout' => array(
            'logout_path' => '/admin/user/logout',
        	'target_url' => '/admin'
        ),
        'users' => $app->share(function($app) { return $app['user.manager']; }),
    ),
);



// Admin area routes
$app->get('/admin', 'Waynik\\Controllers\\Admin::get')->bind('admin');

$app->run();
