<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $treeData = Category::factory()->cateTestTreeData();

        foreach ($treeData as $treeItem) {
            Category::create($treeItem);
        }

        // 当前不给科目绑题型，默认所有可用

        // 单独给 4，6 的科目分类绑题型
        // $one = Category::query()->find(4);
        // $one->patterns()->sync([1, 2, 3, 4, 5, 6]);
        //
        // $two = Category::query()->find(6);
        // $two->patterns()->sync([1, 2, 3]);
    }
}
