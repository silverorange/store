<?php

/**
 * Command-line application to clear abandoned carts from the database.
 *
 * Abandoned carts are based on expired sessions. If a cart entry is session-
 * based and the sessino identifier no longer exists in the sessions directory,
 * the cart is considered abandoned and remvoed from the database.
 *
 * This relies on some asumptions about the session storage mechanism. Sessions
 * must uses files in a flat directory.
 *
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreCartCleaner extends SiteCommandLineApplication
{
    public function init()
    {
        $this->initModules();
        $this->db->loadModule('Datatype', null, true);
    }

    public function run()
    {
        $this->init();
        $this->parseCommandLineArguments();

        if (mb_strpos($this->session->getSavePath(), ';') !== false) {
            $this->terminate(Store::_('Cannot automatically clean cart ' .
                'entries when using multiple levels of session files. See ' .
                "session.save_path documentation.\n"));
        }

        if (ini_get('session.save_handler') !== 'files') {
            $this->terminate(Store::_('Cannot automatically clean cart ' .
                'entries when not using the files session backend. See ' .
                "session.save_handler documentation.\n"));
        }

        $this->debug(Store::_("Finding expired sessions:\n"));
        $expired_sessions = [];
        foreach ($this->getSessionIds() as $session_id) {
            if (!$this->validateSessionId($session_id)) {
                $this->debug('=> ' . sprintf(
                    Store::_("session %s is expired\n"),
                    $session_id
                ));

                $expired_sessions[] = $session_id;
            }
        }
        $this->debug(sprintf(
            Store::_("Found %s expired sessions.\n\n"),
            count($expired_sessions)
        ));

        $expired_sessions_sql =
            $this->db->datatype->implodeArray($expired_sessions, 'text');

        $sql = sprintf(
            'select count(id) from CartEntry
			where sessionid in (%s)',
            $expired_sessions_sql
        );

        $total = SwatDB::queryOne($this->db, $sql);
        $this->debug(sprintf(
            Store::_('Deleting %s expired cart entries ... '),
            $total
        ));

        $sql = sprintf(
            'delete from CartEntry
			where sessionid in (%s)',
            $expired_sessions_sql
        );

        SwatDB::exec($this->db, $sql);

        $this->debug(Store::_("done\n"));
    }

    protected function getSessionIds()
    {
        $session_ids = [];
        $sql = 'select distinct sessionid from CartEntry
			where sessionid is not null';

        $entries = SwatDB::query($this->db, $sql);
        foreach ($entries as $entry) {
            $session_ids[] = $entry->sessionid;
        }

        return $session_ids;
    }

    protected function validateSessionId($session_id)
    {
        $valid = true;

        $filename = $this->session->getSavePath() . '/sess_' . $session_id;
        if (!file_exists($filename)) {
            $valid = false;
        }

        return $valid;
    }

    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config'   => StoreCommandLineConfigModule::class,
                'database' => SiteDatabaseModule::class,
                'session'  => SiteSessionModule::class,
            ]
        );
    }

    /**
     * Adds configuration definitions to the config module of this application.
     *
     * @param SiteConfigModule $config the config module of this application to
     *                                 which to add the config definitions
     */
    protected function addConfigDefinitions(SiteConfigModule $config)
    {
        parent::addConfigDefinitions($config);
        $config->addDefinitions(Store::getConfigDefinitions());
    }
}
