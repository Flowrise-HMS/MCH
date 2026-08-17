<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Models\PregnancyEpisode;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class MchRecordIssuanceTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    private MchBookIssuanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'MCH']);
        $this->branch = Branch::factory()->create();
        $this->service = app(MchBookIssuanceService::class);
    }

    private function episode(): PregnancyEpisode
    {
        return PregnancyEpisode::create([
            'patient_id' => Patient::factory()->female()->create(['branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_issues_one_book_per_pregnancy(): void
    {
        $episode = $this->episode();

        $book = $this->service->issue($episode, $this->branch, 'ANC');

        $this->assertSame($episode->id, $book->owner_id);
        $this->assertSame(PregnancyEpisode::class, $book->owner_type);
        $this->assertSame(MchRecordStatus::ACTIVE, $book->status);
        $this->assertMatchesRegularExpression('/^ANC-\d{4}$/', $book->serial_number);
    }

    public function test_duplicate_issue_for_same_owner_is_rejected(): void
    {
        $episode = $this->episode();
        $this->service->issue($episode, $this->branch, 'ANC');

        $this->expectException(\RuntimeException::class);
        $this->service->issue($episode, $this->branch, 'ANC');
    }

    public function test_serial_is_unique_per_branch(): void
    {
        $episode = $this->episode();
        $other = $this->episode();

        $first = $this->service->issue($episode, $this->branch, 'ANC');
        $second = $this->service->issue($other, $this->branch, 'ANC');

        $this->assertNotSame($first->serial_number, $second->serial_number);
    }

    public function test_replacement_keeps_history_and_allows_new_active_book(): void
    {
        $episode = $this->episode();
        $original = $this->service->issue($episode, $this->branch, 'ANC');

        $replacement = $this->service->replace($original);

        $original->refresh();
        $this->assertSame(MchRecordStatus::REPLACED, $original->status);
        $this->assertSame($replacement->id, $original->replaced_by);
        $this->assertSame(MchRecordStatus::ACTIVE, $replacement->status);
        $this->assertSame($episode->id, $replacement->owner_id);
    }

    public function test_consent_is_stored_but_not_enforced(): void
    {
        $episode = $this->episode();

        $book = $this->service->issue($episode, $this->branch, 'ANC', consent: ['data_consented' => true]);

        $this->assertTrue($book->data_consented);
        $this->assertNotNull($book->consented_at);
    }
}
