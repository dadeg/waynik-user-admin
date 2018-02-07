<?php

namespace Waynik\Models;

class Checkin
{
    private $latitude;
    private $longitude;
    private $createdAt;
    
    public function __construct(float $latitude, float $longitude, string $createdAt)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->createdAt = $createdAt;
    }
    
    public function getLatitude() 
    {
        return $this->latitude;
    }
    
    public function getLongitude()
    {
        return $this->longitude;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}