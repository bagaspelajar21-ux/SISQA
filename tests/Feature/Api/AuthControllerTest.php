<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_profile_photo_and_receive_its_public_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('profile.png'),
        ], ['Accept' => 'application/json'])
            ->assertOk();

        $photoPath = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($photoPath);
        $response->assertJsonPath('user.profile_photo_url', '/storage/'.$photoPath);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.profile_photo_url', $response->json('user.profile_photo_url'));
    }

    public function test_replacing_a_profile_photo_removes_the_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('first.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $previousPhotoPath = $user->fresh()->profile_photo_path;

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('second.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($previousPhotoPath);
        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_keeps_the_existing_profile_photo_when_saving_without_a_new_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $photoPath = UploadedFile::fake()->image('profile.png')->storePublicly('profile-photos', 'public');
        $user->profile_photo_path = $photoPath;
        $user->save();
        Sanctum::actingAs($user);

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => 'Nama Baru',
            'email' => $user->email,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('user.profile_photo_url', '/storage/'.$photoPath);

        Storage::disk('public')->assertExists($photoPath);
        $this->assertSame($photoPath, $user->fresh()->profile_photo_path);
    }

    public function test_returns_422_when_profile_photo_is_not_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile_photo']);

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertDirectoryEmpty('profile-photos');
    }

    public function test_returns_422_when_profile_photo_exceeds_2_mb(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('oversized.png')->size(2049),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile_photo']);

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertDirectoryEmpty('profile-photos');
    }

    public function test_returns_401_when_profile_photo_is_uploaded_without_authentication(): void
    {
        Storage::fake('public');

        $this->post('/api/user/profile', [
            '_method' => 'PUT',
            'name' => 'QA Tester',
            'email' => 'qa@example.test',
            'profile_photo' => UploadedFile::fake()->image('profile.png'),
        ], ['Accept' => 'application/json'])
            ->assertUnauthorized();

        Storage::disk('public')->assertDirectoryEmpty('profile-photos');
    }
}
