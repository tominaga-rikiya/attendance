<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー
        User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        //スタッフデータ
        $staffs = [
            [
                'name' => '西 怜奈',
                'email' => 'reina@coachtech.com',
                'role' => 'user',
            ],
            [
                'name' => '山田 太郎',
                'email' => 'taro@coachtech.com',
                'role' => 'user',
            ],
            [
                'name' => '増田 一世',
                'email' => 'issei@coachtech.com',
                'role' => 'user',
            ],
            [
                'name' => '山本 敬吾',
                'email' => 'keikichi@coachtech.com',
                'role' => 'user',
            ],
            [
                'name' => '秋田 朋美',
                'email' => 'tomomi@coachtech.com',
                'role' => 'user',
            ],
            [
                'name' => '中西 春夫',
                'email' => 'norio@coachtech.com',
                'role' => 'user',
            ],
        ];

        foreach ($staffs as $staff) {
            User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password'), // 全員共通パスワード
                'role' => $staff['role'],
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('ユーザーとスタッフデータを生成しました！');
    }
}
