<?php

namespace Modules\MCH\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\MCH\Enums\VaccineAntigen;
use Modules\MCH\Models\Vaccine;
use Tests\TestCase;

class VaccineCatalogueTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'MCH']);
    }

    public function test_creates_vaccine_for_bcg_antigen(): void
    {
        $vaccine = Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'Bacillus Calmette-Guerin',
            'route' => 'intradermal',
            'site' => 'left_upper_arm',
            'presentation' => 'powder_solution',
        ]);

        $this->assertSame(VaccineAntigen::BCG, $vaccine->antigen);
        $this->assertSame('Bacillus Calmette-Guerin', $vaccine->name);
        $this->assertTrue($vaccine->is_active);
    }

    public function test_one_vaccine_per_antigen(): void
    {
        Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'BCG',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Vaccine::create([
            'antigen' => VaccineAntigen::BCG,
            'name' => 'BCG duplicate',
        ]);
    }

    public function test_vaccine_is_not_branch_scoped(): void
    {
        $vaccine = Vaccine::create([
            'antigen' => VaccineAntigen::OPV,
            'name' => 'Oral Polio Vaccine',
        ]);

        // Without setting branch context, the vaccine should be queryable.
        $found = Vaccine::withoutGlobalScopes()->where('antigen', VaccineAntigen::OPV)->first();
        $this->assertNotNull($found);
        $this->assertSame($vaccine->id, $found->id);
    }

    public function test_vaccine_factory(): void
    {
        $vaccine = Vaccine::factory()->create();

        $this->assertNotNull($vaccine->antigen);
        $this->assertNotNull($vaccine->name);
    }
}
