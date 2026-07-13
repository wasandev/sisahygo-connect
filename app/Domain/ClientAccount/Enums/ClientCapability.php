<?php

namespace App\Domain\ClientAccount\Enums;

enum ClientCapability: string
{
    case ShipmentView = 'shipment.view';
    case ShipmentExport = 'shipment.export';
    case ShipmentHistory = 'shipment.history';
    case PaymentView = 'payment.view';
    case PaymentDownload = 'payment.download';
    case OrderCreate = 'order.create';
    case OrderBulk = 'order.bulk';
    case SettingsManage = 'settings.manage';
    case UsersManage = 'users.manage';
}