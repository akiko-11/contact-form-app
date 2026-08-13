<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    // 正しいCSV検索条件ならバリデーションに成功する
    public function test_valid_export_conditions_pass_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-09',
        ];

        $request = new ExportContactRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // 性別（0・1・2・3）の全許可値を受け付ける
    public function test_allowed_gender_values_pass_validation(): void
    {
        // Arrange
        $request = new ExportContactRequest;

        // Act・Assert
        foreach ([0, 1, 2, 3] as $gender) {
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

    // 不正な性別値ならバリデーションに失敗する
    public function test_disallowed_gender_values_fail_validation(): void
    {
        // Arrange
        $request = new ExportContactRequest;

        // Act・Assert
        foreach ([-1, 4] as $gender) {
            $validator = Validator::make(
                ['gender' => $gender],
                $request->rules()
            );

            $this->assertTrue(
                $validator->fails(),
                "gender={$gender} should fail validation."
            );

            $this->assertTrue(
                $validator->errors()->has('gender')
            );
        }
    }

    // 存在しないカテゴリーIDならバリデーションに失敗する
    public function test_nonexistent_category_id_fails_validation(): void
    {
        // Arrange
        $request = new ExportContactRequest;

        // Act
        $validator = Validator::make(
            ['category_id' => 999999],
            $request->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue(
            $validator->errors()->has('category_id')
        );
    }
}
