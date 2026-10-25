<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo accounts (change these passwords before any real deployment):
     *   Admin   : admin@gesturespeak.com  / password
     *   Parent  : parent@gesturespeak.com / password
     *   Teacher : teacher@gesturespeak.com / password
     *   Child   : Student Code DEMO-1234  / PIN 1234  (linked to BOTH parent and teacher)
     */
    public function run(): void
    {
        $this->call(LearningContentSeeder::class);

        User::updateOrCreate(['email' => 'admin@gesturespeak.com'], [
            'name' => 'GestureSpeak Admin', 'password' => 'password', 'role' => User::ROLE_ADMIN,
        ]);

        $parent = User::updateOrCreate(['email' => 'parent@gesturespeak.com'], [
            'name' => 'Puan Siti', 'password' => 'password', 'role' => User::ROLE_PARENT,
        ]);

        $teacher = User::updateOrCreate(['email' => 'teacher@gesturespeak.com'], [
            'name' => 'Cikgu Tan', 'password' => 'password', 'role' => User::ROLE_TEACHER,
        ]);

        $child = Student::updateOrCreate(['student_code' => 'DEMO-1234'], [
            'name' => 'Adam', 'age' => 7, 'pin' => '1234', 'avatar' => 'lion', 'created_by' => $parent->id,
        ]);

        // Home-school collaboration: the same child is linked to a parent AND a teacher
        $child->guardians()->syncWithoutDetaching([
            $parent->id => ['relationship' => User::ROLE_PARENT],
            $teacher->id => ['relationship' => User::ROLE_TEACHER],
        ]);
    }
}
