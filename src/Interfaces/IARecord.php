<?php

namespace Duckster\Analyzer\Interfaces;

use Duckster\Analyzer\Structures\RecordRelation;

interface IARecord
{
    /**
     * Open a record
     *
     * @param string $name
     * @param bool $isShared
     * @return IARecord
     */
    public static function open(string $name, bool $isShared = false): IARecord;

    /**
     * Start recording
     *
     * @return IARecord
     */
    public function start(): IARecord;

    /**
     * Stop recording.
     *
     * @return IARecord|null
     */
    public function stop(): IARecord;

    /**
     * Get the preparation time to start and stop this Record
     *
     * @return float
     */
    public function prepTime(): float;

    /**
     * Get the diff between startTime and stopTime
     *
     * @return float
     */
    public function diffTime(): float;

    /**
     * Get the preparation memory to start and stop this Record
     *
     * @return int
     */
    public function prepMem(): int;

    /**
     * Get the diff between startEmMem and stopEmMem
     *
     * @return int
     */
    public function diffMem(): int;

    /**
     * Set pre snapshot and return
     *
     * @param array $snapshot
     * @return IARecord
     */
    public function setPreStartSnapshot(array $snapshot): IARecord;

    /**
     * Set post snapshot and return
     *
     * @param array $snapshot
     * @return IARecord
     */
    public function setPreStopSnapshot(array $snapshot): IARecord;

    /**
     * Get Record's UID
     *
     * @return string
     */
    public function getUID(): string;

    /**
     * Get Record's name
     *
     * @return string
     */
    public function getName(): string;


    /**
     * Get startTime timestamp
     *
     * @return float
     */
    public function getStartTime(): float;


    /**
     * Get stopTime timestamp
     *
     * @return float
     */
    public function getStopTime(): float;

    /**
     * Get start emalloc() memory usage
     *
     * @return int
     */
    public function getStartMem(): int;


    /**
     * Get stop emalloc() memory usage
     *
     * @return int
     */
    public function getStopMem(): int;

    /**
     * Get relations
     *
     * @return RecordRelation[]
     */
    public function getRelations(): array;

    /**
     * Check if Record is started
     *
     * @return bool
     */
    public function isStarted(): bool;


    /**
     * Check if Record is stopped
     *
     * @return bool
     */
    public function isStopped(): bool;


    /**
     * Check if Record is shared
     *
     * @return bool
     */
    public function isShared(): bool;
}