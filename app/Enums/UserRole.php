<?php

namespace App\Enums;

enum UserRole: int
{
    case OWNER = 1;
    case MANAGER = 2;
    case CASHIER = 3;
}
