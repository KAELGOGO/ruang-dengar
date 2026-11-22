<?php

namespace Tests\Feature;

use App\Models\ListenerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Modul1VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_pending_listener_sees_status_on_profile_page(): void
    {
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->get('/listener/profile')
            ->assertOk()
            ->assertSee('Menunggu verifikasi');
    }

    public function test_approved_listener_sees_title_on_profile_page(): void
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();

        $this->actingAs($listener)->get('/listener/profile')
            ->assertOk()
            ->assertSee('Terverifikasi')
            ->assertSee('Relawan terverifikasi');
    }

    public function test_rejected_listener_sees_the_rejection_note(): void
    {
        $listener = User::factory()->age(21)->listener()->create();
        $profile = $listener->listenerProfile;
        $profile->forceFill(['verification_status' => ListenerProfile::REJECTED])->save();
        $profile->documents()->create(['path' => 'credentials/x.pdf', 'original_name' => 'ijazah.pdf'])
            ->forceFill(['status' => 'rejected', 'review_note' => 'Dokumen buram'])->save();

        $this->actingAs($listener)->get('/listener/profile')
            ->assertOk()
            ->assertSee('Ditolak')
            ->assertSee('Dokumen buram')
            ->assertSee('ijazah.pdf');
    }

    public function test_regular_user_cannot_open_listener_profile(): void
    {
        $this->actingAs(User::factory()->age(20)->create())->get('/listener/profile')->assertForbidden();
    }

    public function test_listener_can_save_bio(): void
    {
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->put('/listener/profile', ['bio' => 'Suka mendengarkan.'])
            ->assertRedirect();

        $this->assertSame('Suka mendengarkan.', $listener->listenerProfile->fresh()->bio);
    }

    public function test_bio_longer_than_1000_characters_is_rejected(): void
    {
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->put('/listener/profile', ['bio' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('bio');
    }

    public function test_listener_can_upload_a_document(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $dokumen = $listener->listenerProfile->documents()->first();
        $this->assertNotNull($dokumen);
        $this->assertSame('ijazah.pdf', $dokumen->original_name);
        $this->assertSame('pending', $dokumen->status);
        Storage::disk('local')->assertExists($dokumen->path);
    }

    public function test_executable_and_oversized_files_are_rejected(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'),
        ])->assertSessionHasErrors('document');

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors('document');

        $this->assertSame(0, $listener->listenerProfile->documents()->count());
    }

    public function test_rejected_listener_goes_back_to_pending_after_uploading_again(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();
        $listener->listenerProfile->forceFill(['verification_status' => ListenerProfile::REJECTED])->save();

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(ListenerProfile::PENDING, $listener->listenerProfile->fresh()->verification_status);
    }

    public function test_regular_user_cannot_upload_documents(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->age(20)->create())->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_admin_sees_pending_listeners_with_age_and_documents(): void
    {
        $pending = User::factory()->age(21)->listener()->create(['name' => 'Tono Menunggu']);
        $pending->listenerProfile->documents()->create(['path' => 'credentials/a.pdf', 'original_name' => 'ijazah-tono.pdf']);
        User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create(['name' => 'Sudah Disetujui']);

        $this->actingAs(User::factory()->admin()->create())->get('/admin/verifications')
            ->assertOk()
            ->assertSee('Tono Menunggu')
            ->assertSee('21 tahun')
            ->assertSee('Dewasa muda')
            ->assertSee('ijazah-tono.pdf')
            ->assertDontSee('Sudah Disetujui');
    }

    public function test_admin_list_warns_when_a_pending_listener_has_no_documents(): void
    {
        User::factory()->age(21)->listener()->create(['name' => 'Tanpa Dokumen']);

        $this->actingAs(User::factory()->admin()->create())->get('/admin/verifications')
            ->assertOk()
            ->assertSee('Belum ada dokumen yang diunggah.')
            ->assertSee('Tidak bisa disetujui tanpa dokumen');
    }

    public function test_admin_list_shows_empty_message(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/verifications')
            ->assertOk()
            ->assertSee('Tidak ada listener yang menunggu verifikasi.');
    }

    public function test_only_admin_can_open_the_verification_list(): void
    {
        $this->actingAs(User::factory()->age(20)->create())->get('/admin/verifications')->assertForbidden();
        $this->actingAs(User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create())
            ->get('/admin/verifications')->assertForbidden();
    }

    public function test_admin_can_download_a_document_with_its_original_name(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('credentials/abc.pdf', 'isi dokumen');
        $listener = User::factory()->age(21)->listener()->create();
        $dokumen = $listener->listenerProfile->documents()
            ->create(['path' => 'credentials/abc.pdf', 'original_name' => 'ijazah.pdf']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.documents.show', $dokumen))
            ->assertOk()
            ->assertDownload('ijazah.pdf');
    }

    public function test_download_returns_404_when_the_file_is_missing_on_disk(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();
        $dokumen = $listener->listenerProfile->documents()
            ->create(['path' => 'credentials/hilang.pdf', 'original_name' => 'hilang.pdf']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.documents.show', $dokumen))
            ->assertNotFound();
    }

    public function test_only_admin_can_download_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('credentials/abc.pdf', 'isi dokumen');
        $listener = User::factory()->age(21)->listener()->create();
        $dokumen = $listener->listenerProfile->documents()
            ->create(['path' => 'credentials/abc.pdf', 'original_name' => 'ijazah.pdf']);

        // Not even the owner of the document can use the admin route.
        $this->actingAs($listener)->get(route('admin.documents.show', $dokumen))->assertForbidden();
        $this->actingAs(User::factory()->age(20)->create())->get(route('admin.documents.show', $dokumen))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.documents.show', $dokumen))->assertRedirect('/login');
    }
}
