<?php

namespace Legacy\API;

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Legacy\General\DataProcessor;
use Legacy\General\Constants;
use Legacy\Iblock\PromotionsTable;

class Promotions
{
    private static function decodeJson($value)
    {
        return Json::decode($value ?? '{}');
    }

    private static function processData($query)
    {
        $result = [];

        while ($arr = $query->fetch()) {
            $description = self::decodeJson($arr['PREVIEW_DESCRIPTION_VALUE']);
            $result[] = [
                'id' => $arr['ID'],
                'code' => $arr['CODE'],
                'image' => getFilePath($arr['IMAGE_VALUE']),
                'title' => $arr['TITLE_VALUE'],
                'badge' => $arr['BADGE_VALUE'],
                'description' => $description['blocks'][0]['value'] ?? null,
            ];
        }

        return $result;
    }

    private static function processDetailData($query)
    {
        $arr = $query->fetch();
        if (!$arr) {
            return [];
        }

        $detail = self::decodeJson($arr['DETAIL_CONTENT_VALUE']);

        $result = [
            'id' => $arr['ID'],
            'code' => $arr['CODE'],
            'date' => $arr['ACTIVE_FROM'] ? $arr['ACTIVE_FROM']->format('c') : null,
            'image' => getFilePath($arr['IMAGE_VALUE']),
            'title' => $arr['TITLE_VALUE'],
            'badge' => $arr['BADGE_VALUE'],
            'content' => $detail['blocks'] ?? [],
        ];

        foreach ($result['content'] as &$item) {
            if (
                ($item['name'] ?? null) === 'iblock_elements' &&
                ($item['iblock_id'] ?? null) == Constants::IB_PROMOCODES
            ) {
                $item['name'] = 'promocodes';
                unset($item['iblock_id'], $item['element_ids']);
            }
        }

        return array_change_key_case_recursive($result);
    }

    private static function loadIblock()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Не удалось подключить модуль iblock');
        }
    }

    public static function get($arRequest)
    {
        self::loadIblock();

        $page = (int)($arRequest['page'] ?? 1);
        $limit = (int)($arRequest['limit'] ?? 20);

        $q = PromotionsTable::query()
            ->countTotal(true)
            ->withSelect()
            ->setLimit($limit)
            ->withPage($page)
            ->withOrderByDate('DESC')
            ->withDateActive()
            ->exec();

        return [
            'count' => $q->getCount(),
            'items' => self::processData($q),
        ];
    }

    public static function getByIds($arRequest)
    {
        self::loadIblock();

        $ids = $arRequest['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $q = PromotionsTable::query()
            ->withSelect()
            ->withFilterByIDs($ids)
            ->exec();

        $result = self::processData($q);

        return DataProcessor::sortResultByIDs($result, $ids);
    }

    public static function getByCode($arRequest)
    {
        self::loadIblock();

        $code = $arRequest['code'] ?? null;
        if (!$code) {
            throw new \Exception('Не передан код новости');
        }

        $q = PromotionsTable::query()
            ->withDetailSelect()
            ->withFilterByСode($code);

        return self::processDetailData($q);
    }
}
