<?php

namespace Modules\MCH\Enums;

enum MaternalPresentation: string
{
    case CEPHALIC = 'cephalic';
    case BREECH = 'breech';
    case TRANSVERSE = 'transverse';
    case OBLIQUE = 'oblique';
    case OTHER = 'other';
}
