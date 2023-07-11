<?php

namespace App\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AreabrickInterface;

class ProductPrice extends AbstractAreabrick
{
    // implementing class methods
    public function getName(){
        return 'Product Price';
    }
}