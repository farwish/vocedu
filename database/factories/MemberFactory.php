<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class MemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Member::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
        ];
    }

    public function defaultMobileMember()
    {
        return [
            [
                'mobile' => '13185826384',
                'password' => '123456',
            ],
            [
                'mobile' => '13720496099',
                'password' => '123456',
            ],
        ];
    }
}
