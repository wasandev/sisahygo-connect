<?php

namespace App\Domain\Sisahygo\Enums;

enum SisahygoCredentialStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
}