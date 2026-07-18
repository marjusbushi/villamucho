<?php

namespace Tests\Feature;

use App\Models\PosShift;
use App\Models\User;
use App\Services\Reporting\ReportingPeriod;
use App\Services\Reporting\ShiftReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_sealed_shift_totals_and_flags_inconsistent_snapshots(): void
    {
        $user = User::factory()->create();
        $this->shift($user, '2026-07-10 08:00:00', '2026-07-10 16:00:00', 50, 100, 60, 20, 150, 148, -2, 180);
        $this->shift($user, '2026-07-11 08:00:00', '2026-07-11 16:00:00', 20, 80, 40, 10, 100, 105, 5, 999);
        $this->shift($user, '2026-07-12 08:00:00', null, 0, 20, 0, 0, 20, 20, 0, 20, 'open');
        $this->shift($user, '2026-06-01 08:00:00', '2026-06-01 16:00:00', 0, 30, 0, 0, 30, 30, 0, 30);

        $report = app(ShiftReportService::class)
            ->summary(new ReportingPeriod('2026-07-10', '2026-07-11'));

        $this->assertCount(2, $report['shifts']);
        $this->assertFalse($report['shifts'][0]['is_consistent']);
        $this->assertTrue($report['shifts'][1]['is_consistent']);
        $this->assertSame(180.0, $report['totals']['cash']);
        $this->assertSame(100.0, $report['totals']['card']);
        $this->assertSame(30.0, $report['totals']['room_charge']);
        $this->assertSame(1179.0, $report['totals']['total']);
        $this->assertSame(3.0, $report['totals']['over_short']);
        $this->assertSame(1, $report['totals']['inconsistent_count']);
    }

    private function shift(
        User $user,
        string $openedAt,
        ?string $closedAt,
        float $opening,
        float $cash,
        float $card,
        float $room,
        float $expected,
        float $counted,
        float $overShort,
        float $total,
        string $status = 'closed',
    ): PosShift {
        return PosShift::create([
            'user_id' => $user->id,
            'status' => $status,
            'opening_float' => $opening,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'over_short' => $overShort,
            'cash_sales' => $cash,
            'card_sales' => $card,
            'room_charge_sales' => $room,
            'total_sales' => $total,
        ]);
    }
}
