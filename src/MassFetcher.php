<?php

namespace parseword\MassFetcher;

use parseword\logger\Logger;
use parseword\MassFetcher\LoggedException;

/**
 * The MassFetcher class enforces configuration options, and coordinates request
 * activity by maintaining the queue of worker threads.
 *
 * *****************************************************************************
 * This file is part of MassFetcher.
 * Copyright 2015, 2019 Shaun Cummiskey <shaun@shaunc.com> <https://shaunc.com/>
 * Repository: <https://github.com/parseword/massfetcher/>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
class MassFetcher
{

    private $connectTimeout = 10;
    private $fallbackToHttp = false;
    private $followRedirects = true;
    private $gracePeriod = 86400;
    private $inputFile = null;
    private $maxThreads = 48;
    private $outputDirectory = 'data';
    private $requestPath = '/';
    private $strictFilenameMatching = true;
    private $transferTimeout = 30;
    private $userAgent = '';
    private $verifySsl = true;
    private $workers = [];

    /**
     * Constructor. Performs some sanity checks on the environment.
     *
     * @throws LoggedException
     */
    public function __construct() {

        Logger::setLabel('MassFetcher');

        /* Must be run from CLI */
        if (php_sapi_name() != 'cli') {
            Logger::error('This application must be run from the command line. '
                    . 'pthreads is incompatible with the web SAPI.', true);
            exit;
        }

        /* Check that the pthreads dependency is satisfied */
        if (!extension_loaded('pthreads')) {
            Logger::error('This application requires a version of PHP with '
                    . 'pthreads support. You may be able to enable it without '
                    . 'recompiling; see https://php.net/pthreads/', true);
            exit;
        }

        /* Check that the cURL dependency is satisfied */
        if (!extension_loaded('curl')) {
            Logger::error("This application requires PHP's cURL extension. "
                    . 'See https://php.net/curl/', true);
            exit;
        }
    }

    /**
     * Return the configured connection timeout in seconds.
     *
     * @return int
     */
    public function getConnectTimeout(): int {
        return $this->connectTimeout;
    }

    /**
     * Return whether or not to fall back to HTTP if the HTTPS connection fails.
     *
     * @return bool
     */
    public function getFallbackToHttp(): bool {
        return $this->fallbackToHttp;
    }

    /**
     * Return whether or not to follow 301/302 Location header redirects.
     *
     * @return bool
     */
    public function getFollowRedirects(): bool {
        return $this->followRedirects;
    }

    /**
     * Return the grace period in seconds. If a file exists on disk that
     * corresponds to the current request, and it's newer than this value,
     * the request will be skipped.
     *
     * @return int
     */
    public function getGracePeriod(): int {
        return $this->gracePeriod;
    }

    /**
     * Return the path to the input file that contains one hostname per line.
     *
     * @return string
     */
    public function getInputFile(): string {
        return $this->inputFile;
    }

    /**
     * Return the maximum number of concurrent threads to use.
     *
     * @return int
     */
    public function getMaxThreads(): int {
        return $this->maxThreads;
    }

    /**
     * Return the path to the directory where retrieved files should be saved.
     *
     * @return string
     */
    public function getOutputDirectory(): string {
        return $this->outputDirectory;
    }

    /**
     * Return the path to request, i.e. the HTTP GET URI.
     *
     * @return string
     */
    public function getRequestPath(): string {
        return $this->requestPath;
    }

    /**
     * Return whether or not to require that the eventual destination URI, after
     * following all redirects, must match the requested filename in order for
     * the file to be saved to disk.
     *
     * @return bool
     */
    public function getStrictFilenameMatching(): bool {
        return $this->strictFilenameMatching;
    }

    /**
     * Return the post-connection transfer timeout in seconds.
     *
     * @return int
     */
    public function getTransferTimeout(): int {
        return $this->transferTimeout;
    }

    /**
     * Return the User-agent string to use when making HTTP requests.
     *
     * @return string
     */
    public function getUserAgent(): string {
        return $this->userAgent;
    }

    /**
     * Return whether or not to check that the remote server's TLS certificate
     * is valid and matches the hostname for this request.
     *
     * @return bool
     */
    public function getVerifySsl(): bool {
        return $this->verifySsl;
    }

    /**
     * Set the configured connection timeout in seconds.
     *
     * @param int $connectTimeout
     * @return void
     */
    public function setConnectTimeout(int $connectTimeout): void {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Set whether or not to fall back to HTTP if the HTTPS connection fails.
     *
     * @param bool $fallbackToHttp
     * @return void
     */
    public function setFallbackToHttp(bool $fallbackToHttp): void {
        $this->fallbackToHttp = $fallbackToHttp;
    }

    /**
     * Set whether or not to follow 301/302 Location header redirects.
     *
     * @param bool $followRedirects
     * @return void
     */
    public function setFollowRedirects(bool $followRedirects): void {
        $this->followRedirects = $followRedirects;
    }

    /**
     * Set the grace period in seconds. If a file exists on disk that
     * corresponds to the current request, and it's newer than this value,
     * the request will be skipped.
     *
     * @param int $gracePeriod
     * @return void
     */
    public function setGracePeriod(int $gracePeriod): void {
        $this->gracePeriod = $gracePeriod;
    }

    /**
     * Set the path to the input file that contains one hostname per line.
     *
     * @param string $inputFile
     * @return void
     * @throws LoggedException
     */
    public function setInputFile(string $inputFile): void {
        /* Reject a non-existent file */
        if (!file_exists($inputFile)) {
            throw new LoggedException("The specified file doesn't exist: "
                    . $inputFile);
        }
        $this->inputFile = $inputFile;
    }

    /**
     * Set the maximum number of concurrent threads to use.
     *
     * @param int $maxThreads
     * @return void
     */
    public function setMaxThreads(int $maxThreads): void {
        $this->maxThreads = $maxThreads;
    }

    /**
     * Set the path to the directory where retrieved files should be saved.
     *
     * @param string $outputDirectory
     * @return void
     */
    public function setOutputDirectory(string $outputDirectory): void {
        $this->outputDirectory = $outputDirectory;
    }

    /**
     * Set the path to request, i.e. the HTTP GET URI.
     *
     * @param string $requestPath Must begin with '/'
     * @return void
     * @throws LoggedException
     */
    public function setRequestPath(string $requestPath): void {
        if (strpos($requestPath, '/') !== 0) {
            throw new LoggedException('First character of requestPath MUST be '
                    . 'a forward slash; call setRequestPath() with a properly-'
                    . 'formed path.');
        }
        $this->requestPath = $requestPath;
    }

    /**
     * Set whether or not to require that the eventual destination URI, after
     * following all redirects, must match the requested filename in order for
     * the file to be saved to disk.
     *
     * @param bool $strictFilenameMatching
     * @return void
     */
    public function setStrictFilenameMatching(bool $strictFilenameMatching): void {
        $this->strictFilenameMatching = $strictFilenameMatching;
    }

    /**
     * Set the post-connection transfer timeout in seconds.
     *
     * @param int $transferTimeout
     * @return void
     */
    public function setTransferTimeout(int $transferTimeout): void {
        $this->transferTimeout = $transferTimeout;
    }

    /**
     * Set the User-agent string to use when making HTTP requests.
     *
     * @param string $userAgent
     * @return void
     */
    public function setUserAgent(string $userAgent): void {
        $this->userAgent = $userAgent;
    }

    /**
     * Set Return whether or not to check that the remote server's TLS certificate
     * is valid and matches the hostname for this request.
     *
     * @param bool $verifySsl
     * @return void
     */
    public function setVerifySsl(bool $verifySsl): void {
        $this->verifySsl = $verifySsl;
    }

    /**
     * The application's main loop. This method maintains the queue of worker
     * threads, dispatching a new GetRequestThread object for each hostname in
     * the input file.
     *
     * @return float The elapsed seconds between commencement and completion
     * @throws LoggedException
     */
    public function fetch(): float {

        /* Ensure that a User-agent has been set */
        if (empty($this->getUserAgent())) {
            Logger::error('No User-agent has been configured. You must call '
                    . 'setUserAgent() from inside config.php.', true);
            exit;
        }

        /* Note the startup time */
        $timeStart = microtime(true);
        Logger::info('MassFetcher is starting up');

        /*
         * Kludge to prime the autoloader in the global execution context;
         * avoids having to mess with the autoloader in the thread class.
         */
        new RequestData();

        /*
         * Define some constants so we don't have to pass this information
         * into each thread.
         */
        define('FETCHER_CONNECT_TIMEOUT', $this->getConnectTimeout());
        define('FETCHER_FALLBACK_TO_HTTP', $this->getFallbackToHttp());
        define('FETCHER_FOLLOW_REDIRECTS', $this->getFollowRedirects());
        define('FETCHER_GET', $this->getRequestPath());
        define('FETCHER_GRACE_PERIOD', $this->getGracePeriod());
        define('FETCHER_OUTPUT_DIR', $this->getOutputDirectory());
        define('FETCHER_STRICT_FILENAMES', $this->getStrictFilenameMatching());
        define('FETCHER_TRANSFER_TIMEOUT', $this->getTransferTimeout());
        define('FETCHER_USER_AGENT', $this->getUserAgent());
        define('FETCHER_VERIFY_SSL', $this->getVerifySsl());

        /* Open the input file containing the target hostnames */
        $fp = @fopen($this->getInputFile(), 'r');
        if ($fp === false) {
            throw new LoggedException("Couldn't open input file "
                    . "{$this->inputFile} ... Check name and permissions."
            );
        }

        /* Populate the workers queue with an initial batch of threads */
        for ($i = 0; $i < $this->maxThreads && !feof($fp); $i++) {
            /* Create a RequestData target object from the next hostname */
            if (!$this->validate($hostname = trim(fgets($fp)))) {
                $i--;
                continue;
            }

            /* Push a thread onto the queue and start it */
            $threadId = date('YmdHis-') . bin2hex(random_bytes(4));
            $workers[$threadId] = new GetRequestThread($threadId, $hostname);
            $workers[$threadId]->start();
        }

        /* Manage the thread queue until there's no work remaining */
        while (1) {

            /* Be kind, yield time */
            usleep(100000);

            /* Track how many workers will need to be replenished */
            $deadThreads = 0;

            /* Iterate over the queue looking for finished workers */
            foreach ($workers as $thread) {

                /* If this thread is still working, leave it alone */
                if ($thread->isRunning()) {
                    continue;
                }

                /* Join and destroy the completed thread */
                Logger::debug('Joining thread ' . $thread->getId());
                if ($thread->join()) {
                    unset($workers[$thread->getId()]);
                    $deadThreads++;
                }
            }

            /* If no threads were finished, go wait again */
            if ($deadThreads == 0) {
                continue;
            }

            /* Replenish the workers queue if necessary */
            for ($i = 0; $i < $deadThreads && !feof($fp); $i++) {

                /* Create a RequestData target object from the next hostname */
                if (!$this->validate($hostname = trim(fgets($fp)))) {
                    $i--;
                    continue;
                }

                /* Push a thread onto the queue and start it */
                $threadId = date('YmdHis-') . bin2hex(random_bytes(4));
                Logger::debug('workers[]: ' . count($workers) . ' deadThreads: '
                        . $deadThreads . ". Creating new thread {$threadId}");
                $workers[$threadId] = new GetRequestThread($threadId, $hostname);
                $workers[$threadId]->start();
            }

            /* Bail when we're out of work to do */
            if (feof($fp) && count($workers) == 0) {
                break;
            }
        }
        Logger::info('MassFetcher has finished');
        return round(microtime(true) - $timeStart, 3);
    }

    /**
     * Filter comment lines and blatantly invalid hostnames.
     *
     * @param string $hostname The hostname to test
     * @return bool False if the line is a comment or is clearly not a hostname
     */
    private function validate(string $hostname): bool {

        /* Skip comment lines */
        if (strpos($hostname, '#') === 0) {
            return false;
        }

        /* Rudimentary hostname formatting check */
        if (empty($hostname) || !preg_match('|([a-z0-9\-]+\.)+|i', $hostname)) {
            Logger::debug("Skipping invalid hostname: {$hostname}");
            return false;
        }

        return true;
    }

}
