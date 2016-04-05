<?php

namespace Darke\Solr;

use Darke\Solr\Exception\HttpException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Stream;
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

        $options = [
          'connect_timeout' => ($timeout ? $timeout : 10)
        ];

        $proxy = $this->serverConfiguration->getProxy();
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        $httpResponse = $httpTransport->request('HEAD', $this->serverConfiguration->getPingUrl(), $options);

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

    public function add($documents, $overwrite = true, $commitWithin = 5000) {
        $response = $this->post($this->serverConfiguration->getUpdateUrl(), $documents);
        return $this->getResponseBody($response);
    }

    public function deleteById($id) {
        return $this->delete(['id' => $id]);
    }

    public function delete($param) {
        $deleteDoc = [
            'delete' => $param
        ];
        $response = $this->post($this->serverConfiguration->getUpdateUrl(), $deleteDoc);
        return $this->getResponseBody($response);
    }

    /**
     * Simple Search interface
     *
     * @param string $query The raw query string
     * @param int $offset The starting offset for result documents
     * @param int $limit The maximum number of result documents to return
     * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
     * @param string $method The HTTP method (Apache_Solr_Service::METHOD_GET or Apache_Solr_Service::METHOD::POST)
     * @return string
     *
     * @throws HttpException If an error occurs during the service call
     * @throws \Exception If an invalid HTTP method is used
     */
    public function search($query, $offset = 0, $limit = 10, $params = array(), $method = self::METHOD_GET)
    {
        // ensure params is an array
        if (!is_null($params))
        {
            if (!is_array($params))
            {
                // params was specified but was not an array - invalid
                throw new \Exception("\$params must be a valid array or null");
            }
        }
        else
        {
            $params = array();
        }

        // construct our full parameters

        // common parameters in this interface
        $params['wt'] = $this->serverConfiguration->getSolrWriter();
        $params['json.nl'] = $this->_namedListTreatment;

        $params['q'] = $query;
        $params['start'] = $offset;
        $params['rows'] = $limit;

        $searchUrl = $this->serverConfiguration->getSearchUrl();
        /** @var ResponseInterface $response */
        $response = null;
        if ($method == self::METHOD_GET)
        {
            $queryDelimiter = '?';
            $queryString = $this->generateQueryString($params);
            $response = $this->get($searchUrl . $queryDelimiter . $queryString);
        }
        else if ($method == self::METHOD_POST)
        {
            $response = $this->post($searchUrl, $params, FALSE);
        }
        else
        {
            throw new \Exception("Unsupported method '$method', please use the Apache_Solr_Service::METHOD_* constants");
        }

        $body = $this->getResponseBody($response);

        return $body;
    }

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
     * @return string
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function get($url, $timeout = FALSE)
    {
        $httpTransport = $this->getHttpClient();

        $options = [
          'connect_timeout' => ($timeout ? $timeout : 10)
        ];

        $proxy = $this->serverConfiguration->getProxy();
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        $httpResponse = $httpTransport->request(self::METHOD_GET, $url, $options);

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
     * @param string $json
     * @param float $timeout Read timeout in seconds
     *
     * @return string
     *
     * @throws HttpException If a non 200 response status is returned
     */
    protected function post($url, $json, $timeout = FALSE)
    {
        $httpTransport = $this->getHttpClient();

        $options = [
          'json' => $json,
          'connect_timeout' => ($timeout ? $timeout : 10),
          'stream' => false
        ];

        $proxy = $this->serverConfiguration->getProxy();
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        $httpResponse = $httpTransport->request(self::METHOD_POST, $url, $options);

        if ($httpResponse->getStatusCode() != 200)
        {
            throw new HttpException($httpResponse);
        }

        return $httpResponse;
    }
}