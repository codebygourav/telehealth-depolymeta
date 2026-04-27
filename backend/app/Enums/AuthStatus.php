<?php

namespace App\Enums;

enum AuthStatus: string
{
    case new_register = 'new_register';
    case verified = 'verified';
    case registered = 'registered';
}
