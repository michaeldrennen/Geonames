<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use MichaelDrennen\Geonames\BaseTrait;

class FeatureClassSeeder extends Seeder {
    use BaseTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('users')->insert(['name'     => str_random(10),
                                    'email'    => str_random(10) . '@gmail.com',
                                    'password' => bcrypt('secret'),]);
    }

    protected function downloadFeatureClassFile() {
        $this->setStorage();
    }
}