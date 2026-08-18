<?php

namespace Modules\MCH\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum VaccineAntigen: string implements HasColor, HasDescription, HasLabel
{
    case BCG = 'bcg';
    case OPV = 'opv';
    case PENTAVALENT = 'pentavalent';
    case PCV = 'pcv';
    case ROTAVIRUS = 'rotavirus';
    case MEASLES_RUBELLA = 'measles_rubella';
    case YELLOW_FEVER = 'yellow_fever';
    case HEPATITIS_B = 'hepatitis_b';
    case TETANUS_TOXOID = 'tetanus_toxoid';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::BCG => 'BCG',
            self::OPV => 'Oral Polio Vaccine',
            self::PENTAVALENT => 'Pentavalent (DPT-HepB-Hib)',
            self::PCV => 'Pneumococcal Conjugate',
            self::ROTAVIRUS => 'Rotavirus',
            self::MEASLES_RUBELLA => 'Measles-Rubella',
            self::YELLOW_FEVER => 'Yellow Fever',
            self::HEPATITIS_B => 'Hepatitis B',
            self::TETANUS_TOXOID => 'Tetanus Toxoid',
        };
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::BCG => 'Tuberculosis prevention.',
            self::OPV => 'Oral poliomyelitis prevention.',
            self::PENTAVALENT => 'Diphtheria, tetanus, pertussis, hepatitis B, Haemophilus influenzae type b.',
            self::PCV => 'Pneumococcal conjugate vaccine.',
            self::ROTAVIRUS => 'Rotavirus gastroenteritis prevention.',
            self::MEASLES_RUBELLA => 'Measles and rubella prevention.',
            self::YELLOW_FEVER => 'Yellow fever prevention.',
            self::HEPATITIS_B => 'Hepatitis B prevention.',
            self::TETANUS_TOXOID => 'Maternal/neonatal tetanus prevention.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BCG => 'info',
            self::OPV => 'success',
            self::PENTAVALENT => 'primary',
            self::PCV => 'warning',
            self::ROTAVIRUS => 'danger',
            self::MEASLES_RUBELLA => 'success',
            self::YELLOW_FEVER => 'warning',
            self::HEPATITIS_B => 'info',
            self::TETANUS_TOXOID => 'primary',
        };
    }
}
