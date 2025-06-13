<?php

/**
 * A page for displaying a message if the given page is not visible in the
 * current region.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class StoreNotVisiblePage extends StorePage
{
    // {{{ protected properties

    /**
     * @var SwatUI
     */
    protected $ui;

    // }}}
    // {{{ public function init()

    public function init()
    {
        parent::init();

        $this->ui = new SwatUI();
        $this->ui->loadFromXML(__DIR__ . '/not-visible-page.xml');
    }

    // }}}

    // build phase
    // {{{ public function build()

    public function build()
    {
        parent::build();

        $this->buildInternal();
        $this->buildAvailableRegions();

        $this->layout->startCapture('content');
        $this->ui->display();
        $this->layout->endCapture();
    }

    // }}}
    // {{{ protected abstract function buildInternal()

    abstract protected function buildInternal();

    // }}}
    // {{{ protected abstract function getAvailableRegions()

    abstract protected function getAvailableRegions();

    // }}}
    // {{{ protected function buildNavBar()

    protected function buildNavBar($link_prefix = '')
    {
        if (!isset($this->layout->navbar)) {
            return;
        }

        if ($link_prefix !== '') {
            $link_prefix = $link_prefix . '/';
        }

        if ($this->path !== null) {
            foreach ($this->path as $path_entry) {
                $link = $link_prefix . $path_entry->shortname;
                $this->layout->navbar->createEntry($path_entry->title, $link);
            }
        }
    }

    // }}}
    // {{{ private function buildAvailableRegions()

    private function buildAvailableRegions()
    {
        $regions = $this->getAvailableRegions();

        $locales = [];

        foreach ($regions as $region) {
            foreach ($region->locales as $locale) {
                $locales[$locale->id] = $locale;
            }
        }

        if (count($locales) == 0) {
            throw new SiteNotFoundException();
        }
        if (count($locales) == 1) {
            $primary_content = sprintf(
                Store::_('It is
				available on our %s store.'),
                $this->getLocaleLink(current($locales))
            );
        } else {
            $primary_content = Store::_('It is available
				in the following regions:');

            ob_start();

            $ul_tag = new SwatHtmlTag('ul');
            $li_tag = new SwatHtmlTag('li');

            $ul_tag->open();
            foreach ($locales as $locale) {
                $li_tag->open();
                echo $this->getLocaleLink($locale);
                $li_tag->close();
            }

            $ul_tag->close();

            $secondary_content = ob_get_clean();
        }

        $message = new SwatMessage($primary_content);
        $message->type = SwatMessage::NOTIFICATION;
        $message->content_type = 'text/xml';

        if (isset($secondary_content)) {
            $message->secondary_content = $secondary_content;
        }

        $this->ui->getWidget('available_regions')->add($message);
    }

    // }}}
    // {{{ private function getLocaleLink()

    private function getLocaleLink($locale)
    {
        $a_tag = new SwatHtmlTag('a');

        ob_start();

        $a_tag->href = $this->app->getBaseHref() . $this->source;
        $a_tag->setContent($locale->getTitle());
        $a_tag->display();

        return ob_get_clean();
    }

    // }}}

    // finalize phase
    // {{{ public function finalize()

    public function finalize()
    {
        parent::finalize();
        $this->layout->addHtmlHeadEntrySet(
            $this->ui->getRoot()->getHtmlHeadEntrySet()
        );
    }

    // }}}
}
