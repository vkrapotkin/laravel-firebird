<?php

namespace Firebird\Query;

class Builder extends \Illuminate\Database\Query\Builder
{
    /**
     * Execute stored procedure.
     *
     * @param string $procedure
     * @param array $values
     */
    public function executeProcedure($procedure, array $values = null)
    {
        if (! $values) {
            $values = [];
        }

        $bindings = array_values($values);

        $sql = $this->grammar->compileExecProcedure($this, $procedure, $values);

        $this->connection->statement($sql, $this->cleanBindings($bindings));
    }
}
