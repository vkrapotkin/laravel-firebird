<?php

namespace Firebird\Query;

use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * Add a from stored procedure clause to the query builder.
     *
     * @param string $procedure
     * @param array $values
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
}
