<?php

namespace App\Domain\Sisahygo\Enums;

enum SisahygoApiEnvironment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';
}