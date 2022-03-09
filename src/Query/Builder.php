<?php

namespace HarryGulliford\Firebird\Query;

use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return parent::count() > 0;
    }

    /**
     * Add a from stored procedure clause to the query builder.
     *
     * @param  string  $procedure
     * @param  array  $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function fromProcedure(string $procedure, array $values = [])
    {
        $compiledProcedure = $this->grammar->compileProcedure($this, $procedure, $values);

        // Remove any expressions from the values array, as they will have
        // already been evaluated by the grammar's parameterize() function.
        $values = array_filter($values, function ($value) {
            return ! $this->grammar->isExpression($value);
        });

        $this->fromRaw($compiledProcedure, array_values($values));

        return $this;
    }

    /**
     * Run a pagination count query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function runPaginationCountQuery($columns = ['*'])
    {
        $results = parent::runPaginationCountQuery($columns);

        // Convert the resultset keys to lower, so they can be handled correctly
        // by the paginator.
        if ($this->getConnection()->getPdo()->getAttribute(\PDO::ATTR_CASE) !== \PDO::CASE_LOWER) {
            foreach ($results as $key => $value) {
                $results[$key] = (object) array_change_key_case((array) $value, CASE_LOWER);
            }
        }

        return $results;
    }
}
