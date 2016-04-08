<?php

/**
 * Created by PhpStorm.
 * User: mike.darke
 * Date: 06/04/2016
 * Time: 14:55
 */
class QueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testAddCriteria() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $c = new \Darke\Solr\Query\Criteria();
        $c->addPhrase('name', 'test1');
        $qb->addCriteria($c);
        $c2 = new \Darke\Solr\Query\Criteria();
        $c2->add('id', 1);
        $qb->addOr($c2);
        $built = $qb->build();
        $this->assertEquals('(name:"test1") OR (id:1)', $built['query']);
    }

    public function testAddMultiValueCriteria() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $c = new \Darke\Solr\Query\Criteria();
        $c->addMultiValue('cat', ['sci-fi', 'horror', 'drama']);
        $qb->addCriteria($c);
        $c2 = new \Darke\Solr\Query\Criteria();
        $c2->addMultiValue('id', [1, 2, 3]);
        $c2->add('name', '*alien*');
        $qb->addCriteria($c2);
        $built = $qb->build();
        $this->assertEquals('((cat:"sci-fi" OR cat:"horror" OR cat:"drama")) AND ((id:"1" OR id:"2" OR id:"3") AND name:*alien*)', $built['query']);
    }

    public function testAddEmptyCriteria() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $c = new \Darke\Solr\Query\Criteria();
        $qb->addCriteria($c);
        $built = $qb->build();
        $this->assertEquals('*:*', $built['query']);
    }

    public function testAddNot() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $c = new \Darke\Solr\Query\Criteria();
        $c->add('name', 'test1');
        $qb->addNot($c);
        $built = $qb->build();
        $this->assertEquals(' NOT (name:test1)', $built['query']);
    }

    public function testFilter() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $qb->filter('name', 'test1');
        $built = $qb->build();
        $this->assertEquals(1, count($built['filter']));
        $this->assertEquals('name:test1', $built['filter'][0]);
    }

    public function testOffset() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $qb->offset(20);
        $built = $qb->build();
        $this->assertEquals(20, $built['offset']);
    }

    public function testLimit() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $qb->limit(10);
        $built = $qb->build();
        $this->assertEquals(10, $built['limit']);
    }

    public function testSort() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $qb->sort('name', 'asc');
        $built = $qb->build();
        $this->assertEquals('name asc', $built['sort']);
    }

    public function testFields() {
        $qb = new \Darke\Solr\Query\QueryBuilder();
        $qb->fields(['name', 'id', 'cat']);
        $built = $qb->build();
        $this->assertEquals('name,id,cat', $built['fields']);
    }
}
