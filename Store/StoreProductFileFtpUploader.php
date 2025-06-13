<?php

/**
 * Abstract application to upload product listing files to a ftp server.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @todo      Figure out when setPath() is used, it may be a dead code path.
 */
abstract class StoreProductFileFtpUploader extends SiteCommandLineApplication
{
    // {{{ private property

    private $path = '';
    private $upload = true;
    private $display = false;
    private $keep_file = false;

    // }}}
    // {{{ public function __construct()

    public function __construct($id, $filename, $title, $documentation)
    {
        parent::__construct($id, $filename, $title, $documentation);

        // add display argument
        $display = new SiteCommandLineArgument(
            ['-d', '--display'],
            'setDisplay',
            Store::_('Display the generated file.')
        );

        $this->addCommandLineArgument($display);

        // add no-upload argument
        $no_upload = new SiteCommandLineArgument(
            ['-n', '--no-upload'],
            'setNoUpload',
            Store::_('Do not upload the generated file to the ftp server.')
        );

        $this->addCommandLineArgument($no_upload);

        // add keep file argument
        $keep_file = new SiteCommandLineArgument(
            ['-f', '--keep-file'],
            'setKeepFile',
            Store::_('Keep the generated file after the script is done.')
        );

        $this->addCommandLineArgument($keep_file);
    }

    // }}}
    // {{{ public function setPath()

    public function setPath($path)
    {
        $this->path = $path;
    }

    // }}}
    // {{{ public function setDisplay()

    public function setDisplay()
    {
        $this->display = true;
    }

    // }}}
    // {{{ public function setNoUpload()

    public function setNoUpload()
    {
        $this->upload = false;
    }

    // }}}
    // {{{ public function setKeepFile()

    public function setKeepFile()
    {
        $this->keep_file = true;
    }

    // }}}
    // {{{ public function run()

    public function run()
    {
        parent::run();

        if ($this->config->uri->cdn_base != '') {
            SiteImage::$cdn_base = $this->config->uri->cdn_base;
        }

        $this->lock();

        $filename = $this->getFilename();
        $generator = $this->getGenerator();
        $filename_with_path = $this->path . $filename;

        $this->debug(sprintf(
            Store::_('Generating Product file for %s ... '),
            $this->config->site->title
        ));

        $contents = $generator->generate();
        if (file_put_contents($filename_with_path, $contents, LOCK_EX) ===
            false) {
            $this->terminate(sprintf(Store::_(
                'Error writing file: %s',
                $filename_with_path
            )) . "\n\n");
        }

        $this->debug(Store::_('done') . "\n\n");

        if ($this->display) {
            $this->debug(Store::_('Generated File:') . "\n\n");

            echo $contents;

            $this->debug("\n\n");
        }

        if ($this->upload) {
            $server = $this->getFtpServer();
            $username = $this->getFtpUsername();
            $password = $this->getFtpPassword();

            $this->debug(sprintf(
                Store::_('Logging into %s as %s ... '),
                $server,
                $username
            ));

            $ftp_connection = ftp_connect($server);

            if ($ftp_connection === false) {
                throw new SwatException('Unable to connect to FTP server: ' .
                    $server);
            }

            $login_result = ftp_login(
                $ftp_connection,
                $username,
                $password
            );

            if ($ftp_connection == null || $login_result == null) {
                $this->terminate(Store::_('failed to log in') . "\n\n");
            } else {
                $this->debug(Store::_('done') . "\n\n");
            }

            $this->debug(Store::_('Uploading Product file ... '));

            $upload_result = ftp_put(
                $ftp_connection,
                $filename,
                $filename_with_path,
                FTP_BINARY
            );

            if (!$upload_result) {
                $this->terminate(Store::_('failed uploading') . "\n");
            } else {
                $this->debug(Store::_('done') . "\n\n");
            }

            ftp_close($ftp_connection);
        }

        if (!$this->keep_file) {
            unlink($filename_with_path);
        }

        $this->unlock();

        $this->debug(Store::_('All done.') . "\n");
    }

    // }}}
    // {{{ abstract protected function getGenerator()

    abstract protected function getGenerator();

    // }}}
    // {{{ abstract protected function getFilename()

    abstract protected function getFilename();

    // }}}
    // {{{ abstract protected function getFtpServer()

    abstract protected function getFtpServer();

    // }}}
    // {{{ abstract protected function getFtpUsername()

    abstract protected function getFtpUsername();

    // }}}
    // {{{ abstract protected function getFtpPassword()

    abstract protected function getFtpPassword();

    // }}}

    // boilerplate
    // {{{ protected function getDefaultModuleList()

    protected function getDefaultModuleList()
    {
        return array_merge(
            parent::getDefaultModuleList(),
            [
                'config'   => StoreCommandLineConfigModule::class,
                'database' => SiteDatabaseModule::class,
            ]
        );
    }

    // }}}
    // {{{ protected function addConfigDefinitions()

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

    // }}}
}
