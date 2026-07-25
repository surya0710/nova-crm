<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TaskAttachment> */
class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        $name = fake()->word().'.pdf';

        return [
            'organization_id' => Organization::factory(),
            'task_id' => Task::factory(),
            'file_name' => $name,
            'file_path' => 'task-attachments/1/'.$name,
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(1024, 500000),
            'uploaded_by' => User::factory(),
        ];
    }
}
