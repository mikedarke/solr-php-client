<?php

namespace Darke\Solr;

use Darke\Solr\Exception\HttpException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class SolrService
{

    /**
     * NamedList Treatment constants
     */
    const NAMED_LIST_FLAT = 'flat';
    const NAMED_LIST_MAP = 'map';
    /**
     * Search HTTP Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

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
     * How NamedLists should be formatted in the output.  This specifically effects facet counts. Valid values
     * are {@link Apache_Solr_Service::NAMED_LIST_MAP} (default) or {@link Apache_Solr_Service::NAMED_LIST_FLAT}.
     *
     * @var string
     */
    protected $_namedListTreatment = self::NAMED_LIST_MAP;

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
    public function __construct(ServerConfiguration $serverConfiguration, ClientInterface $httpClient) {
        $this->serverConfiguration = $serverConfiguration;
        $this->setHttpClient($httpClient);
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
     * Central method for making a get operation against this Solr Server
     *
     * @param string $url
     * @param float $timeout Read timeout in seconds
     *
*@return Response
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function get($url, $timeout = FALSE)
    {
        $httpTransport = $this->getHttpClient();

        $httpResponse = $httpTransport->request(self::METHOD_GET, $url, [
            'connect_timeout' => ($timeout ? $timeout : 10)
        ]);

        if ($httpResponse->getStatusCode() != 200)
        {
            throw new HttpException($httpResponse);
        }

        return $httpResponse;
    }

    /**
     * Central method for making a post operation against this Solr Server
     *
     * @param string $url
     * @param string $rawPost
     * @param float $timeout Read timeout in seconds
     * @param string $contentType
     *
     * @return ResponseInterface
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function post($url, $rawPost, $timeout = FALSE, $contentType = 'text/json; charset=UTF-8')
    {
        $httpTransport = $this->getHttpClient();

        $httpResponse = $httpTransport->request(self::METHOD_POST, $url, [
          'body' => $rawPost,
          'connect_timeout' => ($timeout ? $timeout : 10),
          'headers' => ['Content-Type' => $contentType]
        ]);

        if ($httpResponse->getStatusCode() != 200)
        {
            throw new HttpException($httpResponse);
        }

        return $httpResponse;
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

        $httpTransport = $this->getHttpClient();
        $httpResponse = $httpTransport->request(self::METHOD_GET, $this->serverConfiguration->getPingUrl(),[
          'connect_timeout' => ($timeout ? $timeout : 10)
        ]);

        if ($httpResponse->getStatusCode() == 200)
        {
            return microtime(true) - $start;
        }
        else
        {
            return false;
        }
    }

    /**
     * Call the /admin/system servlet and retrieve system information about Solr
     *
     * @return Response
     *
     * @throws HttpException If an error occurs during the service call
     */
    public function system()
    {
        return $this->get($this->serverConfiguration->getSystemUrl());
    }

    /**
     * Call the /admin/threads servlet and retrieve information about all threads in the
     * Solr servlet's thread group. Useful for diagnostics.
     *
     * @return ResponseInterface
     *
     * @throws HttpException If an error occurs during the service call
     */
    public function threads()
    {
        return $this->get($this->serverConfiguration->getThreadsUrl());
    }

    public function addDocument($documentJson, $allowDuplicate = false, $overwritePending = true, $overwriteCommitted = true, $commitWithin = 0) {

    }

}