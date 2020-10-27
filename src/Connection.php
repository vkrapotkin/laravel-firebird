<?php

namespace Firebird;

use Exception;
use Firebird\Query\Builder as FirebirdQueryBuilder;
use Firebird\Query\Grammars\FirebirdGrammar as FirebirdQueryGrammar;
use Firebird\Schema\Builder as FirebirdSchemaBuilder;
use Firebird\Schema\Grammars\FirebirdGrammar as FirebirdSchemaGrammar;
use Firebird\Support\Version;
use Illuminate\Database\Connection as DatabaseConnection;

class Connection extends DatabaseConnection
{
    /**
     * Get the default query grammar instance.
     *
     * @return \Firebird\Query\Grammars\FirebirdGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new FirebirdQueryGrammar(
            $this->getFirebirdVersion()
        );
    }

    /**
     * Get a schema builder instance for this connection.
     *
     * @return \Firebird\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new FirebirdSchemaBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Firebird\Schema\Grammars\FirebirdGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new FirebirdSchemaGrammar);
    }

    /**
     * Get query builder.
     *
     * @return \Firebird\Query\Builder
     */
    protected function getQueryBuilder()
    {
        return new FirebirdQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Firebird\Query\Builder
     */
    public function query()
    {
        return $this->getQueryBuilder();
    }

    /**
     * Execute a stored procedure.
     *
     * @param string $procedure
     * @param array $values
     *
     * @return \Illuminate\Support\Collection
     */
    public function executeProcedure($procedure, array $values = [])
    {
        return $this->query()->fromProcedure($procedure, $values)->get();
    }

    protected function getFirebirdVersion()
    {
        if (! $this->config['version']) {
            return Version::FIREBIRD_25;
        }

        // Check the user has provided a supported version.
        if (! in_array($this->config['version'], Version::SUPPORTED_VERSIONS)) {
            throw new Exception('The Firebird version provided is not supported.');
        }

        return $this->config['version'];
    }
}
