<?php

namespace ostark\CraftMockery;

use ostark\CraftMockery\Exceptions\MissingExpectationData;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Util\Printer;

class QueryCollector
{
    public array $calls = [];
    protected ExpectationResolver $expectation;

    public function __construct(ExpectationResolver $expectation)
    {
        $this->expectation = $expectation;
    }

    public function __call(string $name, array $arguments): self
    {
        $this->calls[$name] = implode(',', $arguments);
        return $this;
    }

    public function where(array $criteria = []): self
    {
        $this->calls['where'] = str_replace('"', '', (string)json_encode($criteria));
        return $this;
    }

    public function id($id_or_ids): self
    {
        $this->calls['id'] = (string)json_encode((array)$id_or_ids);
        return $this;
    }

    public function all()
    {
        return $this->getResult('all');
    }

    public function one()
    {
        return $this->getResult('one');
    }

    public function count(): int
    {
        return $this->getResult('count');
    }

    public function exists(): bool
    {
        return $this->getResult('exists');
    }

    public function column()
    {
        return $this->getResult('column');
    }

    public function scalar()
    {
        return $this->getResult('scalar');
    }

    public function sum()
    {
        return $this->getResult('sum');
    }

    public function average()
    {
        return $this->getResult('average');
    }

    public function min()
    {
        return $this->getResult('min');
    }


    /**
     * @param string $method
     *
     * @return mixed
     */
    private function getResult($method = 'all')
    {
        $this->expectation->addKey($this->queryKey($method));
        $this->expectation->addKey($method . '()');

        try {
            return $this->expectation->resolve();
        } catch (MissingExpectationData $e) {
            // phpunit
            if (class_exists(IncompleteTestError::class)) {
                throw new IncompleteTestError($e->getMessage());
            }
            throw $e;
        }
    }

    private function queryKey(string $execMethod): string
    {
        $parts = [];

        foreach ($this->calls as $callMethod => $callArgs) {
            $parts[] = sprintf('%s(%s)', $callMethod, (string)$callArgs);
        }

        $parts[] = $execMethod . '()';

        return implode('.', $parts);
    }
}
