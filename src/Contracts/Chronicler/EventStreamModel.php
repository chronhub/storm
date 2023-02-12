<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface EventStreamModel
{
    /**
     * Get real stream name
     *
     * @return string
     */
    public function realStreamName(): string;

    /**
     * Get table name if exists
     *
     * @return string|null
     */
    public function tableName(): ?string;

    /**s
     * Get category name if exists
     *
     * @return string|null
     */
    public function category(): ?string;
}
