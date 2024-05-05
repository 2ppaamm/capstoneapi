<?php

use Illuminate\Database\Seeder;

use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()

    {
        Model::unguard();

           $this->call(UsersSeeder::class);
           $this->call(StatusSeeder::class);
           $this->call(TypeSeeder::class);
           $this->call(DifficultySeeder::class);
           $this->call(RoleSeeder::class);
           $this->call(FieldSeeder::class);
            $this->call(UnitSeeder::class);
           $this->call(ResultTypeSeeder::class);
           $this->call(FrameworkSeeder::class);
           $this->call(HouseSkillSeeder::class);
            $this->call(QuizSeeder::class);

        Model::reguard();
    }
}
