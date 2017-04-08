<?php


namespace glagola\collections;


use Traversable;

/**
 * Immutable traversable collection, which allows to get added or removed items
 */
abstract class ImmutableCollection implements \IteratorAggregate
{
    /** @var IdentifiableCollectionItem[] */
    protected $added = [];
    
    /** @var IdentifiableCollectionItem[] */
    protected $removed = [];
    
    /** @var callable */
    protected $itemsProxy;
    
    /** @var IdentifiableCollectionItem[]|bool */
    protected $items = false;
    
    protected function __construct(callable $itemsProxy,
                                 $items = false,
                                 array $added = [],
                                 array $removed = [])
    {
        $this->added = $added;
        $this->removed = $removed;
        $this->items = $items;
        
        $this->itemsProxy = $itemsProxy;
    }
    
    /**
     * Instantiate collection
     *
     * @param callable $itemsProxy callable proxy for lazy loading,
     *                             must have following signature function (): iterable { ... }
     *
     * @return ImmutableCollection
     */
    public static function instance(callable $itemsProxy): self
    {
        return new static($itemsProxy);
    }
    
    /**
     * Retrieve an external iterator
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->getItemsWithChanges();
    }
    
    /**
     * Adds items to collection
     *
     * @param IdentifiableCollectionItem[] ...$items
     *
     * @return ImmutableCollection
     */
    public function add(IdentifiableCollectionItem ...$items): self
    {
        $added = $this->added;
        $removed = $this->removed;
        
        foreach ($items as $item) {
            $id = $item->identity();
            
            if (isset($removed[$id])) {
                unset($removed[$id]);
            } else {
                $added[$id] = $item;
            }
        }
        
        return new static($this->itemsProxy, $this->items, $added, $removed);
    }
    
    /**
     * Removes items from collection
     *
     * @param IdentifiableCollectionItem[] ...$items
     *
     * @return ImmutableCollection
     */
    public function remove(IdentifiableCollectionItem ...$items): self
    {
        $added = $this->added;
        $removed = $this->removed;
    
        foreach ($items as $item) {
            $id = $item->identity();
            
            if (isset($added[$id])) {
                unset($added[$id]);
            } else {
                $removed[$id] = $item;
            }
        }
    
        return new static($this->itemsProxy, $this->items, $added, $removed);
    }
    
    /**
     * Returns items from memory or uses $this->itemsProxy to load them into memory and return them
     *
     * @return iterable
     */
    protected function getItems(): iterable
    {
        if (false === $this->items) {
            $this->items = [];
            
            /** @var IdentifiableCollectionItem $item */
            foreach (($this->itemsProxy)() as $item) {
                yield $this->items[$item->identity()] = $item;
            }
            
            return;
        }
    
        foreach ($this->items as $item) {
            yield $item;
        }
    }
    
    /**
     * Allows to traverse collection including added and excluding removed items
     *
     * @return iterable
     */
    protected function getItemsWithChanges(): iterable
    {
        /** @var IdentifiableCollectionItem $item */
        foreach ($this->getItems() as $item) {
            if (isset($this->removed[$item->identity()])) {
                continue;
            }
            
            yield $item;
        }
        
        foreach ($this->added as $item) {
            $identity = $item->identity();
            if (isset($this->removed[$identity]) || isset($this->items[$identity])) {
                continue;
            }
    
            yield $item;
        }
    }
    
    /**
     * Returns added items
     *
     * @return iterable
     */
    public function added(): iterable
    {
        foreach ($this->added as $item) {
            yield $item;
        }
    }
    
    /**
     * Returns removed items
     *
     * @return iterable
     */
    public function removed(): iterable
    {
        foreach ($this->removed as $item) {
            yield $item;
        }
    }
    
    /**
     * Number of objects in collection
     *
     * @return int
     */
    public function count(): int
    {
        return iterator_count($this->getItemsWithChanges());
    }
    
    /**
     * Checks if the $item already in collection
     *
     * @param IdentifiableCollectionItem $item
     *
     * @return bool
     */
    public function has(IdentifiableCollectionItem $item): bool
    {
        $id = $item->identity();
        if (isset($this->added[$id])) {
            return true;
        }
        
        if (isset($this->removed[$id])) {
            return false;
        }
        
        /** @var IdentifiableCollectionItem $item */
        foreach ($this->getItemsWithChanges() as $item) {
            if ($id === $item->identity()) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Checks if collection is empty
     *
     * @return bool
     */
    public function empty(): bool
    {
        if ([] !== $this->added) {
            return false;
        }
        
        return 0 === $this->count();
    }
    
    /**
     * Converts collection to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getItemsWithChanges());
    }
}