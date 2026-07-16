<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderChecking extends Component
{
    public string $receiverSearch = '';

    public ?string $selectedReceiverId = null;

    public string $clientReferenceNo = 'OC-20260716-001';

    public string $orderRemark = '';

    public array $items = [
        [
            'product' => 'กล่องเอกสาร',
            'unit' => 'กล่อง',
            'quantity' => 2,
            'remark' => 'เอกสารฝ่ายบัญชี',
        ],
    ];

    public array $newItem = [
        'product' => '',
        'unit' => 'ชิ้น',
        'quantity' => 1,
        'remark' => '',
    ];

    public bool $showSuccessDialog = false;

    public function mount(): void
    {
        $this->selectedReceiverId = $this->mockReceivers()[0]['id'];
        $this->receiverSearch = $this->mockReceivers()[0]['name'];
    }

    public function updatedReceiverSearch(): void
    {
        if ($this->selectedReceiverId && $this->selectedReceiver?->name !== $this->receiverSearch) {
            $this->selectedReceiverId = null;
        }
    }

    public function selectReceiver(string $receiverId): void
    {
        $receiver = collect($this->mockReceivers())->firstWhere('id', $receiverId);

        if (! $receiver) {
            return;
        }

        $this->selectedReceiverId = $receiver['id'];
        $this->receiverSearch = $receiver['name'];
        $this->resetErrorBag('selectedReceiverId');
    }

    public function addProduct(string $productName): void
    {
        $product = collect($this->mockProducts())->firstWhere('name', $productName);

        if (! $product) {
            return;
        }

        $this->items[] = [
            'product' => $product['name'],
            'unit' => $product['unit'],
            'quantity' => 1,
            'remark' => '',
        ];

        $this->resetErrorBag('items');
    }

    public function addItem(): void
    {
        $this->validateOnlyNewItem();

        $this->items[] = [
            'product' => trim($this->newItem['product']),
            'unit' => trim($this->newItem['unit']),
            'quantity' => (float) $this->newItem['quantity'],
            'remark' => trim($this->newItem['remark']),
        ];

        $this->newItem = [
            'product' => '',
            'unit' => 'ชิ้น',
            'quantity' => 1,
            'remark' => '',
        ];

        foreach (['items', 'newItem.product', 'newItem.unit', 'newItem.quantity'] as $field) {
            $this->resetErrorBag($field);
        }
    }

    public function removeItem(int $index): void
    {
        if (! array_key_exists($index, $this->items)) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function confirmMockOrder(): void
    {
        $this->validate();

        $this->showSuccessDialog = true;
    }

    public function closeSuccessDialog(): void
    {
        $this->showSuccessDialog = false;
    }

    public function getFilteredReceiversProperty(): array
    {
        $query = trim($this->receiverSearch);

        if (mb_strlen($query) < 2) {
            return [];
        }

        return collect($this->mockReceivers())
            ->filter(fn (array $receiver): bool => str_contains(mb_strtolower($receiver['name'].' '.$receiver['branch'].' '.$receiver['phone']), mb_strtolower($query)))
            ->values()
            ->all();
    }

    public function getSelectedReceiverProperty(): ?object
    {
        $receiver = collect($this->mockReceivers())->firstWhere('id', $this->selectedReceiverId);

        return $receiver ? (object) $receiver : null;
    }

    public function getTotalQuantityProperty(): float
    {
        return collect($this->items)->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0));
    }

    public function getReadyForReviewProperty(): bool
    {
        return $this->selectedReceiverId
            && trim($this->clientReferenceNo) !== ''
            && count($this->items) > 0
            && collect($this->items)->every(fn (array $item): bool => trim((string) Arr::get($item, 'product')) !== ''
                && trim((string) Arr::get($item, 'unit')) !== ''
                && (float) Arr::get($item, 'quantity') > 0);
    }

    public function render(): View
    {
        return view('livewire.order-checking', [
            'mockProducts' => $this->mockProducts(),
            'mockSender' => $this->mockSender(),
        ])->layout('layouts.app', [
            'title' => __('navigation.order_checking'),
        ]);
    }

    protected function rules(): array
    {
        return [
            'selectedReceiverId' => ['required'],
            'clientReferenceNo' => ['required', 'string', 'max:40'],
            'orderRemark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product' => ['required', 'string', 'max:120'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.remark' => ['nullable', 'string', 'max:160'],
        ];
    }

    protected function messages(): array
    {
        return __('order_checking.validation');
    }

    private function validateOnlyNewItem(): void
    {
        $validator = validator($this->newItem, [
            'product' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'remark' => ['nullable', 'string', 'max:160'],
        ], [
            'product.required' => __('order_checking.validation.new_item_product_required'),
            'unit.required' => __('order_checking.validation.new_item_unit_required'),
            'quantity.required' => __('order_checking.validation.new_item_quantity_required'),
            'quantity.gt' => __('order_checking.validation.new_item_quantity_gt'),
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())
                    ->mapWithKeys(fn (array $messages, string $key): array => ["newItem.{$key}" => $messages])
                    ->all()
            );
        }
    }

    private function mockSender(): array
    {
        return [
            'name' => 'ABC Company',
            'code' => 'ABC-BKK',
            'branch' => __('order_checking.mock.sender_branch'),
        ];
    }

    private function mockReceivers(): array
    {
        return [
            [
                'id' => 'receiver-siam-sample',
                'name' => 'บริษัท สยามตัวอย่าง จำกัด',
                'branch' => 'สำนักงานใหญ่ - บางนา',
                'phone' => '02-555-0198',
                'tag' => __('order_checking.mock.receiver_frequent'),
            ],
            [
                'id' => 'receiver-north-warehouse',
                'name' => 'หจก. คลังเหนือ',
                'branch' => 'คลังสินค้า - เชียงใหม่',
                'phone' => '053-555-204',
                'tag' => __('order_checking.mock.receiver_available'),
            ],
            [
                'id' => 'receiver-east-retail',
                'name' => 'บริษัท อีสต์รีเทล จำกัด',
                'branch' => 'ศูนย์กระจายสินค้า - ชลบุรี',
                'phone' => '038-555-782',
                'tag' => __('order_checking.mock.receiver_available'),
            ],
        ];
    }

    private function mockProducts(): array
    {
        return [
            ['name' => 'กล่องเอกสาร', 'unit' => 'กล่อง', 'meta' => __('order_checking.mock.product_document_box')],
            ['name' => 'อะไหล่เครื่องจักร', 'unit' => 'ชิ้น', 'meta' => __('order_checking.mock.product_machine_part')],
            ['name' => 'วัสดุแพ็กสินค้า', 'unit' => 'แพ็ก', 'meta' => __('order_checking.mock.product_packaging')],
            ['name' => 'ตัวอย่างสินค้า', 'unit' => 'ชุด', 'meta' => __('order_checking.mock.product_sample')],
        ];
    }
}
