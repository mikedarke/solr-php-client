<?php
/**
 * Created by PhpStorm.
 * User: mike.darke
 * Date: 05/04/2016
 * Time: 17:06
 */

namespace Darke\Solr\Query;


class QueryBuilder
{
    /**
     * Query operators
     */
    const AND_OP = 'AND';
    const OR_OP = 'OR';
    const NOT_OP = 'NOT';

    /**
     * NamedList Treatment constants
     */
    const NAMED_LIST_FLAT = 'flat';
    const NAMED_LIST_MAP = 'map';

    /**
     * @var array The groups of query criteria
     */
    protected $criteriaGroups = [];

    /**
     * @var array An array of filters
     */
    protected $filters = [];

    /**
     * @var int Start of result
     */
    protected $offset = 0;
    /**
     * @var int Number of result to return
     */
    protected $limit = 20;
    /**
     * @var string The sort order of results
     */
    protected $sort;
    /**
     * @var array The fields to include in results
     */
    protected $fields;

    /**
     * How NamedLists should be formatted in the output.  This specifically effects facet counts. Valid values
     * are {@link Apache_Solr_Service::NAMED_LIST_MAP} (default) or {@link Apache_Solr_Service::NAMED_LIST_FLAT}.
     *
     * @var string
     */
    protected $namedListTreatment = self::NAMED_LIST_MAP;

    /**
     * Adds a criteria into a new group
     *
     * @param \Darke\Solr\Query\Criteria $criteria
     * @param string $operator
     *
     * @return $this
     */
    public function addCriteria(Criteria $criteria, $operator = self::AND_OP) {
        $group = new CriteriaGroup($operator);
        $group->addCriteria($criteria);
        $this->criteriaGroups[] = $group;

        return $this;
    }

    /**
     * @param \Darke\Solr\Query\Criteria $criteria
     *
     * @return $this
     */
    public function addOr(Criteria $criteria) {
        $this->addCriteria($criteria, self::OR_OP);

        return $this;
    }

    /**
     * @param \Darke\Solr\Query\Criteria $criteria
     *
     * @return $this
     */
    public function addNot(Criteria $criteria) {
        $this->addCriteria($criteria, self::NOT_OP);

        return $this;
    }

    /**
     * Add a filter
     *
     * @param $key
     * @param $value
     */
    public function filter($key, $value) {
        $this->filters[] = "{$key}:{$value}";
    }

    /**
     * Specify the start of result
     *
     * @param $offset
     *
     * @return QueryBuilder
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Specify number of document to return
     *
     * @param $limit
     *
     * @return QueryBuilder
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Specify the sorting field and order
     *
     * @param string $field
     * @param string $direction - either asc or desc
     *
     * @return QueryBuilder
     */
    public function sort($field, $direction = 'asc')
    {
        $this->sort = $field . ' ' . $direction;

        return $this;
    }

    /**
     * Specify the fields to return
     *
     * @param array $fields
     *
     * @return QueryBuilder
     */
    public function fields($fields = array())
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Build the query object that will be sent to Solr
     *
     * @return array
     */
    public function build() {
        $params = [];
        if (!empty($this->sort)) {
            $params['sort'] = $this->sort;
        }
        if (!empty($this->fields)) {
            $params['fields'] = implode(',', $this->fields);
        }

        $params['filter'] = $this->filters;

        $params['query'] = $this->getQuery();
        $params['offset'] = $this->offset;
        $params['limit'] = $this->limit;

        return $params;
    }

    /**
     * Get the imploded query string
     *
     * @return string
     */
    public function getQuery() {
        $queryString = '';
        $i = 0;
        /** @var CriteriaGroup $group */
        foreach ($this->criteriaGroups as $group) {
            if ($i > 0 || $group->getOperator() == self::NOT_OP) {
                $queryString .= ' ' . $group->getOperator() . ' ';
            }
            $queryString .= $group->build();
            $i++;
        }

        return $queryString;
    }

}