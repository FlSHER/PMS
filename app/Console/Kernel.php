<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\PackageCreateCommand::class,
        Commands\PackageHandlerCommand::class,
        Commands\CalculateStaffBasePoint::class,
        Commands\CalculateStaffPoint::class,
        Commands\PointTargetCommand::class,
        Commands\AttendanceConvert::class,
        Commands\ConvertOneTimePoint::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')->hourly();
        $schedule->command('pms:attendance-convert-point')->dailyAt('4:00');

        // Monthly statistics of employees' points
        $schedule->command('pms:calculate-staff-point')->dailyAt('4:40');
        $schedule->command('pms:calculate-staff-point')->everyThirtyMinutes()->between('17:30', '22:00');
        // Monthly statistics of employees' base points
        $schedule->command('pms:calculate-staff-basepoint')->monthlyOn(1, '2:10');
        $schedule->command('command:pointTarget')->monthlyOn(1, '2:00');

        $schedule->command('pms:one-time-point-convert')->monthlyOn(1, '1:00');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the Artisan application instance.
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        $artisan = parent::getArtisan();
        $artisan->setName('PMS ( For Larvel )');

        return $artisan;
    }
}
