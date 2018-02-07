<?php

namespace Waynik\Providers;

use Silex\Provider\SwiftmailerServiceProvider as SilexSwiftmailerServiceProvider;
use Silex\Application;
use Swift_Transport_Esmtp_AuthHandler;
use Swift_Transport_Esmtp_Auth_CramMd5Authenticator;
use Swift_Transport_Esmtp_Auth_LoginAuthenticator;
use Swift_Transport_Esmtp_Auth_PlainAuthenticator;
use Swift_Transport_Esmtp_Auth_XOAuth2Authenticator;

class SwiftmailerServiceProvider extends SilexSwiftmailerServiceProvider
{
    public function register(Application $app) {
        parent::register($app);

        // Extend swiftmailer to include oauth2 authentication.
        $app['swiftmailer.transport.authhandler'] = $app->share(function () {
            return new Swift_Transport_Esmtp_AuthHandler(array(
                new Swift_Transport_Esmtp_Auth_CramMd5Authenticator(),
                new Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
                new Swift_Transport_Esmtp_Auth_XOAuth2Authenticator(),
            ));
        });
    }
}