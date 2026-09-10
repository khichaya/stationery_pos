<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class RunScheduleOnRequest
{
    /**
     * كل طلب يمر على الموقع، نتحقق: هل مرّت دقيقة منذ آخر فحص للجدولة؟
     * لو نعم، ننفذ backup:check-schedule فوراً (بدون أي عملية خلفية أو خدمة خارجية).
     * هذا بديل كامل لـ cron / Task Scheduler / NSSM.
     */
    public function handle(Request $request, Closure $next)
    {
        $lockKey = 'schedule_last_run_check';

        if (!Cache::has($lockKey)) {
            Cache::put($lockKey, now()->todateTimeString(), 60); // قفل لمدة 60 ثانية

            try {
                Artisan::call('backup:check-schedule');
            } catch (\Throwable $e) {
                \Log::error('Schedule check failed: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
