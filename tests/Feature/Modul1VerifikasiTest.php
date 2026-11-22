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
            ->assertRedirect(route('listener.profile.edit').'#kartu-bio')
            ->assertSessionHas('bio_status', 'Profil disimpan.');

        $this->assertSame('Suka mendengarkan.', $listener->listenerProfile->fresh()->bio);
    }

    public function test_bio_longer_than_1000_characters_is_rejected(): void
    {
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->put('/listener/profile', ['bio' => str_repeat('a', 1001)])
            ->assertRedirect(route('listener.profile.edit').'#kartu-bio')
            ->assertSessionHasErrors('bio');
    }

    public function test_listener_can_upload_a_document(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('listener.profile.edit').'#dokumen')
            ->assertSessionHas('doc_status');

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
        ])->assertRedirect(route('listener.profile.edit').'#dokumen')
            ->assertSessionHasErrors('document');

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
            ->assertSee('Tidak bisa disetujui atau ditolak tanpa dokumen');
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

    private function pendingListenerWithDocument(): User
    {
        $listener = User::factory()->age(21)->listener()->create(['name' => 'Tono Menunggu']);
        $listener->listenerProfile->documents()->create(['path' => 'credentials/a.pdf', 'original_name' => 'ijazah.pdf']);

        return $listener;
    }

    public function test_admin_approval_sets_title_and_marks_documents(): void
    {
        $listener = $this->pendingListenerWithDocument();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $listener->listenerProfile), ['credential_level' => 'psych_student'])
            ->assertRedirect(route('admin.verifications.index'));

        $profile = $listener->listenerProfile->fresh();
        $this->assertSame(ListenerProfile::APPROVED, $profile->verification_status);
        $this->assertSame(ListenerProfile::PSYCH_STUDENT, $profile->credential_level);
        $this->assertSame($admin->id, $profile->verified_by);
        $this->assertNotNull($profile->verified_at);
        $this->assertTrue($listener->fresh()->isVerifiedListener());

        $dokumen = $profile->documents()->first();
        $this->assertSame('approved', $dokumen->status);
        $this->assertSame($admin->id, $dokumen->reviewed_by);
        $this->assertNotNull($dokumen->reviewed_at);
    }

    public function test_approval_rejects_an_unknown_credential_level(): void
    {
        $listener = $this->pendingListenerWithDocument();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.verifications.approve', $listener->listenerProfile), ['credential_level' => 'dokter'])
            ->assertSessionHasErrors('credential_level');

        $this->assertSame(ListenerProfile::PENDING, $listener->listenerProfile->fresh()->verification_status);
    }

    public function test_profile_without_pending_documents_cannot_be_approved_or_rejected(): void
    {
        $listener = User::factory()->age(21)->listener()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $listener->listenerProfile), ['credential_level' => 'volunteer'])
            ->assertSessionHas('status', 'Tidak ada dokumen yang menunggu pemeriksaan untuk profil ini.');
        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $listener->listenerProfile), ['note' => 'Tidak ada dokumen'])
            ->assertSessionHas('status', 'Tidak ada dokumen yang menunggu pemeriksaan untuk profil ini.');

        $profile = $listener->listenerProfile->fresh();
        $this->assertSame(ListenerProfile::PENDING, $profile->verification_status);
        $this->assertNull($profile->credential_level);
    }

    public function test_an_already_processed_profile_is_not_overwritten(): void
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.verifications.approve', $listener->listenerProfile), ['credential_level' => 'psychologist'])
            ->assertSessionHas('status', 'Profil ini sudah diproses sebelumnya.');

        $this->assertSame(ListenerProfile::VOLUNTEER, $listener->listenerProfile->fresh()->credential_level);
    }

    public function test_admin_rejection_stores_the_note_and_the_listener_sees_it(): void
    {
        $listener = $this->pendingListenerWithDocument();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $listener->listenerProfile), ['note' => 'Dokumen buram'])
            ->assertRedirect(route('admin.verifications.index'));

        $profile = $listener->listenerProfile->fresh();
        $this->assertSame(ListenerProfile::REJECTED, $profile->verification_status);
        $this->assertNull($profile->credential_level);

        $dokumen = $profile->documents()->first();
        $this->assertSame('rejected', $dokumen->status);
        $this->assertSame('Dokumen buram', $dokumen->review_note);
        $this->assertSame($admin->id, $dokumen->reviewed_by);

        $this->actingAs($listener)->get('/listener/profile')->assertSee('Dokumen buram');
    }

    public function test_rejection_requires_a_note(): void
    {
        $listener = $this->pendingListenerWithDocument();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.verifications.reject', $listener->listenerProfile), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertSame(ListenerProfile::PENDING, $listener->listenerProfile->fresh()->verification_status);
    }

    public function test_only_admin_can_approve_or_reject(): void
    {
        $listener = $this->pendingListenerWithDocument();
        $profile = $listener->listenerProfile;

        $this->actingAs($listener)
            ->post(route('admin.verifications.approve', $profile), ['credential_level' => 'psychologist'])
            ->assertForbidden();
        $this->actingAs(User::factory()->age(20)->create())
            ->post(route('admin.verifications.reject', $profile), ['note' => 'x'])
            ->assertForbidden();

        $this->assertSame(ListenerProfile::PENDING, $profile->fresh()->verification_status);
    }

    public function test_rejected_listener_can_be_approved_after_uploading_again(): void
    {
        Storage::fake('local');
        $listener = $this->pendingListenerWithDocument();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $listener->listenerProfile), ['note' => 'Buram']);

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ]);
        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $listener->listenerProfile), ['credential_level' => 'volunteer']);

        $this->assertTrue($listener->fresh()->isVerifiedListener());
    }

    public function test_invalid_upload_shows_the_error_inside_the_documents_card(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->followingRedirects()->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'),
        ])->assertSee('id="dokumen"', false)
            ->assertSee('Dokumen tidak diunggah');
    }

    public function test_success_messages_are_shown_inside_their_cards(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->followingRedirects()->put('/listener/profile', ['bio' => 'Halo'])
            ->assertSee('id="kartu-bio"', false)
            ->assertSee('Profil disimpan.');

        $this->actingAs($listener)->followingRedirects()->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        ])->assertSee('Dokumen berhasil diunggah');
    }

    public function test_upload_and_bio_errors_are_in_indonesian(): void
    {
        Storage::fake('local');
        $listener = User::factory()->age(21)->listener()->create();

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'),
        ])->assertSessionHasErrors(['document' => 'Format file harus PDF, JPG, atau PNG.']);

        $this->actingAs($listener)->post('/listener/documents', [
            'document' => UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'),
        ])->assertSessionHasErrors(['document' => 'Ukuran file maksimal 5 MB.']);

        $this->actingAs($listener)->post('/listener/documents', [])
            ->assertSessionHasErrors(['document' => 'Pilih file terlebih dahulu.']);

        $this->actingAs($listener)->put('/listener/profile', ['bio' => str_repeat('a', 1001)])
            ->assertSessionHasErrors(['bio' => 'Bio maksimal 1000 karakter.']);
    }
}
