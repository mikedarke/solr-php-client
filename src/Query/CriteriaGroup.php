<?php
/**
 * Created by PhpStorm.
 * User: mike.darke
 * Date: 06/04/2016
 * Time: 11:00
 */

namespace Darke\Solr\Query;


class CriteriaGroup
{
    /** @var  string $operator */
    protected $operator;

    /** @var  array $criteria */
    protected $criteria;

    public function __construct($operator = 'AND') {
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param array $criteria
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    public function addCriteria(Criteria $criteria)
    {
        $this->criteria[] = $criteria;
    }

    public function build() {
        $built = [];
        /** @var Criteria $criteria */
        foreach ($this->criteria as $criteria) {
            $built[] = $criteria->build();
        }

        return explode(' AND ', $built);
    }
}