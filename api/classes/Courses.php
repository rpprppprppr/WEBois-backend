<?php

namespace Legacy\API;

use Legacy\General\Constants;
use Bitrix\Main\Loader;
use Legacy\Iblock\CoursesTable;

class Courses
{
    public static function get($arRequest = [])
    {
        if (Loader::includeModule('iblock')) {
            $elements = CoursesTable::getCoursesWithDetails(
                [],
                $arRequest['limit'] ?? 50,
                $arRequest['page'] ?? 1
            );
            
            $total = CoursesTable::getElementsCount();
            
            return [
                'items' => $elements,
                'total' => $total
            ];
        }
        throw new \Exception('Не удалось подключить необходимые модули');
    }

    public static function getByTeacher($arRequest = [])
    {
        $teacherId = $arRequest['teacherId'] ?? $_GET['teacherId'] ?? null;
        
        if (!$teacherId) {
            throw new \Exception('Не указан teacherId');
        }

        if (Loader::includeModule('iblock')) {
            $elements = CoursesTable::getCoursesWithDetails(
                ['PROPERTY_TEACHER' => $teacherId],
                $arRequest['limit'] ?? 50,
                $arRequest['page'] ?? 1
            );
            
            $total = CoursesTable::getElementsCount(['PROPERTY_TEACHER' => $teacherId]);
            
            return [
                'items' => $elements,
                'total' => $total
            ];
        }
        throw new \Exception('Не удалось подключить необходимые модули');
    }

public static function getByStudent($arRequest = [])
{
    $studentId = $arRequest['studentId'] ?? $_GET['studentId'] ?? null;
    
    if (!$studentId) {
        throw new \Exception('Не указан studentId');
    }

    if (Loader::includeModule('iblock')) {
        $elements = CoursesTable::getCoursesWithDetails(
            ['PROPERTY_STUDENT' => $studentId],
            $arRequest['limit'] ?? 50,
            $arRequest['page'] ?? 1
        );
        
        $total = CoursesTable::getElementsCount(['PROPERTY_STUDENT' => $studentId]);
        
        return [
            'items' => $elements,
            'total' => $total
        ];
    }
    throw new \Exception('Не удалось подключить необходимые модули');
}

    public static function getById($arRequest = [])
    {
        $courseId = $arRequest['id'] ?? $_GET['id'] ?? null;
        
        if (!$courseId) {
            throw new \Exception('Не указан id курса');
        }

        if (Loader::includeModule('iblock')) {
            $courses = CoursesTable::getCoursesWithDetails(['ID' => $courseId]);
            
            if (empty($courses)) {
                throw new \Exception('Курс не найден');
            }
            
            return $courses[0];
        }
        throw new \Exception('Не удалось подключить необходимые модули');
    }

    public static function getTeacherCourses($teacherId)
    {
        return CoursesTable::getTeacherCourses($teacherId);
    }

    public static function getStudentCourses($studentId)
    {
        return CoursesTable::getStudentCourses($studentId);
    }
public static function add($data)
{
    if (Loader::includeModule('iblock')) {
        $el = new \CIBlockElement;
        
        $fields = [
            'NAME' => $data['NAME'],
            'IBLOCK_ID' => Constants::IB_COURSES,
            'PREVIEW_TEXT' => $data['PREVIEW_TEXT'] ?? '',
            'DETAIL_TEXT' => $data['DETAIL_TEXT'] ?? '',
            'PROPERTY_VALUES' => []
        ];
        
        if (isset($data['PROPERTIES'])) {
            foreach ($data['PROPERTIES'] as $propertyCode => $propertyValue) {
                if ($propertyCode === 'STUDENTS' && is_array($propertyValue)) {
                    $fields['PROPERTY_VALUES'][$propertyCode] = [];
                    foreach ($propertyValue as $index => $studentId) {
                        $fields['PROPERTY_VALUES'][$propertyCode]["n{$index}"] = $studentId;
                    }
                } else {
                    $fields['PROPERTY_VALUES'][$propertyCode] = $propertyValue;
                }
            }
        }
        
        \Bitrix\Main\Diag\Debug::writeToFile($fields, 'DEBUG: Course fields before add');
        
        $id = $el->Add($fields);
        
        if ($id) {
            return $id;
        } else {
            throw new \Exception($el->LAST_ERROR);
        }
    }
    throw new \Exception('Не удалось подключить модуль iblock');
}
    public static function update($id, $data)
    {
        if (Loader::includeModule('iblock')) {
            $el = new \CIBlockElement;
            
            $fields = [
                'NAME' => $data['NAME'] ?? '',
                'PREVIEW_TEXT' => $data['PREVIEW_TEXT'] ?? '',
                'DETAIL_TEXT' => $data['DETAIL_TEXT'] ?? '',
            ];
            
            if (!empty($data['PROPERTIES'])) {
                $fields['PROPERTY_VALUES'] = $data['PROPERTIES'];
            }
            
            $result = $el->Update($id, $fields);
            
            if (!$result) {
                throw new \Exception($el->LAST_ERROR);
            }
            
            return $result;
        }
        throw new \Exception('Не удалось подключить модуль iblock');
    }

    public static function delete($id)
    {
        if (Loader::includeModule('iblock')) {
            $result = \CIBlockElement::Delete($id);
            
            if ($result) {
                return true;
            } else {
                throw new \Exception('Не удалось удалить курс');
            }
        }
        throw new \Exception('Не удалось подключить модуль iblock');
    }

    /**
     * Добавить студента к курсу
     */
    public static function addStudent($courseId, $studentId)
    {
        if (Loader::includeModule('iblock')) {
            \CIBlockElement::SetPropertyValuesEx(
                $courseId,
                Constants::IB_COURSES,
                ['STUDENTS' => $studentId]
            );
            
            return true;
        }
        throw new \Exception('Не удалось подключить модуль iblock');
    }

    /**
     * Удалить студента из курса
     */
    public static function removeStudent($courseId, $studentId)
    {
        if (Loader::includeModule('iblock')) {
            // Получаем текущих студентов
            $dbProps = \CIBlockElement::GetProperty(
                Constants::IB_COURSES,
                $courseId,
                [],
                ['CODE' => 'STUDENTS']
            );
            
            $students = [];
            while ($prop = $dbProps->Fetch()) {
                if ($prop['VALUE'] != $studentId) {
                    $students[] = $prop['VALUE'];
                }
            }
            
            \CIBlockElement::SetPropertyValuesEx(
                $courseId,
                Constants::IB_COURSES,
                ['STUDENTS' => $students]
            );
            
            return true;
        }
        throw new \Exception('Не удалось подключить модуль iblock');
    }
}