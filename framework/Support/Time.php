<?php

declare(strict_types=1);

namespace Framework\Support;

use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use Exception;

class Time
{
    private DateTimeImmutable $dateTime;

    public function __construct(string|DateTimeImmutable $time = 'now', string|DateTimeZone|null $timezone = null)
    {
        $tz = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone ?? date_default_timezone_get());
        
        if ($time instanceof DateTimeImmutable) {
            $this->dateTime = $time->setTimezone($tz);
        } else {
            $this->dateTime = new DateTimeImmutable($time, $tz);
        }
    }

    public static function now(string|DateTimeZone|null $timezone = null): self
    {
        return new self('now', $timezone);
    }

    public static function parse(string $time, string|DateTimeZone|null $timezone = null): self
    {
        return new self($time, $timezone);
    }

    public static function createFromTimestamp(int $timestamp, string|DateTimeZone|null $timezone = null): self
    {
        return new self("@{$timestamp}", $timezone);
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }

    /**
     * 人性化格式化 (例如：1小时前)
     */
    public function diffForHumans(): string
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($this->dateTime);

        if ($diff->y > 0) return $diff->y . ' 年' . ($this->dateTime > $now ? '后' : '前');
        if ($diff->m > 0) return $diff->m . ' 个月' . ($this->dateTime > $now ? '后' : '前');
        if ($diff->d > 0) return $diff->d . ' 天' . ($this->dateTime > $now ? '后' : '前');
        if ($diff->h > 0) return $diff->h . ' 小时' . ($this->dateTime > $now ? '后' : '前');
        if ($diff->i > 0) return $diff->i . ' 分钟' . ($this->dateTime > $now ? '后' : '前');
        
        return '刚刚';
    }

    public function add(string $duration): self
    {
        return new self($this->dateTime->add(new DateInterval($duration)));
    }

    public function sub(string $duration): self
    {
        return new self($this->dateTime->sub(new DateInterval($duration)));
    }

    public function addDays(int $days): self
    {
        return new self($this->dateTime->modify("+{$days} days"));
    }

    public function subDays(int $days): self
    {
        return new self($this->dateTime->modify("-{$days} days"));
    }

    public function startOfDay(): self
    {
        return new self($this->dateTime->setTime(0, 0, 0));
    }

    public function endOfDay(): self
    {
        return new self($this->dateTime->setTime(23, 59, 59));
    }

    public function toDateTimeString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    public function getTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    public function __toString(): string
    {
        return $this->toDateTimeString();
    }
}
