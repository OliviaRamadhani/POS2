<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Buat data dummy untuk tabel reservations
        Reservation::create([
            'name' => 'John Doe',
            'phone' => '08123456789',
            'date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'guests' => 5,
            'menus' => '',
            'total_price' => 0
        ]);

        Reservation::create([
            'name' => 'Jane Smith',
            'phone' => '08198765432',
            'date' => Carbon::now()->addDay()->format('Y-m-d'),
            'start_time' => '19:00',
            'end_time' => '21:00',
            'guests' => 3,
            'menus' => '',
            'total_price' => 0
        ]);

        Reservation::create([
            'name' => 'Michael Johnson',
            'phone' => '08122334455',
            'date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'guests' => 2,
            'menus' => '',
            'total_price' => 0
        ]);
    }
}