<?php

namespace Waynik\Models;

use SimpleUser\User as BaseUser;

class CheckinManager extends AbstractManager
{
    protected $tableName = "checkins";

    public function getMostRecentCheckin(BaseUser $user)
    {
        $sql = "SELECT * FROM " . $this->tableName . " WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
        $params = [$user->getId()];
        $data = $this->conn->fetchAll($sql, $params);
        
        foreach ($data as $checkinData) {
            return $this->hydrateCheckin($checkinData);
        }

        return null;
    }
    
    public function getRecentCheckins(BaseUser $user) 
    {
    	$sql = "SELECT * FROM " . $this->tableName . " WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    	$params = [$user->getId()];
    	$data = $this->conn->fetchAll($sql, $params);
    	$results =[];
    	
    	foreach ($data as $checkinData) {
    		$results[] = $this->hydrateCheckin($checkinData);
    	}
    	
    	return $results;
    }
    
    private function hydrateCheckin(array $data)
    {
         return new Checkin($data['latitude'], $data['longitude'], $data['created_at']);

    }
}