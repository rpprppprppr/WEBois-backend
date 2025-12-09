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

class CoursesTable extends ElementTable
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

    public static function withRuntimeTeacher(Query $query)
    {
        $query->registerRuntimeField('TEACHER_PROP', new ReferenceField(
            'TEACHER_PROP', ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::PROP_TEACHER)]
        ));
        $query->addSelect('TEACHER_PROP.VALUE', 'TEACHER_ID');
        return $query;
    }

    public static function withRuntimeStudents(Query $query)
    {
        $query->registerRuntimeField('STUDENTS_PROP', new ReferenceField(
            'STUDENTS_PROP', ElementPropertyTable::class,
            ['=this.ID' => 'ref.IBLOCK_ELEMENT_ID', 'ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?', Constants::PROP_STUDENTS)]
        ));
        $query->addSelect('STUDENTS_PROP.VALUE', 'STUDENT_ID');
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
        $finalFilter = array_merge(['IBLOCK_ID' => Constants::IB_COURSES, 'ACTIVE' => 'Y'], $filter);
        $navParams = $limit > 0 ? ['nPageSize' => $limit, 'iNumPage' => $page] : false;
        if (empty($select)) $select = ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'];
        $db = \CIBlockElement::GetList($order ?: ['SORT' => 'ASC','ID' => 'ASC'], $finalFilter, false, $navParams, $select);
        $result = [];
        while ($element = $db->Fetch()) $result[] = $element;
        return $result;
    }

    public static function getCount($filter = [], array $cache = [])
    {
        $finalFilter = array_merge(['IBLOCK_ID' => Constants::IB_COURSES, 'ACTIVE' => 'Y'], $filter);
        return \CIBlockElement::GetList([], $finalFilter, []);
    }

    public static function getElementsCount($filter = [])
    {
        $finalFilter = array_merge(['IBLOCK_ID' => Constants::IB_COURSES, 'ACTIVE' => 'Y'], $filter);
        return \CIBlockElement::GetList([], $finalFilter, []);
    }

    public static function getTeacherCourses($teacherId)
    {
        return self::getElementsList(['PROPERTY_TEACHER' => $teacherId]);
    }

    public static function getStudentCourses($studentId)
    {
        return self::getElementsList(['PROPERTY_STUDENT' => $studentId]);
    }

    public static function getCoursesWithDetails($filter = [], $limit = 0, $page = 1)
    {
        $courses = self::getElementsList($filter, ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'], ['SORT'=>'ASC','ID'=>'ASC'], $limit, $page);
        $result = [];
        foreach ($courses as $course) {
            $courseId = $course['ID'];
            $result[] = [
                'ID' => $course['ID'],
                'NAME' => $course['NAME'],
                'CODE' => $course['CODE'],
                'PREVIEW_TEXT' => $course['PREVIEW_TEXT'],
                'DETAIL_TEXT' => $course['DETAIL_TEXT'],
                'DATE_CREATE' => $course['DATE_CREATE'],
                'TIMESTAMP_X' => $course['TIMESTAMP_X'],
                'SORT' => $course['SORT'],
                'PROPERTIES' => self::getCourseProperties($courseId),
                'MODULES_COUNT' => self::getCourseModulesCount($courseId),
                'STUDENTS_COUNT' => self::getCourseStudentsCount($courseId),
                'TEACHER_INFO' => self::getCourseTeacherInfo($courseId)
            ];
        }
        return $result;
    }

    private static function getCourseProperties($courseId)
    {
        $properties = [];
        if (!$courseId) return $properties;
        $dbProps = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], []);
        while ($prop = $dbProps->Fetch()) {
            if (!empty($prop['CODE']) && !empty($prop['VALUE'])) {
                $propertyKey = $prop['CODE'];
                $propertyValue = $prop['VALUE'];
                if (($prop['PROPERTY_TYPE'] == 'E' || $prop['USER_TYPE'] == 'UserID') && !empty($propertyValue)) {
                    if ($prop['PROPERTY_TYPE'] == 'E') {
                        $linkedElement = \CIBlockElement::GetByID($propertyValue)->Fetch();
                        $properties[$propertyKey] = ['ID'=>$propertyValue,'NAME'=>$linkedElement['NAME'] ?? '','VALUE'=>$propertyValue];
                    } else {
                        $user = \CUser::GetByID($propertyValue)->Fetch();
                        $properties[$propertyKey] = ['ID'=>$propertyValue,'NAME'=>($user['NAME'] ?? '').' '.($user['LAST_NAME'] ?? ''),'LOGIN'=>$user['LOGIN'] ?? '','VALUE'=>$propertyValue];
                    }
                } else if ($prop['CODE'] == 'STUDENT' && $prop['MULTIPLE'] == 'Y') {
                    if (!isset($properties[$propertyKey])) $properties[$propertyKey] = [];
                    $user = \CUser::GetByID($propertyValue)->Fetch();
                    $properties[$propertyKey][] = ['ID'=>$propertyValue,'NAME'=>($user['NAME'] ?? '').' '.($user['LAST_NAME'] ?? ''),'LOGIN'=>$user['LOGIN'] ?? '','VALUE'=>$propertyValue];
                } else {
                    $properties[$propertyKey] = $propertyValue;
                }
            }
        }
        return $properties;
    }

    private static function getCourseModulesCount($courseId)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) return 0;
        return \CIBlockElement::GetList([], ['IBLOCK_ID'=>Constants::IB_LEARNINGMODULES,'PROPERTY_COURSE'=>$courseId,'ACTIVE'=>'Y'], []);
    }

    private static function getCourseStudentsCount($courseId)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) return 0;
        $db = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], ['CODE'=>'STUDENT']);
        $students = [];
        while ($prop = $db->Fetch()) if (!empty($prop['VALUE'])) $students[] = $prop['VALUE'];
        return count($students);
    }

    private static function getCourseTeacherInfo($courseId)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) return null;
        $db = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], ['CODE'=>'TEACHER']);
        if ($prop = $db->Fetch() && !empty($prop['VALUE'])) {
            $user = \CUser::GetByID($prop['VALUE'])->Fetch();
            return [
                'ID'=>$prop['VALUE'],
                'NAME'=>$user['NAME'] ?? '',
                'LAST_NAME'=>$user['LAST_NAME'] ?? '',
                'EMAIL'=>$user['EMAIL'] ?? '',
                'FULL_NAME'=>trim(($user['NAME'] ?? '').' '.($user['LAST_NAME'] ?? ''))
            ];
        }
        return null;
    }
}