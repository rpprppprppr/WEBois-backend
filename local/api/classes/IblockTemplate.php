<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Legacy\General\Constants;
use Legacy\Iblock\IblockElementTable;

class IblockTemplate
{
    public static function getElement($arRequest)
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Не удалось подключить модуль iblock');
        }

        $query = IblockElementTable::query()
            ->withSelect()
            ->addFilter('1', Constants::IB_TEMPLATE)
            ->withFilter()
            ->withOrder()
            ->withPage($arRequest['limit'] ?? 20, $arRequest['page'] ?? 1);

        $count = $query->queryCountTotal();
        $db = $query->exec();

        $result = [];
        while ($row = $db->fetch()) {
            $result[] = $row;
        }

        return [
            'items' => $result,
            'total' => $count
        ];
    }
}
