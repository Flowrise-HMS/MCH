<?php

namespace Modules\MCH\Enums;

enum MchRecordStatus: string
{
    case ACTIVE = 'active';
    case LOST = 'lost';
    case DAMAGED = 'damaged';
    case REPLACED = 'replaced';
}
