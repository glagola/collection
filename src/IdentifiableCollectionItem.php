<?php


namespace glagola\collections;

/**
 * Interface IdentifiableCollectionItem
 * Used by ImmutableCollection for comparison of collection's items
 */
interface IdentifiableCollectionItem
{
    /**
     * Returns identity, which uniquely identifies object in collection
     *
     * @return string
     */
    public function identity(): string;
}