<?php

namespace App\Service;

use App\Repository\AppConfigRepository;

class SimulatedClockService
{
    private const KEY = 'simulated_now';

    public function __construct(private AppConfigRepository $configRepo) {}

    /**
     * Returns the simulated time if set, otherwise real now.
     */
    public function now(): \DateTimeImmutable
    {
        $stored = $this->configRepo->get(self::KEY);
        if ($stored) {
            return new \DateTimeImmutable($stored);
        }
        return new \DateTimeImmutable();
    }

    /**
     * Set the simulated time. Pass null to revert to real time.
     */
    public function set(?\DateTimeImmutable $time): void
    {
        $this->configRepo->set(self::KEY, $time?->format(\DateTime::ATOM));
    }

    /**
     * Advance the simulated time by a DateInterval string e.g. "PT4H", "P1D".
     * If no simulated time is set, starts from real now.
     */
    public function advance(string $interval): \DateTimeImmutable
    {
        $current = $this->now();
        $new = $current->add(new \DateInterval($interval));
        $this->set($new);
        return $new;
    }

    public function isSimulated(): bool
    {
        return $this->configRepo->get(self::KEY) !== null;
    }

    public function reset(): void
    {
        $this->set(null);
    }
}
