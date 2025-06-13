<?php

/**
 * Application to upload Catalog Data files to Bing Shopping.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreBingShoppingUploader extends StoreProductFileFtpUploader
{
    // {{{ protected function getGenerator()

    protected function getGenerator()
    {
        return new StoreBingShoppingGenerator($this->db, $this->config);
    }

    // }}}
    // {{{ protected function getFilename()

    protected function getFilename()
    {
        return $this->config->bing->filename;
    }

    // }}}
    // {{{ protected function getFtpServer()

    protected function getFtpServer()
    {
        return $this->config->bing->server;
    }

    // }}}
    // {{{ protected function getFtpUsername()

    protected function getFtpUsername()
    {
        return $this->config->bing->username;
    }

    // }}}
    // {{{ protected function getFtpPassword()

    protected function getFtpPassword()
    {
        return $this->config->bing->password;
    }

    // }}}
}
