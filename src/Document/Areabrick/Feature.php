<?php

namespace App\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AreabrickInterface;

class Feature extends AbstractAreabrick
{
    // implementing class methods
    public function getName(){
        return 'Feature';
    }
}