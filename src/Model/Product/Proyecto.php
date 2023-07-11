<?php
namespace App\Model\Product;

use Pimcore\Model\DataObject\AccessoryPart\Listing;
use Pimcore\Model\DataObject\Data\Hotspotimage;

class Proyecto extends \Pimcore\Model\DataObject\Proyecto
{
    public function getOSName(): ?string
    {
        return $this->getNombre();
    }
    
    public function getName(): ?string
    {
        return $this->getNombre();
    }
    
    public function getShortDescription(): ?string
    {
        return $this->getDescripcion();
    }
    
    /**
    * @return Category|null
    */
    public function getMainCategory(): ?Category
    {
    $categories = $this->getCategories();
    $category = reset($categories);
    
    return $category ?: null;
    }
    
    
    /**
    * @return string|null
    */
    public function getOSProductNumber(): ?string
    {
    return $this->getId();
    }
    
    
    /**
     * @return Hotspotimage|null
     */
    public function getMainImage(): ?Hotspotimage
    {
        $gallery = $this->getImagenes();
        if ($gallery) {
            $items = $gallery->getItems();
            if ($items) {
                return $items[0];
            }
        }
    
        return null;
    }
    
        /**
     * @return Hotspotimage[]
     */
    public function getAdditionalImages(): array
    {
        $gallery = $this->getImagenes();
        $items = $gallery->getItems();
    
        if ($items) {
            unset($items[0]);
        } else {
            $items = [];
        }
    
        $items = array_filter($items, function ($item) {
            return !empty($item) && !empty($item->getImage());
        });
    
        return $items;
    }
}


