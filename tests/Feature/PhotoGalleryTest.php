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
        $this->get('/')->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->missing('canUpload'));

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
        ])->assertRedirect('/dashboard');

        $photo = Photo::query()->firstOrFail();

        $this->assertSame('holiday.jpg', $photo->name);
        Storage::disk('public')->assertExists($photo->path);

        $this->get('/dashboard')->assertInertia(fn ($page) => $page
            ->where('flash.success', "Foto's zijn toegevoegd."));
    }

    public function test_only_authenticated_users_can_manage_and_delete_photos(): void
    {
        Storage::fake('public');
        $photo = Photo::query()->create([
            'name' => 'holiday.jpg',
            'path' => 'photos/holiday.jpg',
        ]);
        Storage::disk('public')->put($photo->path, 'photo content');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->delete("/photos/{$photo->id}")->assertRedirect('/login');

        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('photos', 1));

        $this->delete("/photos/{$photo->id}")->assertRedirect('/dashboard');

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing('photos/holiday.jpg');
    }
}