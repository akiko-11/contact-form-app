<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    // 正しい必須項目と存在するタグidならバリデーションに成功する
    public function test_valid_contact_data_with_tag_passes_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $request = new StoreContactRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // 電話番号が10桁または11桁ならバリデーションに成功する
    public function test_10_and_11_digit_phone_numbers_pass_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'address' => '東京都渋谷区',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $request = new StoreContactRequest;

        // Act・Assert
        foreach (['0312345678', '09012345678'] as $tel) {
            $data['tel'] = $tel;

            $validator = Validator::make($data, $request->rules());

            $this->assertTrue(
                $validator->passes(),
                "tel={$tel}はバリデーションに成功する必要があります"
            );
        }
    }

    // 電話番号が9桁または12桁ならバリデーションに失敗する
    public function test_9_and_12_digit_phone_numbers_fail_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ];

        $request = new StoreContactRequest;

        // Act・Assert
        foreach (['123456789', '123456789012'] as $tel) {
            $data['tel'] = $tel;

            $validator = Validator::make($data, $request->rules());

            $this->assertTrue(
                $validator->fails(),
                "tel={$tel}はバリデーションに失敗する必要があります"
            );

            $this->assertTrue(
                $validator->errors()->has('tel')
            );
        }
    }

    // 不正な電話番号形式ならバリデーションに失敗する
    public function test_invalid_phone_number_fails_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '090-1234-5678',
            'address' => '東京都渋谷区',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ];

        $request = new StoreContactRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('tel'));
    }
}
