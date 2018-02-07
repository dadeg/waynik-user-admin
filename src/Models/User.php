<?php

namespace Waynik\Models;

class User extends \SimpleUser\User
{
    public function getApiToken()
    {
        return $this->getCustomField('apiToken');
    }

    public function setApiToken($apiToken)
    {
        $this->setCustomField('apiToken', $apiToken);
    }
    
    public function validate()
    {
        $errors = parent::validate();

        if ($this->getApiToken()) {
            //$errors['apiToken'] = 'Twitter username must begin with @.';
        }

        return $errors;
    }
	
    /**
     * Override to return 1 or 0 for insert into users table.
     * {@inheritDoc}
     * @see \SimpleUser\User::isEnabled()
     */
    public function isEnabled()
    {
        return parent::isEnabled() ? 1 : 0;
    }
    
    public function setCustomField($customField, $value)
    {
    	$this->customFields[$customField] = $value;
    }
}