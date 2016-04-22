<?php

class ServerConfigurationTest extends PHPUnit_Framework_TestCase
{
    public function testConstructUrl() {
        $s = new \Darke\Solr\ServerConfiguration('http://localhost:8983/solr/');
        $url = $s->getSearchUrl();
        $this->assertEquals('http://localhost:8983/solr/select?wt=json', $url);
        $url = $s->getPingUrl();
        $this->assertEquals('http://localhost:8983/solr/admin/ping?wt=json', $url);
    }
}
