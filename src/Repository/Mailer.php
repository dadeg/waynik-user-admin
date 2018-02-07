<?php


namespace Waynik\Repository;

use SimpleUser\Mailer as SimpleUserMailer;
use SimpleUser\User as SimpleUser;

class Mailer extends SimpleUserMailer
{
    protected $welcomeTemplate;

    /**
     * @param string $welcomeTemplate
     */
    public function setWelcomeTemplate($welcomeTemplate)
    {
        $this->welcomeTemplate = $welcomeTemplate;
    }

    /**
     * @return string
     */
    public function getWelcomeTemplate()
    {
        return $this->welcomeTemplate;
    }

    public function sendWelcomeMessage(SimpleUser $user)
    {
        $context = array();

        $this->sendMessage($this->welcomeTemplate, $context, $this->getFromEmail(), $user->getEmail());
    }
}