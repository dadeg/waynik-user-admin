<?php

namespace Waynik\Controllers;

use Silex\Application;
use SimpleUser\UserController;
use SimpleUser\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Waynik\Models\CheckinManager;
use Aws\S3\S3Client;
use Waynik\Models\User as UserObject;

class User extends UserController
{
    /** @var CheckinManager */
    protected $checkinManager;
    
    public function __construct(UserManager $userManager, $deprecated = null, CheckinManager $checkinManager)
    {
        parent::__construct($userManager, $deprecated);
        $this->checkinManager = $checkinManager;
    }
    
    public function editAction(Application $app, Request $request, $id)
    {
    	$errors = array();
    
    	$user = $this->userManager->getUser($id);
    	if (!$user) {
    		throw new NotFoundHttpException('No user was found with that ID.');
    	}
    
    	$customFields = $this->editCustomFields ?: array();
    
    	if ($request->isMethod('POST')) {
    		$user->setName($request->request->get('name'));
    		$user->setEmail($request->request->get('email'));
    		if ($request->request->has('username')) {
    			$user->setUsername($request->request->get('username'));
    		}
    		if ($request->request->get('password')) {
    			if ($request->request->get('password') != $request->request->get('confirm_password')) {
    				$errors['password'] = 'Passwords don\'t match.';
    			} else if ($error = $this->userManager->validatePasswordStrength($user, $request->request->get('password'))) {
    				$errors['password'] = $error;
    			} else {
    				$this->userManager->setUserPassword($user, $request->request->get('password'));
    			}
    		}
    		if ($app['security']->isGranted('ROLE_ADMIN') && $request->request->has('roles')) {
    			$user->setRoles($request->request->get('roles'));
    		}
    
    		foreach (array_keys($customFields) as $customField) {
    			if ($request->request->has($customField)) {
    				$user->setCustomField($customField, $request->request->get($customField));
    			}
    		}
    
    		$errors += $this->userManager->validate($user);
    
    		if (empty($errors)) {
    			$this->userManager->update($user);
    			$this->handleImageUpload($request, $user);
    			$msg = 'Saved account information.' . ($request->request->get('password') ? ' Changed password.' : '');
    			$app['session']->getFlashBag()->set('alert', $msg);
    		}
    	}
    
    	return $app['twig']->render($this->getTemplate('edit'), array(
    			'layout_template' => $this->getTemplate('layout'),
    			'error' => implode("\n", $errors),
    			'user' => $user,
    			'available_roles' => array('ROLE_USER', 'ROLE_ADMIN'),
    			'image_url' => $this->getProfileImageUrl($user->getId()),
    			'customFields' => $customFields,
    			'isUsernameRequired' => $this->isUsernameRequired,
    	));
    }

    private function handleImageUpload(Request $request, UserObject $user)
    {
    	$image = $request->files->get('image');
    	if (!$image) {
    		return;
    	}
    	
    	$client = $this->getS3Client();
    	
    	$result = $client->putObject(array(
    			'Bucket'     => 'waynik-user-profiles',
    			'Key'        => $user->getId(),
    			'SourceFile' => $image->getRealPath()
    	));
    }
    
    public function viewAction(Application $app, Request $request, $id)
    {
        $loggedInUser = $app['security.token_storage']->getToken()->getUser();
        $loggedInUserIsAdmin = $app['security']->isGranted('ROLE_ADMIN');
        if ($loggedInUser->getId() !== $id && !$loggedInUserIsAdmin) {
        	throw new NotFoundHttpException('access denied.');
        }
    	$user = $this->userManager->getUser($id);
        $checkin = $this->checkinManager->getMostRecentCheckin($user);
        $someRecentCheckins = $this->checkinManager->getRecentCheckins($user);

        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        if (!$user->isEnabled() && !$app['security']->isGranted('ROLE_ADMIN')) {
            throw new NotFoundHttpException('That user is disabled (pending email confirmation).');
        }

        return $app['twig']->render($this->getTemplate('view'), array(
            'layout_template' => $this->getTemplate('layout'),
            'user' => $user,
            'checkin' => $checkin,
        	'someRecentCheckins' => $someRecentCheckins,
            'imageUrl' => $this->getProfileImageUrl($user->getId())
        ));

    }
    

    private function getProfileImageUrl(string $userId)
    {
    	$client = $this->getS3Client();
    
    	$cmd = $client->getCommand('GetObject', [
    			'Bucket' => 'waynik-user-profiles',
    			'Key'    => $userId
    	]);
    
    	$request = $client->createPresignedRequest($cmd, '+2 minutes');
    	$presignedUrl = (string) $request->getUri();
    	// Get the actual presigned-url
    	return $presignedUrl;
    
    }
    
    private function getS3Client() 
    {
    	 return S3Client::factory(array(
    			'credentials' => [
    					'key' => 'AKIAIGYPNQHUPJXDGM7Q',
    					'secret' => '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'
    			],
    			'region' => 'us-west-2',
    			'version' => '2006-03-01'
    	));
    }
    
    protected function createUserFromRequest(Request $request)
    {
    	$user = parent::createUserFromRequest($request);
    	foreach($request->request->getIterator() as $field => $value) {
    		if (array_key_exists($field, $this->editCustomFields)) {
    			$user->setCustomField($field, $value);
    		}
    	}
    	
    	return $user;
    }
    
    /**
     * Action to handle email confirmation links.
     *
     * @param Application $app
     * @param Request $request
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function confirmEmailAction(Application $app, Request $request, $token)
    {
    	$user = $this->userManager->findOneBy(array('confirmationToken' => $token));
    	if (!$user) {
    		$app['session']->getFlashBag()->set('alert', 'Sorry, your email confirmation link has expired.');
    
    		return $app->redirect($app['url_generator']->generate('user.login'));
    	}
    
    	$user->setConfirmationToken(null);
    	$user->setEnabled(true);
    	$this->userManager->update($user);
    
    	$this->userManager->loginAsUser($user);
    
    	$app['session']->getFlashBag()->set('alert', 'Thank you! Your account has been activated.');
    
    	// This is the part that is different from the parent.
    	// Send welcome email.
    	$app['user.mailer']->sendWelcomeMessage($user);
    	// end customized part.
    	
    	return $app->redirect($app['url_generator']->generate('user.view', array('id' => $user->getId())));
    }
}