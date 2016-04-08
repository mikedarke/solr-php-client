<?php

namespace Darke\Solr\Query;


class Criteria
{
    protected $operator;
    /** @var  array Array of query components */
    protected $query;

    public function __construct($operator = 'AND') {
        $this->operator = $operator;
    }

    /**
     * Add a query value
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function add($key, $value) {
        $this->query[] = "{$key}:{$value}";

        return $this;
    }

    /**
     * Add a single value criteria
     *
     * @param $key
     * @param $value
     *
     * @return QueryBuilder
     */
    public function addPhrase($key, $value)
    {
        $this->query[] = "{$key}:\"{$value}\"";

        return $this;
    }

    /**
     * Add a multi value criteria
     *
     * @param $key
     * @param $values
     *
     * @return QueryBuilder
     */
    public function addMultiValue($key, $values)
    {
        $params = array();
        foreach ($values as $singleValue) {
            $params[] = "{$key}:\"{$singleValue}\"";
        }
        $this->query[] = "(" . implode(" OR ", $params) . ")";

        return $this;
    }

    /**
     * Add a range criteria
     *
     * @param $key
     * @param $from
     * @param $to
     *
     * @return QueryBuilder
     */
    public function addRange($key, $from, $to)
    {
        $this->query[] = "{$key}:[{$from} TO {$to}]";

        return $this;
    }

    /**
     * Add a greater than criteria
     *
     * @param $key
     * @param $value
     *
     * @return QueryBuilder
     */
    public function addGreaterThan($key, $value)
    {
        $this->addRange($key, $value, '*');

        return $this;
    }

    /**
     * Add a less than criteria
     *
     * @param $key
     * @param $value
     *
     * @return QueryBuilder
     */
    public function addLessThan($key, $value)
    {
        $this->addRange($key, '*', $value);

        return $this;
    }

    /**
     * @return array
     */
    public function build() {
        if (empty($this->query)) {
            //include everything
            return '*:*';
        }

        $delimiter = ' ' . $this->operator . ' ';
        $built = implode($delimiter, $this->query);

        return '(' . $built . ')';
    }
}