id<?php

use Illuminate\Database\Seeder;
use App\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create ([
            'status' =>'Only Me',
            'description' => 'Unpublished only creator can see it'
        ]);
        Status::create ([
            'status' =>'Restricted',
            'description' =>'Restricted by community'
        ]);
        Status::create ([
            'status' => 'Public',
            'description' => 'Everyone can see'
        ]);
        Status::create([
            'status'=>'Draft',
            'description' =>'Draft and not to be published'
        ]);
    }
}
