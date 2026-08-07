<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = Tag::all();

        // FactoryのルールでContactを20件作成
        $contacts = Contact::factory()
            ->count(20)
            ->create();

        foreach ($contacts as $contact) {
            // タグをランダムに1〜3件選び、紐付けに必要なidだけ抜き出す
            $tagIds = $tags
                ->random(random_int(1, 3))
                ->pluck('id');

            // contact_tagに関連を登録
            $contact->tags()->attach($tagIds);
        }
    }
}
