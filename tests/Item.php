<?php


namespace tests;


use glagola\collections\IdentifiableCollectionItem;

class Item implements IdentifiableCollectionItem
{
    private $id;
    
    public function __construct(string $id)
    {
        $this->id = $id;
    }
    
    public function identity(): string
    {
        return $this->id;
    }
}