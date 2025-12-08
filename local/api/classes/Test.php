<?php

namespace api\classes;

use Bitrix\Main\Loader;
use Bitrix\Main\Context;

class Test
{
    private static $IBLOCK_ID = 2; 

    private static function getElements($page = 1, $limit = 10, $ids = [], $code = '')
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Модуль iblock не загружен');
        }

        $filter = ['IBLOCK_ID' => self::$IBLOCK_ID, 'ACTIVE' => 'Y'];

        if (!empty($ids)) {
            $filter['ID'] = $ids;
        }

        if (!empty($code)) {
            $filter['CODE'] = $code;
        }

        $res = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            ['nPageSize' => $limit, 'iNumPage' => $page],
            ['ID', 'NAME', 'CODE', 'DATE_ACTIVE_FROM', 'DATE_CREATE', 'TIMESTAMP_X', 'ACTIVE_TO', 'SORT', 'PROPERTY_TEST_PROPERTY']
        );

        $items = [];
        while ($row = $res->Fetch()) {
            $items[] = [
                'id'            => $row['ID'],
                'code'          => $row['CODE'],
                'title'         => $row['NAME'],
                'date'          => $row['DATE_ACTIVE_FROM'] ?: null,
                'date_create'   => $row['DATE_CREATE'] ?: null,
                'timestamp_x'   => $row['TIMESTAMP_X'] ?: null,
                'active_to'     => $row['ACTIVE_TO'] ?: null,
                'sort'          => $row['SORT'],
                'test_property' => $row['PROPERTY_TEST_PROPERTY_VALUE'] ?? null
            ];
        }

        return $items;
    }

    public static function get($arRequest)
    {
        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, (int)($arRequest['limit'] ?? 10));

        $items = self::getElements($page, $limit);

        return [
            'count' => count($items),
            'items' => $items
        ];
    }

    public static function getByIds($arRequest)
    {
        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, (int)($arRequest['limit'] ?? 10));
        $ids = $arRequest['ids'] ?? [];

        $items = self::getElements($page, $limit, $ids);

        return [
            'count' => count($items),
            'items' => $items
        ];
    }

    public static function getByCode($arRequest)
    {
        $page = max(1, (int)($arRequest['page'] ?? 1));
        $limit = max(1, (int)($arRequest['limit'] ?? 10));
        $code = $arRequest['code'] ?? '';

        if (!$code) {
            throw new \Exception('Не передан код');
        }

        $items = self::getElements($page, $limit, [], $code);

        return [
            'count' => count($items),
            'items' => $items
        ];
    }
}
