<?php

namespace Legacy\Iblock;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Legacy\General\Constants;

class LearningModulesTable extends ElementTable
{
    public static function withSelect(Query $query)
    {
        $query->setSelect([
            'ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT',
            'DATE_CREATE','TIMESTAMP_X','SORT','ACTIVE'
        ]);
        return $query;
    }

    public static function withRuntimeSections(Query $query)
    {
        $query->registerRuntimeField('SECTION', new ReferenceField(
            'SECTION', SectionTable::class, ['=this.IBLOCK_SECTION_ID' => 'ref.ID']
        ));
        $query->addSelect('SECTION.NAME', 'SECTION_NAME');
        $query->addSelect('SECTION.CODE', 'SECTION_CODE');
        return $query;
    }

    public static function withFilter(Query $query, $additionalFilter = [])
    {
        $query->setFilter(array_merge(['ACTIVE' => 'Y'], $additionalFilter));
        return $query;
    }

    public static function withOrder(Query $query, $order = [])
    {
        if (empty($order)) $order = ['SORT' => 'ASC', 'ID' => 'ASC'];
        $query->setOrder($order);
        return $query;
    }

    public static function withPage(Query $query, $limit = 50, $page = 1)
    {
        $query->setLimit($limit);
        $query->setOffset(($page - 1) * $limit);
        return $query;
    }

    public static function queryCountTotal(Query $query)
    {
        $countQuery = clone $query;
        $countQuery->setSelect(['CNT' => new ExpressionField('CNT', 'COUNT(*)')]);
        $countQuery->setLimit(null);
        $countQuery->setOffset(null);
        $result = $countQuery->exec()->fetch();
        return $result['CNT'] ?? 0;
    }

    public static function getElementsList($filter = [], $select = [], $order = [], $limit = 0, $page = 1)
    {
        $finalFilter = array_merge(['IBLOCK_ID' => Constants::IB_LEARNINGMODULES,'ACTIVE'=>'Y'], $filter);
        $navParams = $limit > 0 ? ['nPageSize'=>$limit,'iNumPage'=>$page] : false;
        if (empty($select)) $select = ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'];
        $db = \CIBlockElement::GetList($order ?: ['SORT'=>'ASC','ID'=>'ASC'],$finalFilter,false,$navParams,$select);
        $result = [];
        while ($element = $db->Fetch()) $result[] = $element;
        return $result;
    }

    public static function getCount($filter = [], array $cache = [])
    {
        $finalFilter = array_merge(['IBLOCK_ID' => Constants::IB_LEARNINGMODULES,'ACTIVE'=>'Y'], $filter);
        return \CIBlockElement::GetList([], $finalFilter, []);
    }

    public static function getElementsCount($filter = [])
    {
        return self::getCount($filter);
    }

    public static function getElementsByProperty($propertyFilter = [], $additionalFilter = [], $select = [], $order = [], $limit = 0, $page = 1)
    {
        return self::getElementsList(array_merge($additionalFilter, $propertyFilter), $select, $order, $limit, $page);
    }
}