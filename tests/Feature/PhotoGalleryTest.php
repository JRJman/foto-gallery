<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_the_gallery_but_cannot_upload_photos(): void
    {
        $this->get('/')->assertOk();

        $this->post('/photos', [
            'photos' => [UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg')],
        ])->assertRedirect('/login');
    }

    public function test_authenticated_users_can_upload_photos(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post('/photos', [
            'photos' => [UploadedFile::fake()->create('holiday.jpg', 10, 'image/jpeg')],
        ])->assertRedirect('/');

        $photo = Photo::query()->firstOrFail();

        $this->assertSame('holiday.jpg', $photo->name);
        Storage::disk('public')->assertExists($photo->path);
    }
}