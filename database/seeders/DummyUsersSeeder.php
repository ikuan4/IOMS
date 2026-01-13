<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Str;

class DummyUsersSeeder extends Seeder
{
    public function run()
    {
        $roles = Role::where('is_active', 1)->where('name', '!=', 'Developer')->get();

        if ($roles->isEmpty()) {
            $this->call([BranchSeeder::class, DummyRolesSeeder::class]);
            $roles = Role::where('is_active', 1)->where('name', '!=', 'Developer')->get();
        }

        // Indian first names
        $firstNames = [
            'Rajesh', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anjali', 'Rahul', 'Pooja', 'Arjun', 'Kavita',
            'Sanjay', 'Neha', 'Aditya', 'Riya', 'Karthik', 'Divya', 'Rohan', 'Ananya', 'Suresh', 'Meera',
            'Vishal', 'Shreya', 'Nikhil', 'Tanvi', 'Deepak', 'Ishita', 'Manish', 'Swati', 'Arun', 'Nisha',
            'Prakash', 'Aditi', 'Ramesh', 'Shweta', 'Ashok', 'Pallavi', 'Sandeep', 'Aarti', 'Vivek', 'Preeti',
            'Ravi', 'Shalini', 'Manoj', 'Kriti', 'Gaurav', 'Priyanka', 'Akash', 'Madhuri', 'Abhishek', 'Sakshi',
            'Sumit', 'Rachna', 'Naveen', 'Surbhi', 'Ajay', 'Payal', 'Mohit', 'Simran', 'Pankaj', 'Rani',
            'Dinesh', 'Lakshmi', 'Yogesh', 'Manju', 'Harsh', 'Bhavna', 'Siddharth', 'Geeta', 'Alok', 'Smita',
            'Varun', 'Sunita', 'Chandan', 'Rita', 'Ankit', 'Usha', 'Jay', 'Rekha', 'Akshay', 'Sapna'
        ];

        // Indian last names
        $lastNames = [
            'Sharma', 'Verma', 'Singh', 'Kumar', 'Patel', 'Gupta', 'Reddy', 'Agarwal', 'Joshi', 'Mehta',
            'Nair', 'Shah', 'Rao', 'Iyer', 'Pillai', 'Jain', 'Desai', 'Kulkarni', 'Pandey', 'Mishra',
            'Sinha', 'Chopra', 'Malhotra', 'Kapoor', 'Khanna', 'Banerjee', 'Chatterjee', 'Das', 'Ghosh', 'Roy',
            'Bose', 'Sen', 'Dutta', 'Mukherjee', 'Patil', 'Yadav', 'Thakur', 'Bhatt', 'Saxena', 'Tiwari',
            'Chauhan', 'Rajput', 'Bhat', 'Naidu', 'Menon', 'Krishna', 'Murthy', 'Prasad', 'Varma', 'Rana'
        ];

        // For each role, create 2-10 users
        foreach ($roles as $role) {
            $numberOfUsers = rand(2, 10);

            for ($i = 0; $i < $numberOfUsers; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = $firstName . ' ' . $lastName;

                // Generate email using name (make it fake domain)
                $emailSlug = Str::slug($fullName);
                $email = $emailSlug . rand(10, 99) . '@testmail.local';

                // Generate Indian mobile number (10 digits starting with 6-9)
                $mobile = $this->generateIndianMobile();

                // Check if user already exists
                if (User::where('email', $email)->exists()) {
                    $email = $emailSlug . rand(100, 999) . '@testmail.local';
                }

                if (User::where('mobile', $mobile)->exists()) {
                    $mobile = $this->generateIndianMobile();
                }

                $user = User::create([
                    'name' => $fullName,
                    'email' => $email,
                    'password' => 'password',
                    'mobile' => $mobile,
                    'active' => 1,
                    'branch_id' => $role->branch_id,
                    'role_id' => $role->id,
                ]);

                // Ensure pivot mapping for Spatie's model_has_roles table
                try {
                    $role->users()->attach($user->id);
                } catch (\Exception $e) {
                    // ignore duplicates
                }
            }
        }
    }

    /**
     * Generate a valid Indian mobile number (10 digits starting with 6-9)
     */
    private function generateIndianMobile(): string
    {
        $firstDigit = rand(6, 9);
        $remainingDigits = '';
        for ($i = 0; $i < 9; $i++) {
            $remainingDigits .= rand(0, 9);
        }
        return $firstDigit . $remainingDigits;
    }
}
