<?php

namespace Database\Seeders\Development;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientAccountDemoSeeder extends Seeder
{
    private const FAKE_PASSWORD = 'password';

    public function run(): void
    {
        $users = $this->seedUsers();
        $accounts = $this->seedAccounts();

        $this->seedMemberships($users, $accounts);
        $this->seedCustomerLinks($accounts);
        $this->seedCapabilities($accounts);
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $definitions = [
            'no_account' => ['name' => 'คุณไม่มีบัญชี สาธิต', 'email' => 'noaccount@demo.test'],
            'single_owner' => ['name' => 'คุณบัญชีเดียว สาธิต', 'email' => 'owner@abc-demo.test'],
            'multi_owner' => ['name' => 'คุณหลายบัญชี สาธิต', 'email' => 'multi@demo.test'],
            'sender_owner' => ['name' => 'คุณผู้ส่ง สาธิต', 'email' => 'sender@sender-demo.test'],
            'receiver_owner' => ['name' => 'คุณผู้รับ สาธิต', 'email' => 'receiver@receiver-demo.test'],
            'viewer' => ['name' => 'คุณดูข้อมูล สาธิต', 'email' => 'viewer@abc-demo.test'],
            'accounting' => ['name' => 'คุณบัญชี สาธิต', 'email' => 'accounting@abc-demo.test'],
        ];

        $users = [];

        foreach ($definitions as $key => $definition) {
            $users[$key] = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::FAKE_PASSWORD),
                ],
            );
        }

        return $users;
    }

    /**
     * @return array<string, ClientAccount>
     */
    private function seedAccounts(): array
    {
        $definitions = [
            'single' => ['name' => 'บริษัท สาธิตบัญชีเดียว จำกัด', 'code' => 'SC-DEMO-SINGLE'],
            'sender' => ['name' => 'บริษัท ผู้ส่งสาธิต จำกัด', 'code' => 'SC-DEMO-SENDER'],
            'receiver' => ['name' => 'บริษัท ผู้รับสาธิต จำกัด', 'code' => 'SC-DEMO-RECEIVER'],
            'both' => ['name' => 'บริษัท ส่งรับสาธิต จำกัด', 'code' => 'SC-DEMO-BOTH'],
            'accounting' => ['name' => 'บริษัท บัญชีสาธิต จำกัด', 'code' => 'SC-DEMO-ACCOUNTING'],
        ];

        $accounts = [];

        foreach ($definitions as $key => $definition) {
            $accounts[$key] = ClientAccount::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'status' => ClientAccountStatus::Active,
                ],
            );
        }

        return $accounts;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, ClientAccount> $accounts
     */
    private function seedMemberships(array $users, array $accounts): void
    {
        $this->membership($accounts['single'], $users['single_owner'], ClientAccountRole::Owner);

        $this->membership($accounts['sender'], $users['multi_owner'], ClientAccountRole::Owner);
        $this->membership($accounts['receiver'], $users['multi_owner'], ClientAccountRole::Owner);
        $this->membership($accounts['both'], $users['multi_owner'], ClientAccountRole::Owner);

        $this->membership($accounts['sender'], $users['sender_owner'], ClientAccountRole::Owner);
        $this->membership($accounts['receiver'], $users['receiver_owner'], ClientAccountRole::Owner);

        $this->membership($accounts['both'], $users['viewer'], ClientAccountRole::Viewer);
        $this->membership($accounts['accounting'], $users['accounting'], ClientAccountRole::Accounting);
    }

    /**
     * @param array<string, ClientAccount> $accounts
     */
    private function seedCustomerLinks(array $accounts): void
    {
        $this->customerLink($accounts['single'], 10001, canSend: true, canReceive: true, canViewPayment: true, defaultSender: true, defaultReceiver: true);
        $this->customerLink($accounts['sender'], 10001, canSend: true, canReceive: false, canViewPayment: true, defaultSender: true, defaultReceiver: false);
        $this->customerLink($accounts['receiver'], 20001, canSend: false, canReceive: true, canViewPayment: true, defaultSender: false, defaultReceiver: true);
        $this->customerLink($accounts['both'], 10002, canSend: true, canReceive: false, canViewPayment: true, defaultSender: true, defaultReceiver: false);
        $this->customerLink($accounts['both'], 20002, canSend: false, canReceive: true, canViewPayment: true, defaultSender: false, defaultReceiver: true);
        $this->customerLink($accounts['accounting'], 20002, canSend: false, canReceive: true, canViewPayment: true, defaultSender: false, defaultReceiver: true);
    }

    /**
     * @param array<string, ClientAccount> $accounts
     */
    private function seedCapabilities(array $accounts): void
    {
        $this->capabilities($accounts['single'], [
            ClientCapability::OrderCreate,
            ClientCapability::OrderBulk,
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
            ClientCapability::UsersManage,
            ClientCapability::SettingsManage,
        ]);

        $this->capabilities($accounts['sender'], [
            ClientCapability::OrderCreate,
            ClientCapability::OrderBulk,
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::UsersManage,
            ClientCapability::SettingsManage,
        ]);

        $this->capabilities($accounts['receiver'], [
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
        ]);

        $this->capabilities($accounts['both'], [
            ClientCapability::OrderCreate,
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
            ClientCapability::UsersManage,
            ClientCapability::SettingsManage,
        ]);

        $this->capabilities($accounts['accounting'], [
            ClientCapability::ShipmentView,
            ClientCapability::PaymentView,
            ClientCapability::PaymentDownload,
        ]);
    }

    private function membership(ClientAccount $account, User $user, ClientAccountRole $role): void
    {
        ClientAccountUser::query()->updateOrCreate(
            [
                'client_account_id' => $account->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'is_active' => true,
                'joined_at' => now(),
            ],
        );
    }

    private function customerLink(
        ClientAccount $account,
        int $customerId,
        bool $canSend,
        bool $canReceive,
        bool $canViewPayment,
        bool $defaultSender,
        bool $defaultReceiver,
    ): void {
        ClientAccountCustomer::query()->updateOrCreate(
            [
                'client_account_id' => $account->id,
                'customer_id' => $customerId,
            ],
            [
                'can_send' => $canSend,
                'can_receive' => $canReceive,
                'can_view_payment' => $canViewPayment,
                'is_default_sender' => $defaultSender,
                'is_default_receiver' => $defaultReceiver,
                'is_active' => true,
            ],
        );
    }

    /**
     * @param array<int, ClientCapability> $capabilities
     */
    private function capabilities(ClientAccount $account, array $capabilities): void
    {
        foreach ($capabilities as $capability) {
            ClientAccountCapability::query()->updateOrCreate(
                [
                    'client_account_id' => $account->id,
                    'capability' => $capability,
                ],
                ['is_enabled' => true],
            );
        }
    }
}