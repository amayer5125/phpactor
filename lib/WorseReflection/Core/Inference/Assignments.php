<?php

namespace Phpactor\WorseReflection\Core\Inference;

use Countable;
use IteratorAggregate;
use RuntimeException;
use ArrayIterator;

/**
 * @implements IteratorAggregate<array-key,Variable>
 */
abstract class Assignments implements Countable, IteratorAggregate
{
    /**
     * @var array<int, Variable>-
     */
    private array $variables = [];

    /**
     * @param array<int,Variable> $variables
     */
    final public function __construct(array $variables)
    {
        $this->variables = array_map(function (Variable $v) {
            return $v;
        }, $variables);
        $this->sort();
    }


    public function __toString(): string
    {
        return implode("\n", array_map(function (Variable $variable) {
            return sprintf(
                '%s:%s: %s',
                $variable->name(),
                $variable->offset(),
                $variable->type()->__toString()
            );
        }, $this->variables));
    }

    public function add(Variable $variable): void
    {
        $this->variables[] = $variable;
        $this->sort();
    }

    /**
     * @return self
     */
    public function byName(string $name): Assignments
    {
        $name = ltrim($name, '$');
        return new static(array_filter($this->variables, function (Variable $v) use ($name) {
            return $v->name() === $name;
        }));
    }

    public function lessThanOrEqualTo(int $offset): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) use ($offset) {
            return $v->offset() <= $offset;
        }));
    }

    public function lessThan(int $offset): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) use ($offset) {
            return $v->offset() < $offset;
        }));
    }

    public function greaterThan(int $offset): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) use ($offset) {
            return $v->offset() > $offset;
        }));
    }

    public function greaterThanOrEqualTo(int $offset): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) use ($offset) {
            return $v->offset() >= $offset;
        }));
    }

    public function first(): Variable
    {
        $first = reset($this->variables);

        if (!$first) {
            throw new RuntimeException(
                'Variable collection is empty'
            );
        }

        return $first;
    }

    public function atIndex(int $index): Variable
    {
        $variables = array_values($this->variables);
        if (!isset($variables[$index])) {
            throw new RuntimeException(sprintf(
                'No variable at index "%s"',
                $index
            ));
        }

        return $variables[$index];
    }

    public function last(): Variable
    {
        $last = end($this->variables);

        if (!$last) {
            throw new RuntimeException(
                'Cannot get last, variable collection is empty'
            );
        }

        return $last;
    }
    
    public function count(): int
    {
        return count($this->variables);
    }

    /**
     * @return ArrayIterator<array-key,Variable>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->variables));
    }

    public function merge(Assignments $variables): Assignments
    {
        foreach ($variables->variables as $offset => $variable) {
            $this->variables[$offset] = $variable;
        }
        $this->sort();

        return $this;
    }

    public function replace(Variable $existing, Variable $replacement): void
    {
        foreach ($this->variables as $offset => $variable) {
            if ($variable !== $existing) {
                continue;
            }
            $this->variables[$offset] = $replacement;
        }
    }

    public function equalTo(int $offset): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) use ($offset) {
            return $v->offset() === $offset;
        }));
    }

    public function assignmentsOnly(): Assignments
    {
        return new static(array_filter($this->variables, function (Variable $v) {
            return $v->wasAssigned();
        }));
    }

    public function lastOrNull(): ?Variable
    {
        $last = end($this->variables);

        if (!$last) {
            return null;
        }

        return $last;
    }

    private function sort(): void
    {
        usort($this->variables, function (Variable $one, Variable $two) {
            return $one->offset() <=> $two->offset();
        });
    }
}
