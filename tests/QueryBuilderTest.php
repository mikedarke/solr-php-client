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
        $c->addSingleValue('name', 'test1');
        $qb->addCriteria($c);
        $c2 = new \Darke\Solr\Query\Criteria();
        $c2->addSingleValue('id', 1);
        $qb->addOr($c2);
        $built = $qb->build();
        $this->assertEquals('(name:"test1") OR (id:"1")', $built['query']);
    }
}
