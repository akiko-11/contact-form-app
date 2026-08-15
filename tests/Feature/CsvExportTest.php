<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがCSVをダウンロードできる
    public function test_authenticated_user_can_download_csv(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->get('/contacts/export');

        // Assert
        $response->assertOk();
        $response->assertDownload('contacts.csv');
    }

    // キーワードに一致するお問い合わせだけがCSVに出力される
    public function test_csv_can_be_filtered_by_keyword(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'email' => 'sato@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get('/contacts/export?keyword=山田');

        // CSVの中身を文字列として取得
        $csvContent = $response->streamedContent();

        // Assert
        $response->assertOk();

        // CSVに検索対象が含まれているか確認
        $this->assertStringContainsString(
            'yamada@example.com',
            $csvContent
        );

        // CSVに検索対象に一致しないデータが含まれていないか確認
        $this->assertStringNotContainsString(
            'sato@example.com',
            $csvContent
        );
    }

    // 性別条件に一致するデータが出力される
    public function test_csv_can_be_filtered_by_gender(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 2,
            'email' => 'female@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 1,
            'email' => 'other-gender@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get('/contacts/export?gender=2');

        // CSVの中身を文字列として取得
        $csvContent = $response->streamedContent();

        // Assert
        $response->assertOk();

        // CSVに検索対象が含まれているか確認
        $this->assertStringContainsString(
            'female@example.com',
            $csvContent
        );

        // CSVに検索対象に一致しないデータが含まれていないか確認
        $this->assertStringNotContainsString(
            'other-gender@example.com',
            $csvContent
        );
    }

    // カテゴリー条件に一致するデータが出力される
    public function test_csv_can_be_filtered_by_category(): void
    {
        // Arrange
        $user = User::factory()->create();

        $targetCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $otherCategory = Category::factory()->create([
            'content' => 'サービスについて',
        ]);

        Contact::factory()->create([
            'category_id' => $targetCategory->id,
            'email' => 'target-category@example.com',
        ]);

        Contact::factory()->create([
            'category_id' => $otherCategory->id,
            'email' => 'other-category@example.com',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get("/contacts/export?category_id={$targetCategory->id}");

        // CSVの中身を文字列として取得
        $csvContent = $response->streamedContent();

        // Assert
        $response->assertOk();

        // CSVに検索対象が含まれているか確認
        $this->assertStringContainsString(
            'target-category@example.com',
            $csvContent
        );

        // CSVに検索対象に一致しないデータが含まれていないか確認
        $this->assertStringNotContainsString(
            'other-category@example.com',
            $csvContent
        );
    }

    // 日付条件に一致するデータが出力される
    public function test_csv_can_be_filtered_by_date(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'target-date@example.com',
            'created_at' => '2026-08-14 10:00:00',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'other-date@example.com',
            'created_at' => '2026-08-13 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get('/contacts/export?date=2026-08-14');

        // CSVの中身を文字列として取得
        $csvContent = $response->streamedContent();

        // Assert
        $response->assertOk();

        // CSVに検索対象が含まれているか確認
        $this->assertStringContainsString(
            'target-date@example.com',
            $csvContent
        );

        // CSVに検索対象に一致しないデータが含まれていないか確認
        $this->assertStringNotContainsString(
            'other-date@example.com',
            $csvContent
        );
    }

    // 条件未指定時は全件が新着順でCSVに出力される
    public function test_all_contacts_are_exported_in_latest_order_without_filters(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'old@example.com',
            'created_at' => '2026-08-13 10:00:00',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'latest@example.com',
            'created_at' => '2026-08-14 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->get('/contacts/export');

        $csvContent = $response->streamedContent();

        // Assert
        $response->assertOk();

        // CSVを1行ずつに分ける
        $csvLines = explode("\n", trim($csvContent));

        // 2行目に新しいデータがある
        $this->assertStringContainsString(
            'latest@example.com',
            $csvLines[1]
        );

        // 3行目に古いデータがある
        $this->assertStringContainsString(
            'old@example.com',
            $csvLines[2]
        );
    }
}
