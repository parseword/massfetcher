<?php

namespace parseword\MassFetcher;

use parseword\logger\Logger;
use parseword\MassFetcher\RequestData;

/**
 * The GetRequestThread class represents a worker thread. It performs a fetch
 * operation against one host, writing the result to disk if necessary.
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
class GetRequestThread extends \Thread
{

    /**
     * A unique ID for this thread.
     *
     * @var string
     */
    private $id = null;

    /**
     * The hostname that this thread will try to fetch data from.
     *
     * @var string
     */
    private $hostname = null;

    /**
     * Constructor.
     *
     * @param string $id A unique ID for this thread (caller must generate this)
     * @param string $hostname The target hostname
     */
    public function __construct($id, string $hostname) {
        $this->id = $id;
        $this->hostname = $hostname;
        Logger::debug("GetRequestThread {$this->id}: created, hostname {$hostname}");
    }

    /**
     * Return the unique ID for this thread.
     *
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Perform a GET request and write the results to disk if necessary.
     *
     * @return void
     */
    public function run(): void {

        /* Alias $this->hostname for convenience */
        $hostname = $this->hostname;

        /*
         * Create a RequestData object. We'll pass &it to the curlGet() method
         * to be populated with metadata about the request disposition.
         */
        $target = new RequestData();
        $target->setHostname($hostname);

        /*
         * Prepare the output path and filename.
         *
         * The spider writes its output in a two-level subdirectory structure
         * based on the hostname.
         *
         * For example, if FETCHER_GET is `/.well-known/keybase.txt`
         * and the site is twitter.com, we need `/t/w/twitter.com/.well-known/`
         * where we'll write the file `keybase.txt`
         */
        $outputPath = pathinfo(FETCHER_GET, PATHINFO_DIRNAME);
        $outputFile = pathinfo(FETCHER_GET, PATHINFO_BASENAME);

        /* If no filename is in the request path, force to index.html */
        if (empty($outputFile) || strpos($outputFile, '.') === false) {
            $outputFile = 'index.html';
        }

        /* Directory where the output file will go */
        $directory = FETCHER_OUTPUT_DIR
                . '/' . substr($hostname, 0, 1)
                . '/' . substr($hostname, 1, 1)
                . '/' . $hostname
                . $outputPath;

        /* Fully-qualified path for the output file */
        $absolutePath = str_replace('//', '/', "{$directory}/{$outputFile}");

        /*
         * If the target file already exists and was created within the grace
         * period, don't fetch it again. Suppress E_WARNING on nonexistent file.
         */
        if ((int) @filemtime($absolutePath) > (time() - FETCHER_GRACE_PERIOD)) {
            Logger::info("GetRequestThread {$this->id}: {$absolutePath} "
                    . 'was already fetched recently; skipping');
            return;
        }

        /* Attempt to fetch the target URI using HTTPS */
        $this->curlGet('https', $target);

        /* If HTTPS failed, should we try HTTP? */
        if (empty($target->getResponseData())) {
            if (FETCHER_FALLBACK_TO_HTTP) {
                /* Try using plain HTTP */
                Logger::debug("GetRequestThread {$this->id}: {$hostname} https "
                        . 'failed, trying fallback to http');
                $this->curlGet('http', $target);
            }
            else {
                /* Consider this fatal */
                Logger::debug("GetRequestThread {$this->id}: {$hostname} https "
                        . 'failed, aborting');
                return;
            }
        }

        /* Bail on empty response */
        if (empty($target->getResponseData())) {
            /* Complete failure, bail from the thread */
            Logger::debug("GetRequestThread {$this->id}: {$hostname} http "
                    . 'failed, aborting');
            return;
        }

        /* Bail on final (after redirect chain) status code other than 200 */
        if ($target->getResponseCode() != 200) {
            Logger::debug("GetRequestThread {$this->id}: {$hostname} sent "
                    . "status {$target->getResponseCode()}, aborting");
            return;
        }

        /*
         * If strict filename matching is enabled, and the server redirected
         * to a file of some other name, abort... *Unless* we're fetching
         * index pages, in which case mismatches are expected.
         */
        if (FETCHER_STRICT_FILENAMES && FETCHER_GET != '/') {
            $effective = parse_url($target->getEffectiveUri(), PHP_URL_PATH);
            if ($effective != FETCHER_GET) {
                Logger::debug("GetRequestThread {$this->id}: {$hostname} strict "
                        . 'filename match failed after redirect to '
                        . "{$target->getEffectiveUri()}, aborting");
                return;
            }
        }

        /* Ensure the target directory exists. */
        if (!file_exists($directory) && !mkdir($directory, 0755, true)) {
            Logger::error("GetRequestThread {$this->id}: Failed to create "
                    . 'directory structure ' . $directory);
            return;
        }

        /* Write the response to disk */
        Logger::debug("GetRequestThread {$this->id}: Writing {$absolutePath}");
        if (!file_put_contents($absolutePath, $target->getResponseData())) {
            Logger::error("GetRequestThread {$this->id}: Failed to write file "
                    . $absolutePath);
        }
        Logger::info("GetRequestThread {$this->id}: {$absolutePath} "
                . "saved successfully ({$target->getBytes()} bytes)");

        /*
         * At this point, you could wire the RequestData object up to an ORM
         * and persist the data somewhere...
         *
         * ...but this version of MassFetcher just nukes it.
         */
        unset($target);

        /* Be aggressive with cleanup in threaded contexts */
        unset($hostname, $outputPath, $outputFile, $directory, $absolutePath);

        return;
    }

    /**
     * Perform a GET request via cURL.
     *
     * @param string $protocol The protocol to use; a string literal, either
     *      'http' or 'https'
     * @param RequestData $target A reference to a RequestData object which
     *      will be populated based on the disposition of the request
     * @return bool True if a request was made and some response was received,
     *      false if an error was encountered. A true value does NOT speak to
     *      the validity of whatever data the server sent back, only that no
     *      failure was encountered during the request.
     */
    private function curlGet(string $protocol, RequestData &$target): bool {

        /* Check the protocol value */
        if (!in_array($protocol, ['http', 'https'])) {
            Logger::error("GetRequestThread {$this->id}: {$this->hostname} "
                    . "invalid protocol '{$protocol}', use 'http' or 'https'");
            return false;
        }

        /* An array to hold the response headers */
        $responseHeaders = [];

        /* Build the target URI */
        $uri = "{$protocol}://" . $target->getHostname() . FETCHER_GET;
        $target->setRequestUri($uri);

        /* Configure various transfer options for cURL */
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, FETCHER_CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FETCHER_FOLLOW_REDIRECTS);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: close']);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FETCHER_VERIFY_SSL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,
                FETCHER_VERIFY_SSL == true ? 2 : 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, FETCHER_TRANSFER_TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, FETCHER_USER_AGENT);

        /* Set a callback fn to get a copy of the server's response headers */
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function($ch, $header) use (&$responseHeaders) {
            $trimHeader = trim($header);
            if (!empty($trimHeader) && strpos($trimHeader, ':') !== false) {
                list ($name, $value) = explode(':', $trimHeader, 2);
                $name = trim($name);
                $responseHeaders[$name] = trim($value);
            }
            return strlen($header);
        });

        /* Perform the request and cache the reply in the RequestData object */
        Logger::debug("GetRequestThread {$this->id}: fetching {$uri}");
        $result = curl_exec($ch);
        $target->setResponseData($result);

        /* Bail if cURL reports an error condition */
        if (!empty(curl_error($ch))) {
            Logger::error("GetRequestThread {$this->id}: {$this->hostname} "
                    . 'cURL error: ' . curl_error($ch));
            return false;
        }

        /* Populate the RequestData object */
        $target->setResponseCode(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
        $target->setEffectiveUri(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
        Logger::debug("GetRequestThread {$this->id}: {$this->hostname} got "
                . 'response code ' . $target->getResponseCode());
        $target->setBytes(curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD));
        Logger::debug("GetRequestThread {$this->id}: {$this->hostname} got "
                . "{$target->getBytes()} bytes");
        $target->setFileModifiedTime(curl_getinfo($ch, CURLINFO_FILETIME));
        $target->setResponseHeaders($responseHeaders);
        $target->setRequestTimestamp(time());

        curl_close($ch);

        /* Be aggressive with cleanup in threaded contexts */
        unset($ch, $responseHeaders, $result, $uri);

        return true;
    }

}
