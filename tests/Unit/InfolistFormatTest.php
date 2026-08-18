<?php

namespace Modules\MCH\Tests\Unit;

use Modules\MCH\Enums\DangerSign;
use Modules\MCH\Filament\Support\InfolistFormat;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InfolistFormatTest extends TestCase
{
    #[Test]
    public function it_formats_enum_labels_or_returns_dash_for_empty_values(): void
    {
        $this->assertSame('-', InfolistFormat::enumLabels(null, DangerSign::class));
        $this->assertSame('-', InfolistFormat::enumLabels([], DangerSign::class));
        $this->assertSame(
            DangerSign::BLEEDING->getLabel(),
            InfolistFormat::enumLabels([DangerSign::BLEEDING->value], DangerSign::class),
        );
    }

    #[Test]
    public function it_formats_yes_no_values(): void
    {
        $this->assertSame('-', InfolistFormat::yesNo(null));
        $this->assertSame('Yes', InfolistFormat::yesNo(true));
        $this->assertSame('No', InfolistFormat::yesNo(false));
    }
}
