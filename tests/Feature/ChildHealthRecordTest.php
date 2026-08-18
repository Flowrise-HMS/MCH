<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Branch;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class ChildHealthRecordTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'MCH']);
    }

    public function test_creates_child_record_for_cwc_registration(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);

        $record = ChildHealthRecord::create([
            'patient_id' => $child->id,
            'branch_id' => $branch->id,
            'date_of_birth' => $child->date_of_birth->toDateString(),
        ]);

        $this->assertSame($child->id, $record->patient_id);
        $this->assertSame('active', $record->status->value);
    }

    public function test_one_active_record_per_child(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);

        ChildHealthRecord::create(['patient_id' => $child->id, 'branch_id' => $branch->id]);

        $this->expectException(\RuntimeException::class);
        ChildHealthRecord::create(['patient_id' => $child->id, 'branch_id' => $branch->id]);
    }

    public function test_soft_deleted_record_allows_new_registration(): void
    {
        $branch = Branch::factory()->create();
        $child = Patient::factory()->child()->create(['branch_id' => $branch->id]);

        $original = ChildHealthRecord::create(['patient_id' => $child->id, 'branch_id' => $branch->id]);
        $original->delete();

        $replacement = ChildHealthRecord::create(['patient_id' => $child->id, 'branch_id' => $branch->id]);

        $this->assertNotSame($original->id, $replacement->id);
        $this->assertSame($child->id, $replacement->patient_id);
    }
}
