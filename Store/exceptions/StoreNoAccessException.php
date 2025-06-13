<?php

/**
 * Thrown when access to a page is not allowed.
 *
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreNoAccessException extends StoreException
{
    // {{{ public function __construct()

    /**
     * Creates a new no access exception.
     *
     * @param string $message the message of the exception
     * @param int    $code    the code of the exception
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
        $this->title = _('No Access');
        $this->http_status_code = 403;
    }

    // }}}
}
