<?php


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
        $this->call(UserSeeder::class);
        $this->call(ContaSeeder::class);
        $this->call(MaterialSeeder::class);
        $this->call(PostagemSeeder::class);
        $this->call(AtlaSeeder::class);
    }
}
