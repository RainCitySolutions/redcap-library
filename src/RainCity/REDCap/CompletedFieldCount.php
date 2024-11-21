<?php
declare(strict_types = 1);
namespace RainCity\REDCap;

class CompletedFieldCount
{
    private int $completedCnt;
    private int $requiredCnt;
    private ?string $firstIncompleteInstrument;
    private ?string $firstIncompleteField;

    public function __construct(
        int $completedCnt = 0,
        int $requiredCnt= 0,
        string $firstIncompleteInstrument = null,
        string $firstIncompleteField = null
        )
    {
        $this->completedCnt = $completedCnt;
        $this->requiredCnt = $requiredCnt;
        $this->firstIncompleteInstrument = $firstIncompleteInstrument;
        $this->firstIncompleteField = $firstIncompleteField;
    }

    public function setCounts(int $completedCnt, int $requiredCnt): void
    {
        $this->completedCnt = $completedCnt;
        $this->requiredCnt = $requiredCnt;
    }

    public function setFirstIncomplete(string $instrument, string $field): void
    {
        if (!isset($this->firstIncompleteField)) {
            $this->firstIncompleteInstrument = $instrument;
            $this->firstIncompleteField = $field;
        }
    }

    public function merge(CompletedFieldCount $inCnt): void
    {
        $this->completedCnt += $inCnt->completedCnt;
        $this->requiredCnt += $inCnt->requiredCnt;

        if (!isset($this->firstIncompleteField)) {
            $this->firstIncompleteInstrument = $inCnt->firstIncompleteInstrument;
            $this->firstIncompleteField = $inCnt->firstIncompleteField;
        }
    }

    public function getCompletedCount(): int
    {
        return $this->completedCnt;
    }

    public function getRequiredCount(): int
    {
        return $this->requiredCnt;
    }

    public function getFirstIncompleteInstrument(): ?string
    {
        return $this->firstIncompleteInstrument;
    }

    public function getFirstIncompleteField(): ?string
    {
        return $this->firstIncompleteField;
    }

    public function hasIncompleteField(): bool
    {
        return isset($this->firstIncompleteField);
    }
}
