<?php

/**
 * @copyright 2007-2016 silverorange
 */
class StoreArticleWrapper extends SiteArticleWrapper
{
    // {{{ protected properties

    /**
     * The region to use when loading region-specific sub-articles.
     *
     * @var StoreRegion
     *
     * @see StoreProduct::setRegion()
     */
    protected $region;

    // }}}
    // {{{ public function setRegion()

    /**
     * Sets the region to use when loading region-specific sub-articles.
     *
     * @param StoreRegion $region the region to use
     */
    public function setRegion(StoreRegion $region)
    {
        $this->region = $region;

        foreach ($this->getArray() as $article) {
            $article->setRegion($region);
        }
    }

    // }}}
}
