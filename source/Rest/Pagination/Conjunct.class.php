<?php

namespace Rest\Pagination;

/**
 * Represents one of possibly many conjuncts in a WHERE clause which is used to build conditional searching.
 */
class Conjunct
{
    /**
     * @var string $sql The SQL excerpt that defines the conjunct, using placeholders for variables.
     */
    private $sql;

    /**
     * @var array $values The string-indexed array of placeholder values to plug into the SQL. They should follow the
     * form: 'my_value' => 5, etc.
     */
    private $values;

    /**
     * Conjunct constructor.
     * @param string $sql
     * @param array $values
     */
    public function __construct(string $sql, array $values)
    {
        $this->sql = $sql;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}