<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        $this->call([
            UserSeeder::class,

            PatternSeeder::class,

            CategorySeeder::class,
            ChapterSeeder::class,

            PackageSeeder::class,
            SuiteSeeder::class,
            PaperSeeder::class,
            TabSeeder::class,

            VideoSeeder::class,
            ArticleSeeder::class,
            ExamSeeder::class,

            MemberSeeder::class,

            AppMenuSeeder::class,
        ]);
    }
}
