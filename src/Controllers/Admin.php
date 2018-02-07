<?php

namespace Waynik\Controllers;

use Silex\Application;

class Admin
{
    public function get(Application $app)
    {
        return $app['twig']->render('admin/index.twig');
    }
}