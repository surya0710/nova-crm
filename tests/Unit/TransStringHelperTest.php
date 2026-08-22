<?php

namespace Tests\Unit;

use Tests\TestCase;

class TransStringHelperTest extends TestCase
{
    public function test_attendance_word_does_not_return_the_lang_file_array(): void
    {
        $this->assertSame('Attendance', trans_string('Attendance'));
        $this->assertSame('Attendance', trans_string('attendance.label'));
    }
}
