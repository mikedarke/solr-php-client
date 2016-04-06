<?php

class CriteriaTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleCriteria() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->add('*', '*');
        $built = $criteria->build();

        $this->assertEquals('(*:*)', $built);
    }

    public function testAndCriteria() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->add('name', 'test');
        $criteria->add('id', '1');
        $built = $criteria->build();
        $this->assertEquals('(name:test AND id:1)', $built);

        $criteria->add('location', 'earth');
        $built = $criteria->build();
        $this->assertEquals('(name:test AND id:1 AND location:earth)', $built);
    }

    public function testGreaterThan() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->addGreaterThan('price', 10);
        $built = $criteria->build();
        $this->assertEquals('(price:[10 TO *])', $built);
    }

    public function testLessThan() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->addLessThan('price', 100);
        $built = $criteria->build();
        $this->assertEquals('(price:[* TO 100])', $built);
    }

    public function testRange() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->addRange('price', 1, 1000);
        $built = $criteria->build();
        $this->assertEquals('(price:[1 TO 1000])', $built);
    }

    public function testMultiValue() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->addMultiValue('price', [10, 20, 30]);
        $built = $criteria->build();
        $this->assertEquals('((price:"10" OR price:"20" OR price:"30"))', $built);

        $criteria->addMultiValue('name', ["test1", "test2"]);
        $built = $criteria->build();
        $this->assertEquals('((price:"10" OR price:"20" OR price:"30") AND (name:"test1" OR name:"test2"))', $built);
    }

    public function testLessAndGreaterThan() {
        $criteria = new \Darke\Solr\Query\Criteria();
        $criteria->addGreaterThan('price', 10);
        $criteria->addLessThan('price', 100);
        $built = $criteria->build();
        $this->assertEquals('(price:[10 TO *] AND price:[* TO 100])', $built);
    }
}
