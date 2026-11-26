<?php

namespace App\Services\Analytics;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Throwable;

/**
 * Visit figures from Google Analytics, for the admin pages that show them.
 *
 * Google is optional to the admin: a missing key file, a wrong property id or
 * Google being down must leave an empty chart, not take the whole dashboard
 * down with it. After the first failure the rest of the request stops asking,
 * so one outage costs one timeout rather than four.
 */
class GoogleTraffic
{
    private bool $unavailable = false;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topBrowsers(int $days): Collection
    {
        return $this->fetch(fn () => Analytics::fetchTopBrowsers(Period::days($days)));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topReferrers(int $days, int $limit): Collection
    {
        return $this->fetch(fn () => Analytics::fetchTopReferrers(Period::days($days), $limit));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function topOperatingSystems(int $days): Collection
    {
        return $this->fetch(fn () => Analytics::fetchTopOperatingSystems(Period::days($days)));
    }

    /**
     * Visitors and page views per day, oldest first, shaped for the chart.
     *
     * @return array{date: Collection<int, string>, activeUsers: Collection<int, mixed>, screenPageViews: Collection<int, mixed>}
     */
    public function dailyVisits(int $days, string $dateFormat): array
    {
        $rows = $this->fetch(fn () => Analytics::fetchTotalVisitorsAndPageViews(Period::days($days)))->sortBy('date');

        return [
            'date' => $rows->pluck('date')->map(fn ($date) => $date->format($dateFormat))->values(),
            'activeUsers' => $rows->pluck('activeUsers')->values(),
            'screenPageViews' => $rows->pluck('screenPageViews')->values(),
        ];
    }

    public function unavailable(): bool
    {
        return $this->unavailable;
    }

    /**
     * @param  Closure(): Collection<int, array<string, mixed>>  $report
     * @return Collection<int, array<string, mixed>>
     */
    private function fetch(Closure $report): Collection
    {
        if ($this->unavailable) {
            return collect();
        }

        try {
            return $report();
        } catch (Throwable $e) {
            $this->unavailable = true;
            Log::warning('Google Analytics unavailable', ['exception' => $e::class, 'message' => $e->getMessage()]);

            return collect();
        }
    }
}
