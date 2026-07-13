<?php

namespace Tests\Unit\Domain\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use PHPUnit\Framework\TestCase;

class ClientAccountRoleTest extends TestCase
{
    public function test_owner_and_administrator_can_manage_account(): void
    {
        $this->assertTrue(ClientAccountRole::Owner->canManageAccount());
        $this->assertTrue(ClientAccountRole::Administrator->canManageAccount());
    }

    public function test_non_administrative_roles_do_not_manage_account(): void
    {
        $this->assertFalse(ClientAccountRole::Operator->canManageAccount());
        $this->assertFalse(ClientAccountRole::Viewer->canManageAccount());
        $this->assertFalse(ClientAccountRole::Accounting->canManageAccount());
    }
}