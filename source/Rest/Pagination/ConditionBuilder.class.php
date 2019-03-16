<?php

namespace Rest\Pagination;

/**
 * Responsible for building a condition clause from a set of conjuncts.
 */
class ConditionBuilder
{
    /** @var Conjunct[] $conjuncts A list of conjuncts that will make up the WHERE condition. */
    private $conjuncts;

    /**
     * ConditionBuilder constructor.
     * @param Conjunct[] $conjuncts
     */
    public function __construct(array $conjuncts = [])
    {
        $this->conjuncts = $conjuncts;
    }

    /**
     * Determine if this builder has any conjuncts added to it.
     *
     * @return bool
     */
    public function hasConjuncts(): bool
    {
        return !empty($this->conjuncts);
    }

    /**
     * Adds a conjunct to this builder.
     *
     * @param Conjunct $conjunct
     */
    public function addConjunct(Conjunct $conjunct)
    {
        $this->conjuncts[] = $conjunct;
    }

    /**
     * Adds the conjunct only if all the specified keys exist in $args.
     *
     * @param string $sql
     * @param array $keys
     * @param array $args
     * @return bool True if a conjunct was added, or false if not.
     */
    public function addConjunctIfArrayKeysExist(string $sql, array $keys, array $args): bool
    {
        $placeholder_values = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $args)) {
                return false;
            } else {
                $placeholder_values[$key] = $args[$key];
            }
        }

        $this->addConjunct(new Conjunct($sql, $placeholder_values));
        return true;
    }

    /**
     * @return string An SQL WHERE clause, that is, each conjunct separated by AND.
     */
    public function buildConditionalClause(): string
    {
        $sql_strings = array_map([Conjunct::class, 'getSql'], $this->conjuncts);
        return implode(' AND ', $sql_strings);
    }

    /**
     * @return array An array of the format [':key' => value] which can be directly used for queries.
     */
    public function buildPlaceholderValues(): array
    {
        $all_placeholders = [];
        foreach ($this->conjuncts as $conjunct) {
            array_merge($all_placeholders, $conjunct->getValues());
        }
        $finalized_placeholders = [];
        foreach ($all_placeholders as $key => $value) {
            $finalized_placeholders[':' . $key] = $value;
        }
        return $finalized_placeholders;
    }
}