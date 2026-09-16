<?php

namespace App\Enums;

enum ProcessStatus: string
{
    case Start = 'start';
    case Pause = 'pause';
    case Stop = 'stop';
}
