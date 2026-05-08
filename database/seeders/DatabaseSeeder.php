<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ColorSeeder::class);
        $this->call(SizeSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ImageSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(ProductQuantitySeeder::class);
        $this->call(CateNewsSeeder::class);
        $this->call(TagsSeeder::class);
        $this->call(NewsSeeder::class);
        $this->call(NewsByTagsSeeder::class);
        $this->call(CouponSeeder::class);
        // $this->call(FaqSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(ContactSeeder::class);
        $this->call(FaqSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(VisitorSeeder::class);
        $this->call(CateSlideSeeder::class);
        $this->call(PromotionSeeder::class);
    }
}
