<?php

namespace Allocine\Twigcs\RegEngine\Checker;

class RuleError
{
    /**
     * @var int
     */
    private $column;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var Regex
     */
    private $source;

    public function __construct(string $reason, int $column, Regex $source)
    {
        $this->column = $column;
        $this->reason = $reason;
        $this->source = $source;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getSource(): Regex
    {
        return $this->source;
    }
}