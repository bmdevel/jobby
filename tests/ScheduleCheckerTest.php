<?php

namespace Jobby\Tests;

use Jobby\ScheduleChecker;
use PHPUnit\Framework\TestCase;

class ScheduleCheckerTest extends TestCase
{
    /**
     * @var ScheduleChecker
     */
    private $scheduleChecker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduleChecker = new ScheduleChecker();
    }

    /**
     * @return void
     */
    public function test_it_can_detect_a_due_job_from_a_datetime_string()
    {
        $this->assertTrue($this->scheduleChecker->isDue(date('Y-m-d H:i:s')));
    }

    /**
     * @return void
     */
    public function test_it_can_detect_a_non_due_job_from_a_datetime_string()
    {
        $this->assertFalse($this->scheduleChecker->isDue(date('Y-m-d H:i:s', strtotime('tomorrow'))));
    }

    /**
     * @return void
     */
    public function test_it_can_detect_a_due_job_from_a_cron_expression()
    {
        $this->assertTrue($this->scheduleChecker->isDue("* * * * *"));
    }

    /**
     * @return void
     */
    public function test_it_can_detect_a_non_due_job_from_a_cron_expression()
    {
        $hour = date("H", strtotime('+1 hour'));
        $this->assertFalse($this->scheduleChecker->isDue("* {$hour} * * *"));
    }

    /**
     * @return void
     */
    public function test_it_can_use_a_closure_to_detect_a_due_job()
    {
        $this->assertTrue(
            $this->scheduleChecker->isDue(function() {
                return true;
            })
        );
    }

    /**
     * @return void
     */
    public function test_it_can_use_a_closure_to_detect_a_non_due_job()
    {
        $this->assertFalse(
            $this->scheduleChecker->isDue(function() {
                return false;
            })
        );
    }
}
