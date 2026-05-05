<?php

declare(strict_types=1);

namespace Framework\Events;

class ScheduleRegisteringEvent extends Event
{
    private array $schedule;

    public function __construct(array $schedule)
    {
        parent::__construct('schedules.registering');
        $this->schedule = $schedule;
    }

    public function getSchedule(): array
    {
        return $this->schedule;
    }
}