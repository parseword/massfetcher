<?php

namespace parseword\MassFetcher;

/**
 * The GetRequest class holds metadata about an HTTP GET request.
 *
 * A GetRequest object containing the target hostname must be supplied to each
 * new GetRequestThread. When the GetRequestThread finishes its work, the
 * GetRequest object is populated with metadata regarding the disposition of
 * the request.
 *
 * The public distribution of MassFetcher doesn't do anything with these
 * objects, but you could plug this class up to an ORM and persist the data.
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
class RequestData
{

    /**
     * The size in bytes of the retrieved content, if any.
     *
     * @var int
     */
    private $bytes = 0;

    /**
     * The final destination URI after all redirects have been followed.
     *
     * @var string
     */
    private $effectiveUri = null;

    /**
     * An epoch timestamp representing the Last-Modified date for the retrieved
     * file, if the server sends this header.
     *
     * @var int
     */
    private $fileModifiedTime = null;

    /**
     * The hostname used for this request.
     *
     * @var string
     */
    private $hostname = null;

    /**
     * An epoch timestamp representing when the request was made to the server.
     *
     * @var int
     */
    private $requestTimestamp = 0;

    /**
     * The originally requested URI.
     *
     * @var string
     */
    private $requestUri = null;

    /**
     * The final HTTP status code returned by the server after all redirects
     * have been followed.
     *
     * @var int
     */
    private $responseCode = 0;

    /**
     * The retrieved content, if any.
     *
     * @var string
     */
    private $responseData = null;

    /**
     * The HTTP headers that the server sent in its final response after all
     * redirects have been followed.
     *
     * @var array
     */
    private $responseHeaders = [];

    /**
     * Return the size in bytes of the retrieved content, if any.
     *
     * @return int
     */
    public function getBytes(): int {
        return $this->bytes;
    }

    /**
     * Return the final destination URI after all redirects have been followed.
     *
     * @return string
     */
    public function getEffectiveUri(): string {
        return $this->effectiveUri;
    }

    /**
     * Return the epoch timestamp representing the Last-Modified date for the
     * retrieved file, if the server sends this header.
     *
     * @return int
     */
    public function getFileModifiedTime(): int {
        return $this->fileModifiedTime;
    }

    /**
     * Return the hostname used for this request.
     *
     * @return string
     */
    public function getHostname(): string {
        return $this->hostname;
    }

    /**
     * Return the epoch timestamp representing when the request was made to
     * the server.
     *
     * @return int
     */
    public function getRequestTimestamp(): int {
        return $this->requestTimestamp;
    }

    /**
     * Return the originally requested URI.
     *
     * @return string
     */
    public function getRequestUri(): string {
        return $this->requestUri;
    }

    /**
     * Return the final HTTP status code returned by the server after all
     * redirects have been followed.
     *
     * @return int
     */
    public function getResponseCode(): int {
        return $this->responseCode;
    }

    /**
     * Return the retrieved content, if any.
     *
     * @return string
     */
    public function getResponseData(): string {
        return $this->responseData;
    }

    /**
     * Return the HTTP headers that the server sent in its final response after
     * all redirects have been followed.
     * @return array
     */
    public function getResponseHeaders(): array {
        return $this->responseHeaders;
    }

    /**
     * Set the size in bytes of the retrieved content, if any.
     *
     * @param int $bytes
     */
    public function setBytes(int $bytes) {
        $this->bytes = $bytes;
    }

    /**
     * Set the final destination URI after all redirects have been followed.
     *
     * @param string $effectiveUri
     */
    public function setEffectiveUri(string $effectiveUri) {
        $this->effectiveUri = $effectiveUri;
    }

    /**
     * Set the epoch timestamp representing the Last-Modified date for the
     * retrieved file, if the server sends this header.
     *
     * @param int $fileModifiedTime
     */
    public function setFileModifiedTime(int $fileModifiedTime) {
        $this->fileModifiedTime = $fileModifiedTime;
    }

    /**
     * Set the hostname used for this request.
     *
     * @param string $hostname
     */
    public function setHostname(string $hostname) {
        $this->hostname = $hostname;
    }

    /**
     * Set the epoch timestamp representing when the request was made to
     * the server.
     *
     * @param int $requestTimestamp
     */
    public function setRequestTimestamp(int $requestTimestamp) {
        $this->requestTimestamp = $requestTimestamp;
    }

    /**
     * Set the originally requested URI.
     *
     * @param string $requestUri
     */
    public function setRequestUri(string $requestUri) {
        $this->requestUri = $requestUri;
    }

    /**
     * Set the final HTTP status code returned by the server after all redirects
     * have been followed.
     *
     * @param int $responseCode
     */
    public function setResponseCode(int $responseCode) {
        $this->responseCode = $responseCode;
    }

    /**
     * Set the retrieved content, if any.
     *
     * @param string $responseData
     */
    public function setResponseData(string $responseData) {
        $this->responseData = $responseData;
    }

    /**
     * Set the HTTP headers that the server sent in its final response after
     * all redirects have been followed.
     *
     * @param array $responseHeaders
     */
    public function setResponseHeaders(array $responseHeaders) {
        $this->responseHeaders = $responseHeaders;
    }

}
