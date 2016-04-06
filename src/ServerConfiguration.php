<?php
/**
 * Created by PhpStorm.
 * User: mike.darke
 * Date: 05/04/2016
 * Time: 08:54
 */

namespace Darke\Solr;


class ServerConfiguration
{
    /**
     * Servlet mappings
     */
    const PING_SERVLET = 'admin/ping';
    const UPDATE_SERVLET = 'update';
    const SEARCH_SERVLET = 'select';
    const SYSTEM_SERVLET = 'admin/system';
    const THREADS_SERVLET = 'admin/threads';
    const EXTRACT_SERVLET = 'update/extract';

    /**
     * Server identification strings
     *
     * @var string
     */
    protected $host;
    protected $port;
    protected $path;
    protected $solrWriter;
    protected $pingUrl;
    protected $updateUrl;
    protected $searchUrl;
    protected $systemUrl;
    protected $threadsUrl;
    protected $proxy;
    protected $read = true;
    protected $write = true;
    protected $queryDelimiter = '?';
    protected $queryStringDelimiter = '&';
    protected $queryBracketsEscaped = true;

    public function __construct($host = 'localhost', $port = 8180, $path = '/solr/', $solr_writer = 'json', $proxy = '', $read = true, $write = true) {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->solrWriter = $solr_writer;

        if (!empty($proxy)) {
            $this->proxy = $proxy;
        }

        $this->initUrls();
        $this->read = $read;
        $this->write = $write;
    }

    /**
     * @return string
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param string $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * @return string
     */
    public function getSolrWriter()
    {
        return $this->solrWriter;
    }


    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getPingUrl()
    {
        return $this->pingUrl;
    }

    /**
     * @return mixed
     */
    public function getUpdateUrl()
    {
        return $this->updateUrl;
    }

    /**
     * @return mixed
     */
    public function getSearchUrl()
    {
        return $this->searchUrl;
    }


    /**
     * @return mixed
     */
    public function getSystemUrl()
    {
        return $this->systemUrl;
    }

    /**
     * @return mixed
     */
    public function getThreadsUrl()
    {
        return $this->threadsUrl;
    }


    /**
     * Return a valid http URL given this server's host, port and path and a provided servlet name
     *
     * @param string $servlet
     * @return string
     */
    protected function constructUrl($servlet, $params = array())
    {
        if (count($params))
        {
            //escape all parameters appropriately for inclusion in the query string
            $escapedParams = array();

            foreach ($params as $key => $value)
            {
                $escapedParams[] = urlencode($key) . '=' . urlencode($value);
            }

            $queryString = $this->queryDelimiter . implode($this->queryStringDelimiter, $escapedParams);
        }
        else
        {
            $queryString = '';
        }

        return 'http://' . $this->host . ':' . $this->port . $this->path . $servlet . $queryString;
    }

    /**
     * Construct the Full URLs for the three servlets we reference
     */
    protected function initUrls()
    {
        //Initialize our full servlet URLs now that we have server information
        $this->extractUrl = $this->constructUrl(self::EXTRACT_SERVLET);
        $this->pingUrl = $this->constructUrl(self::PING_SERVLET, ['wt' => $this->solrWriter]);
        $this->searchUrl = $this->constructUrl(self::SEARCH_SERVLET, ['wt' => $this->solrWriter]);
        $this->systemUrl = $this->constructUrl(self::SYSTEM_SERVLET, ['wt' => $this->solrWriter]);
        $this->threadsUrl = $this->constructUrl(self::THREADS_SERVLET, ['wt' => $this->solrWriter]);
        $this->updateUrl = $this->constructUrl(self::UPDATE_SERVLET, ['wt' => $this->solrWriter]);
    }
}