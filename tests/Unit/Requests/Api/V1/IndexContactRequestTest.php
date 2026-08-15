<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    // 正しい検索条件ならバリデーションに成功する
    public function test_valid_search_conditions_pass_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-15',
            'page' => 2,
            'per_page' => 20,
        ];

        $request = new IndexContactRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // 性別の許可値（1・2・3）を受け付ける
    public function test_allowed_gender_values_pass_validation(): void
    {
        // Arrange
        $request = new IndexContactRequest;

        // Act・Assert
        foreach ([1, 2, 3] as $gender) {
            $validator = Validator::make(
                ['gender' => $gender],
                $request->rules()
            );

            $this->assertTrue(
                $validator->passes(),
                "gender={$gender} should pass validation."
            );
        }
    }

    // 不正な検索条件ならバリデーションに失敗する
    public function test_invalid_search_conditions_fail_validation(): void
    {
        // Arrange
        $request = new IndexContactRequest;

        $invalidCases = [
            // キーワードが255文字を超える場合
            ['data' => ['keyword' => str_repeat('a', 256)], 'field' => 'keyword'],

            // 性別が0の場合
            ['data' => ['gender' => 0], 'field' => 'gender'],

            // 性別が4の場合
            ['data' => ['gender' => 4], 'field' => 'gender'],

            // カテゴリIDが存在しない値の場合
            ['data' => ['category_id' => 999999],
                'field' => 'category_id'],

            // 日付にdate型以外が設定された場合
            ['data' => ['date' => 'invalid-date'], 'field' => 'date'],

            // ページ番号が0の場合
            ['data' => ['page' => 0], 'field' => 'page'],

            // 1ページあたりの件数が0の場合
            ['data' => ['per_page' => 0], 'field' => 'per_page'],

            // 1ページあたりの件数が100を超える場合
            ['data' => ['per_page' => 101], 'field' => 'per_page'],
        ];

        // Act・Assert
        foreach ($invalidCases as $case) {
            $validator = Validator::make(
                $case['data'],
                $request->rules()
            );

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has($case['field'])
            );
        }
    }
}
