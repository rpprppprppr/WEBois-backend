<?php

namespace Legacy\HighLoadBlock;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\Query;
use Legacy\General\Constants;

class SubmissionsTable
{
    public static function getList($filter = [], $select = ['*'], $order = ['ID' => 'DESC'], $limit = 50, $offset = 0)
    {
        if (!Loader::includeModule('highloadblock')) return [];
        $entity = self::getEntity();
        if (!$entity) return [];
        $entityClass = $entity->getDataClass();
        try {
            $query = new Query($entityClass);
            $query->setSelect($select);
            $query->setFilter(self::convertFilter($filter));
            $query->setOrder($order);
            if ($limit > 0) $query->setLimit($limit);
            if ($offset > 0) $query->setOffset($offset);
            $db = $query->exec();
            $result = [];
            while ($item = $db->fetch()) $result[] = $item;
            return $result;
        } catch (\Exception $e) {
            error_log("SubmissionsTable::getList error: " . $e->getMessage());
            return [];
        }
    }

    private static function convertFilter($filter)
    {
        $converted = [];
        foreach ($filter as $key => $value) {
            if (strpos($key, 'UF_') === 0 || $key === 'ID') $converted[$key] = $value;
        }
        return $converted;
    }

    public static function getById($id)
    {
        $items = self::getList(['ID' => (int)$id], ['*'], [], 1);
        return !empty($items) ? $items[0] : null;
    }

    public static function getByStudentAndLesson($studentId, $lessonId)
    {
        $items = self::getList(['UF_USER_ID' => (int)$studentId, 'UF_LESSON_ID' => (int)$lessonId], ['*'], [], 1);
        return !empty($items) ? $items[0] : null;
    }

    public static function getByStudent($studentId, $filter = [], $order = ['ID' => 'DESC'], $limit = 50, $offset = 0)
    {
        $filter['UF_USER_ID'] = (int)$studentId;
        return self::getList($filter, ['*'], $order, $limit, $offset);
    }

    public static function getByLesson($lessonId, $filter = [], $order = ['ID' => 'DESC'], $limit = 50, $offset = 0)
    {
        $filter['UF_LESSON_ID'] = (int)$lessonId;
        return self::getList($filter, ['*'], $order, $limit, $offset);
    }

    public static function getForGrading($courseIds, $filter = [], $order = ['UF_DATE_SUBMITTED' => 'ASC'], $limit = 50, $offset = 0)
    {
        if (!Loader::includeModule('highloadblock') || !Loader::includeModule('iblock')) return [];
        $lessonIds = self::getLessonsFromCourses($courseIds);
        if (empty($lessonIds)) return [];
        $filter['UF_STATUS'] = 'На проверке';
        $filter['UF_LESSON_ID'] = $lessonIds;
        return self::getList($filter, ['*'], $order, $limit, $offset);
    }

    private static function getLessonsFromCourses($courseIds)
    {
        if (empty($courseIds)) return [];
        $db = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => Constants::IB_LEARNINGMODULES,
                'PROPERTY_COURSE' => $courseIds,
                'PROPERTY_MODULE_TYPE_VALUE' => 'Task',
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID']
        );
        $lessonIds = [];
        while ($lesson = $db->Fetch()) $lessonIds[] = (int)$lesson['ID'];
        return $lessonIds;
    }

    public static function getWithDetails($filter = [], $order = ['ID' => 'DESC'], $limit = 50, $offset = 0)
    {
        $submissions = self::getList($filter, ['*'], $order, $limit, $offset);
        $result = [];
        foreach ($submissions as $submission) {
            $result[] = array_merge($submission, [
                'STUDENT_INFO' => self::getStudentInfo($submission['UF_USER_ID']),
                'LESSON_INFO' => self::getLessonInfo($submission['UF_LESSON_ID'])
            ]);
        }
        return $result;
    }

    public static function add($data)
    {
        if (!Loader::includeModule('highloadblock')) throw new \Exception('Не удалось подключить модуль highloadblock');
        $entity = self::getEntity();
        if (!$entity) throw new \Exception('Не удалось получить entity HL-блока');
        $entityClass = $entity->getDataClass();
        $preparedData = self::prepareData($data);
        if (empty($preparedData['UF_DATE_SUBMITTED'])) $preparedData['UF_DATE_SUBMITTED'] = new \Bitrix\Main\Type\DateTime();
        if (empty($preparedData['UF_STATUS'])) $preparedData['UF_STATUS'] = 'На проверке';
        try {
            $result = $entityClass::add($preparedData);
            if ($result->isSuccess()) return $result->getId();
            throw new \Exception("Ошибка добавления: " . implode(', ', $result->getErrorMessages()));
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при добавлении сдачи: ' . $e->getMessage());
        }
    }

    private static function prepareData($data)
    {
        $prepared = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'UF_') === 0) {
                if (in_array($key, ['UF_USER_ID', 'UF_LESSON_ID', 'UF_FILE_ID', 'UF_SCORE', 'UF_DOWNLOAD_COUNT'])) {
                    $prepared[$key] = (int)$value;
                } else if ($key === 'UF_DATE_SUBMITTED' && is_string($value)) {
                    $prepared[$key] = new \Bitrix\Main\Type\DateTime($value);
                } else {
                    $prepared[$key] = $value;
                }
            }
        }
        return $prepared;
    }

    public static function update($id, $data)
    {
        if (!Loader::includeModule('highloadblock')) throw new \Exception('Не удалось подключить модуль highloadblock');
        $entity = self::getEntity();
        if (!$entity) throw new \Exception('Не удалось получить entity HL-блока');
        $entityClass = $entity->getDataClass();
        $preparedData = self::prepareData($data);
        try {
            $result = $entityClass::update((int)$id, $preparedData);
            if ($result->isSuccess()) return true;
            throw new \Exception("Ошибка обновления: " . implode(', ', $result->getErrorMessages()));
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при обновлении сдачи: ' . $e->getMessage());
        }
    }

    public static function delete($id)
    {
        if (!Loader::includeModule('highloadblock')) throw new \Exception('Не удалось подключить модуль highloadblock');
        $entity = self::getEntity();
        if (!$entity) throw new \Exception('Не удалось получить entity HL-блока');
        $entityClass = $entity->getDataClass();
        try {
            $result = $entityClass::delete((int)$id);
            if ($result->isSuccess()) return true;
            throw new \Exception(implode(', ', $result->getErrorMessages()));
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при удалении сдачи: ' . $e->getMessage());
        }
    }

    public static function getCount($filter = [])
    {
        return count(self::getList($filter, ['ID'], [], 0));
    }

    public static function exists($studentId, $lessonId)
    {
        $items = self::getList(['UF_USER_ID' => (int)$studentId, 'UF_LESSON_ID' => (int)$lessonId], ['ID'], [], 1);
        return !empty($items);
    }

    private static function getStudentInfo($studentId)
    {
        if (!Loader::includeModule('main')) return null;
        $user = \CUser::GetByID($studentId)->Fetch();
        if ($user) {
            return [
                'ID' => $user['ID'],
                'NAME' => $user['NAME'],
                'LAST_NAME' => $user['LAST_NAME'],
                'EMAIL' => $user['EMAIL'],
                'FULL_NAME' => trim($user['NAME'] . ' ' . $user['LAST_NAME'])
            ];
        }
        return null;
    }

    private static function getLessonInfo($lessonId)
    {
        if (!Loader::includeModule('iblock')) return null;
        $element = \CIBlockElement::GetByID($lessonId)->Fetch();
        if ($element) return ['ID' => $element['ID'], 'NAME' => $element['NAME'], 'CODE' => $element['CODE']];
        return null;
    }

    public static function getEntity()
    {
        if (!Loader::includeModule('highloadblock')) return null;
        try {
            $hlblock = HighloadBlockTable::getById(Constants::HLBLOCK_SUBMISSIONS)->fetch();
            if ($hlblock) return HighloadBlockTable::compileEntity($hlblock);
        } catch (\Exception $e) {
            error_log("SubmissionsTable::getEntity - Error: " . $e->getMessage());
        }
        return null;
    }
}