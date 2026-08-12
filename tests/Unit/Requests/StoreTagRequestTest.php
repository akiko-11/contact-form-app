<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    // 正しいタグ名なら成功する
    public function test_valid_tag_name_passes_validation(): void
    {
        // Arrange
        $data = [
            'name' => 'テスト',
        ];

        $request = new StoreTagRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // タグ名が空なら失敗する
    public function test_empty_tag_name_fails_validation(): void
    {
        // Arrange
        $data = [
            'name' => '',
        ];

        $request = new StoreTagRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    // タグ名が上限の50文字なら成功する
    public function test_tag_name_with_50_characters_passes_validation(): void
    {
        // Arrange
        $data = [
            'name' => str_repeat('あ', 50),
        ];

        $request = new StoreTagRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    // タグ名が51文字なら失敗する
    public function test_tag_name_with_51_characters_fails_validation(): void
    {
        // Arrange
        $data = [
            'name' => str_repeat('あ', 51),
        ];

        $request = new StoreTagRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    // 登録済みのタグ名なら失敗する
    public function test_duplicate_tag_name_fails_validation(): void
    {
        // Arrange
        Tag::factory()->create([
            'name' => '質問',
        ]);

        $data = [
            'name' => '質問',
        ];

        $request = new StoreTagRequest;

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }
}
