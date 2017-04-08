<?php


namespace tests;


use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private function newCollection(array $items): Collection
    {
        /** @var Collection $instance */
        $instance = Collection::instance(function () use ($items) {
            return $items;
        });
        
        return $instance;
    }
    
    public function testEmpty()
    {
        $collection = $this->newCollection([]);
        
        $this->assertEquals(0, $collection->count());
        $this->assertEquals([], iterator_to_array($collection));
    }
    
    public function testCollectionImmutability()
    {
        $item = new Item('10');
        
        $collection = $this->newCollection([$item]);
        
        $collection->add($item);
        $this->assertEquals(1, $collection->count());
        
        $collection->remove($item);
        $this->assertEquals(1, $collection->count());
    }
    
    public function testAddNewItem()
    {
        $item = new Item('10');
        
        $collection = $this->newCollection([]);
        
        $collection = $collection->add($item);
        
        $this->assertEquals(1, $collection->count());
        $this->assertEquals(1, iterator_count($collection->added()));
        
        $items = iterator_to_array($collection->added());
        $this->assertEquals($item->identity(), $items[0]->identity());
    }
    
    public function testRemoveItem()
    {
        $item = new Item('10');
        
        $collection = $this->newCollection([$item]);
        
        $collection = $collection->remove($item);
        
        $this->assertEquals(0, $collection->count());
        $this->assertEquals(1, iterator_count($collection->removed()));
        
        $items = iterator_to_array($collection->removed());
        $this->assertEquals($item->identity(), $items[0]->identity());
    }
    
    public function testItemsProxyCall()
    {
        $proxyCalled = false;
        $collection = Collection::instance(function () use (&$proxyCalled) {
            $proxyCalled = true;
            
            return [];
        });
        
        iterator_to_array($collection);
        $this->assertTrue($proxyCalled);
        
        $proxyCalled = false;
        iterator_to_array($collection);
        $this->assertFalse($proxyCalled, 'Items proxy must be called only once');
    }
    
    public function testCheckItemsUniquenessAfterAddingNewItem()
    {
        /** @var Collection $collection */
        $collection = $this->newCollection([
            new Item('10'),
        ]);
        
        $collection = $collection->add(new Item('20'), new Item('10'));
        
        $this->assertEquals(2, $collection->count());
        /** @var Item[] $items */
        $items = iterator_to_array($collection);
        $this->assertTrue(
            $items[0]->identity() === '10' && $items[1]->identity() === '20' ||
            $items[0]->identity() === '20' && $items[1]->identity() === '10'
        );
        
        $collection = $collection->remove(new Item('20'));
        $this->assertEquals(1, $collection->count());
        $items = iterator_to_array($collection);
        $this->assertTrue($items[0]->identity() === '10');
    }
    
    public function testRemoveAddedItem()
    {
        /** @var Collection $collection */
        $collection = $this->newCollection([]);
        $item = new Item('10');
        
        $collection = $collection->add($item);
        $collection = $collection->remove($item);
        
        $this->assertEquals(0, $collection->count());
    }
    
    public function testAddRemovedItem()
    {
        /** @var Collection $collection */
        $collection = $this->newCollection([]);
        $item = new Item('10');
    
        $collection = $collection->remove($item);
        $collection = $collection->add($item);
    
        $this->assertEquals(0, $collection->count());
    }
}