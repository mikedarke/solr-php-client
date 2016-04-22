<?php

namespace Darke\Solr;

use Darke\Solr\Exception\HttpException;
use Darke\Solr\Query\QueryBuilder;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

class SolrService
{
    /**
     * Search HTTP Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_HEAD = 'HEAD';

    /** @var  ServerConfiguration $serverConfiguration */
    protected $serverConfiguration;

    /**
     * Whether {@link Apache_Solr_Response} objects should create {@link Apache_Solr_Document}s in
     * the returned parsed data
     *
     * @var boolean
     */
    protected $_createDocuments = true;

    /**
     * Whether {@link Apache_Solr_Response} objects should have multivalue fields with only a single value
     * collapsed to appear as a single value would.
     *
     * @var boolean
     */
    protected $_collapseSingleValueArrays = true;

    /**
     * HTTP Transport implementation (pluggable)
     *
     * @var ClientInterface
     */
    protected $httpClient = null;


    /**
     * Constructor. All parameters are optional and will take on default values
     * if not specified.
     *
     * @param ServerConfiguration $serverConfiguration
     * @param ClientInterface $httpClient
     */
    public function __construct(ServerConfiguration $serverConfiguration, ClientInterface $httpClient = null) {
        if (!$httpClient) {
            $httpClient = new GuzzleClient();
        }
        $this->setServerConfiguration($serverConfiguration);
        $this->setHttpClient($httpClient);
    }

    /**
     * @return ServerConfiguration
     */
    public function getServerConfiguration()
    {
        return $this->serverConfiguration;
    }

    /**
     * @param ServerConfiguration $serverConfiguration
     */
    public function setServerConfiguration($serverConfiguration)
    {
        $this->serverConfiguration = $serverConfiguration;
    }

    /**
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param ClientInterface $httpClient
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Call the /admin/ping servlet, can be used to quickly tell if a connection to the
     * server is able to be made.
     *
     * @param float $timeout maximum time to wait for ping in seconds, -1 for unlimited (default is 2)
     * @return float Actual time taken to ping the server, FALSE if timeout or HTTP error status occurs
     */
    public function ping($timeout = 2)
    {
        $start = microtime(true);

        $options = [
          'connect_timeout' => ($timeout ? $timeout : 2)
        ];

        $httpResponse = $this->request(self::METHOD_HEAD, $this->serverConfiguration->getPingUrl(), $options);

        if ($httpResponse->getStatusCode() == 200)
        {
            return microtime(true) - $start;
        }

        return false;
    }

    /**
     * Call the /admin/system servlet and retrieve system information about Solr
     *
     * @return string
     *
     * @throws HttpException If an error occurs during the service call
     */
    public function system()
    {
        $response = $this->get($this->serverConfiguration->getSystemUrl());
        return $this->getResponseBody($response);
    }

    /**
     * Call the /admin/threads servlet and retrieve information about all threads in the
     * Solr servlet's thread group. Useful for diagnostics.
     *
     * @return string
     *
     * @throws HttpException If an error occurs during the service call
     */
    public function threads()
    {
        $response = $this->get($this->serverConfiguration->getThreadsUrl());
        return $this->getResponseBody($response);
    }

    /**
     * @param      $documents
     * @param bool $overwrite
     * @param int  $commitWithin
     *
     * @return \Psr\Http\Message\StreamInterface|string
     * @throws \Darke\Solr\Exception\HttpException
     */
    public function add($documents, $overwrite = true, $commitWithin = 5000) {
        $response = $this->post($this->serverConfiguration->getUpdateUrl(), $documents);
        return $this->getResponseBody($response);
    }

    /**
     * Delete a document by it's ID
     *
     * @param $id
     *
     * @return \Psr\Http\Message\StreamInterface|string
     */
    public function deleteById($id) {
        return $this->delete(['id' => $id]);
    }

    /**
     * Delete document(s) using given param
     *
     * Example:
     *  $service->delete(['query' => '*:*']);
     *  $service->delete(['id' => 1]);
     *
     * @param $param
     *
     * @return \Psr\Http\Message\StreamInterface|string
     * @throws \Darke\Solr\Exception\HttpException
     */
    public function delete($param) {
        $deleteDoc = [
            'delete' => $param
        ];
        $response = $this->post($this->serverConfiguration->getUpdateUrl(), $deleteDoc);
        return $this->getResponseBody($response);
    }

    /**
     * @param \Darke\Solr\Query\QueryBuilder $query
     *
     * @return \Psr\Http\Message\StreamInterface|string
     * @throws \Darke\Solr\Exception\HttpException
     */
    public function search(QueryBuilder $query) {
        $searchUrl = $this->serverConfiguration->getSearchUrl();
        $params = $query->build();
        $response = $this->post($searchUrl, $params);

        $body = $this->getResponseBody($response);
        return $body;
    }

    /**
     * Get the response body
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\StreamInterface|string
     */
    protected function getResponseBody(ResponseInterface $response) {
        $responseBody = $response->getBody();
        if ($responseBody instanceof Stream) {
            $body = '';
            while (!$responseBody->eof()) {
                $body .= $responseBody->read(1024);
            }

            return $body;
        }

        return $responseBody;
    }

    protected function generateQueryString($params)
    {
        $queryString = http_build_query($params);
        return preg_replace('/\\[(?:[0-9]|[1-9][0-9]+)\\]=/', '=', $queryString);
    }

    /**
     * Central method for making a get operation against this Solr Server
     *
     * @param string $url
     * @param float $timeout Read timeout in seconds
     *
     * @return ResponseInterface
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function get($url, $timeout = FALSE)
    {
        $options = [
          'connect_timeout' => ($timeout ? $timeout : 10)
        ];

        return $this->request(self::METHOD_GET, $url, $options);
    }

    /**
     * Central method for making a post operation against this Solr Server
     *
     * @param string $url
     * @param string $json
     * @param float $timeout Read timeout in seconds
     *
     * @return string
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function post($url, $json, $timeout = FALSE)
    {
        $options = [
          'json' => $json,
          'connect_timeout' => ($timeout ? $timeout : 10),
          'stream' => false
        ];

        return $this->request(self::METHOD_GET, $url, $options);
    }

    /**
     * @param $method
     * @param $url
     * @param $options
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Darke\Solr\Exception\HttpException
     */
    protected function request($method, $url, $options) {

        if (empty($options['proxy'])) {
            $proxy = $this->serverConfiguration->getProxy();
            if (!empty($proxy)) {
                $options['proxy'] = $proxy;
            }
        }

        $httpTransport = $this->getHttpClient();
        $httpResponse = $httpTransport->request($method, $url, $options);

        if ($httpResponse->getStatusCode() != 200)
        {
            throw new HttpException($httpResponse);
        }

        return $httpResponse;
    }
}