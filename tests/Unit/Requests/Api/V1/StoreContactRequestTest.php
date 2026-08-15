<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    // 正しい必須項目と存在するタグIDならバリデーションに成功する
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

    // 必須項目が未入力ならバリデーションに失敗する
    public function test_required_fields_fail_validation_when_missing(): void
    {
        // Arrange
        $request = new StoreContactRequest;

        // Act
        $validator = Validator::make([], $request->rules());

        // Assert
        $this->assertTrue($validator->fails());

        foreach ([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ] as $field) {
            $this->assertTrue($validator->errors()->has($field));
        }
    }

    // 電話番号が10桁または11桁ならバリデーションに成功する
    public function test_10_and_11_digit_phone_numbers_pass_validation(): void
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
        foreach (['0312345678', '09012345678'] as $tel) {
            $data['tel'] = $tel;

            $validator = Validator::make($data, $request->rules());

            $this->assertTrue(
                $validator->passes(),
                "tel={$tel}はバリデーションに成功する必要があります"
            );
        }
    }

    // 不正な値ならバリデーションに失敗する
    public function test_invalid_contact_data_fails_validation(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $validData = [
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

        $invalidCases = [
            // 姓が255文字を超える場合
            ['field' => 'first_name', 'value' => str_repeat('a', 256)],

            // 名が255文字を超える場合
            ['field' => 'last_name', 'value' => str_repeat('a', 256)],

            // 性別が0の場合
            ['field' => 'gender', 'value' => 0],

            // 性別が4の場合
            ['field' => 'gender', 'value' => 4],

            // メールアドレス形式でない場合
            ['field' => 'email', 'value' => 'invalid-email'],

            // 電話番号が9桁の場合
            ['field' => 'tel', 'value' => '123456789'],

            // 電話番号が12桁の場合
            ['field' => 'tel', 'value' => '123456789012'],

            // 電話番号にハイフンが使用されている場合
            ['field' => 'tel', 'value' => '090-1234-5678'],

            // 住所が255文字を超える場合
            ['field' => 'address', 'value' => str_repeat('a', 256)],

            // 建物名が255文字を超える場合
            ['field' => 'building', 'value' => str_repeat('a', 256)],

            // お問い合わせ分類のIDが存在しないIDの場合
            ['field' => 'category_id', 'value' => 999999],

            // お問い合わせ内容が120文字を超える場合
            ['field' => 'detail', 'value' => str_repeat('a', 121)],

            // タグIDの入力が配列でない場合
            ['field' => 'tag_ids', 'value' => 'invalid'],

            // タグIDが整数でない場合
            [
                'field' => 'tag_ids',
                'value' => ['invalid'],
                'error_field' => 'tag_ids.0',
            ],

            // タグIDが存在しない場合
            [
                'field' => 'tag_ids',
                'value' => [999999],
                'error_field' => 'tag_ids.0',
            ],
        ];

        $request = new StoreContactRequest;

        // Act・Assert
        foreach ($invalidCases as $case) {
            $data = $validData;

            // 検証対象の項目だけを不正な値へ置き換え
            $data[$case['field']] = $case['value'];

            // API用StoreContactRequestのルールで検証
            $validator = Validator::make($data, $request->rules());

            // タグIDのような配列項目は、子要素のエラー項目名を使用する
            $errorField = $case['error_field'] ?? $case['field'];

            $this->assertTrue($validator->fails());
            $this->assertTrue(
                $validator->errors()->has($errorField)
            );
        }
    }
}
