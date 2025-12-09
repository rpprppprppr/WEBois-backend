<?php

namespace Legacy\API;

use Legacy\General\Constants;
use Bitrix\Main\Loader;
use Legacy\Iblock\LearningModulesTable;

class LearningModules
{
    private static function loadIblock()
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Exception('Не удалось подключить необходимые модули');
        }
    }

    private static function requestParam($arRequest, $key)
    {
        return $arRequest[$key] ?? $_GET[$key] ?? null;
    }

    public static function get($arRequest = [])
    {
        self::loadIblock();

        $elements = LearningModulesTable::getElementsList(
            [],
            ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'],
            ['SORT'=>'ASC','ID'=>'ASC'],
            $arRequest['limit'] ?? 50,
            $arRequest['page'] ?? 1
        );

        $result = [];
        foreach ($elements as $element) {
            $element['PROPERTIES'] = self::getElementProperties($element['ID']);
            $result[] = $element;
        }

        return [
            'items' => $result,
            'total' => LearningModulesTable::getElementsCount()
        ];
    }

    public static function getById($arRequest = [])
    {
        self::loadIblock();

        $id = self::requestParam($arRequest, 'id');
        if (!$id) throw new \Exception('Не указан id модуля');

        $elements = LearningModulesTable::getElementsList(
            ['ID' => $id],
            ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'],
            ['SORT'=>'ASC','ID'=>'ASC'],
            1,
            1
        );

        if (empty($elements)) throw new \Exception('Модуль не найден');

        $element = $elements[0];
        $element['PROPERTIES'] = self::getElementProperties($element['ID']);

        return $element;
    }

    public static function getByCourse($arRequest = [])
    {
        $courseId = self::requestParam($arRequest, 'courseId');
        if (!$courseId) throw new \Exception('Не указан courseId');

        return self::getWithFilter(['PROPERTY_COURSE' => $courseId], $arRequest);
    }

    public static function getLectures($arRequest = [])
    {
        $courseId = self::requestParam($arRequest, 'courseId');

        $filter = ['PROPERTY_MODULE_TYPE' => 'Lecture'];
        if ($courseId) $filter['PROPERTY_COURSE'] = $courseId;

        return self::getWithFilter($filter, $arRequest);
    }

    public static function getTasks($arRequest = [])
    {
        $courseId = self::requestParam($arRequest, 'courseId');

        $filter = ['PROPERTY_MODULE_TYPE' => 'Task'];
        if ($courseId) $filter['PROPERTY_COURSE'] = $courseId;

        return self::getWithFilter($filter, $arRequest);
    }

    public static function getByTeacher($arRequest = [])
    {
        $teacherId = self::requestParam($arRequest, 'teacherId');
        if (!$teacherId) throw new \Exception('Не указан teacherId');

        $courses = self::getTeacherCourses($teacherId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        $courseIds = array_column($courses, 'ID');

        return self::getWithFilter(['PROPERTY_COURSE' => $courseIds], $arRequest);
    }

    public static function getTasksForGrading($arRequest = [])
    {
        $teacherId = self::requestParam($arRequest, 'teacherId');
        if (!$teacherId) throw new \Exception('Не указан teacherId');

        $courses = self::getTeacherCourses($teacherId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        $courseIds = array_column($courses, 'ID');

        return self::getWithFilter([
            'PROPERTY_COURSE' => $courseIds,
            'PROPERTY_MODULE_TYPE' => 'Task'
        ], $arRequest);
    }

    public static function getTeacherLectures($arRequest = [])
    {
        $teacherId = self::requestParam($arRequest, 'teacherId');
        if (!$teacherId) throw new \Exception('Не указан teacherId');

        $courses = self::getTeacherCourses($teacherId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        $courseIds = array_column($courses, 'ID');

        return self::getWithFilter([
            'PROPERTY_COURSE' => $courseIds,
            'PROPERTY_MODULE_TYPE' => 'Lecture'
        ], $arRequest);
    }

    public static function getByStudent($arRequest = [])
    {
        $studentId = self::requestParam($arRequest, 'studentId');
        if (!$studentId) throw new \Exception('Не указан studentId');

        $courses = self::getStudentCourses($studentId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        $courseIds = array_column($courses, 'ID');

        return self::getWithFilter(['PROPERTY_COURSE' => $courseIds], $arRequest);
    }

    public static function getAvailableTasksForStudent($arRequest = [])
    {
        $studentId = self::requestParam($arRequest, 'studentId');
        if (!$studentId) throw new \Exception('Не указан studentId');

        $courses = self::getStudentCourses($studentId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        return self::getWithFilter([
            'PROPERTY_COURSE' => array_column($courses, 'ID'),
            'PROPERTY_MODULE_TYPE' => 'Task'
        ], $arRequest);
    }

    public static function getStudentLectures($arRequest = [])
    {
        $studentId = self::requestParam($arRequest, 'studentId');
        if (!$studentId) throw new \Exception('Не указан studentId');

        $courses = self::getStudentCourses($studentId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        return self::getWithFilter([
            'PROPERTY_COURSE' => array_column($courses, 'ID'),
            'PROPERTY_MODULE_TYPE' => 'Lecture'
        ], $arRequest);
    }

    public static function getExpiredTasksForStudent($arRequest = [])
    {
        $studentId = self::requestParam($arRequest, 'studentId');
        if (!$studentId) throw new \Exception('Не указан studentId');

        $courses = self::getStudentCourses($studentId);
        if (empty($courses)) return ['items'=>[], 'total'=>0];

        return self::getWithFilter([
            'PROPERTY_COURSE' => array_column($courses, 'ID'),
            'PROPERTY_MODULE_TYPE' => 'Task',
            '<=PROPERTY_DEADLINE' => date('d.m.Y')
        ], $arRequest);
    }

    public static function getTeacherCourses($teacherId)
    {
        self::loadIblock();

        $db = \CIBlockElement::GetList(
            ['SORT'=>'ASC','NAME'=>'ASC'],
            ['IBLOCK_ID'=>Constants::IB_COURSES,'PROPERTY_TEACHER'=>$teacherId,'ACTIVE'=>'Y'],
            false,
            false,
            ['ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X']
        );

        $courses = [];
        while ($course = $db->Fetch()) {
            $courses[] = [
                'ID'=>$course['ID'],
                'NAME'=>$course['NAME'],
                'CODE'=>$course['CODE'],
                'PREVIEW_TEXT'=>$course['PREVIEW_TEXT'],
                'DETAIL_TEXT'=>$course['DETAIL_TEXT'],
                'DATE_CREATE'=>$course['DATE_CREATE'],
                'TIMESTAMP_X'=>$course['TIMESTAMP_X'],
                'PROPERTIES'=>self::getCourseProperties($course['ID']),
                'MODULES_COUNT'=>self::getCourseModulesCount($course['ID']),
                'STUDENTS_COUNT'=>self::getCourseStudentsCount($course['ID'])
            ];
        }

        return $courses;
    }

    public static function getStudentCourses($studentId)
    {
        self::loadIblock();

        $db = \CIBlockElement::GetList(
            ['SORT'=>'ASC','NAME'=>'ASC'],
            ['IBLOCK_ID'=>Constants::IB_COURSES,'PROPERTY_STUDENTS'=>$studentId,'ACTIVE'=>'Y'],
            false,
            false,
            ['ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X']
        );

        $courses = [];
        while ($course = $db->Fetch()) {
            $courses[] = [
                'ID'=>$course['ID'],
                'NAME'=>$course['NAME'],
                'CODE'=>$course['CODE'],
                'PREVIEW_TEXT'=>$course['PREVIEW_TEXT'],
                'DETAIL_TEXT'=>$course['DETAIL_TEXT'],
                'DATE_CREATE'=>$course['DATE_CREATE'],
                'TIMESTAMP_X'=>$course['TIMESTAMP_X'],
                'PROPERTIES'=>self::getCourseProperties($course['ID']),
                'MODULES_COUNT'=>self::getCourseModulesCount($course['ID']),
                'COMPLETED_MODULES'=>self::getStudentCompletedModulesCount($course['ID'], $studentId),
                'TEACHER_INFO'=>self::getCourseTeacherInfo($course['ID'])
            ];
        }

        return $courses;
    }

    private static function getCourseProperties($courseId)
    {
        $props = [];
        if (!$courseId) return $props;

        $db = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], []);
        while ($p = $db->Fetch()) {
            if (empty($p['CODE']) || empty($p['VALUE'])) continue;

            if ($p['PROPERTY_TYPE'] === 'E') {
                $el = \CIBlockElement::GetByID($p['VALUE'])->Fetch();
                $props[$p['CODE']] = [
                    'ID'=>$p['VALUE'], 'NAME'=>$el['NAME'] ?? '', 'VALUE'=>$p['VALUE']
                ];
            }
            elseif ($p['USER_TYPE'] === 'UserID') {
                $u = \CUser::GetByID($p['VALUE'])->Fetch();
                $props[$p['CODE']] = [
                    'ID'=>$p['VALUE'],
                    'NAME'=>$u['NAME'].' '.$u['LAST_NAME'],
                    'LOGIN'=>$u['LOGIN'],
                    'VALUE'=>$p['VALUE']
                ];
            }
            else {
                $props[$p['CODE']] = $p['VALUE'];
            }
        }

        return $props;
    }

    private static function getCourseModulesCount($courseId)
    {
        self::loadIblock();
        return \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID'=>Constants::IB_LEARNINGMODULES,'PROPERTY_COURSE'=>$courseId,'ACTIVE'=>'Y'],
            []
        );
    }

    private static function getCourseStudentsCount($courseId)
    {
        self::loadIblock();

        $db = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], ['CODE'=>'STUDENTS']);
        $s = 0;
        while ($p = $db->Fetch()) if (!empty($p['VALUE'])) $s++;

        return $s;
    }

    private static function getCourseTeacherInfo($courseId)
    {
        self::loadIblock();

        $db = \CIBlockElement::GetProperty(Constants::IB_COURSES, $courseId, [], ['CODE'=>'TEACHER']);
        if ($p = $db->Fetch()) {
            if (!empty($p['VALUE'])) {
                $u = \CUser::GetByID($p['VALUE'])->Fetch();
                return [
                    'ID'=>$p['VALUE'],
                    'NAME'=>$u['NAME'] ?? '',
                    'LAST_NAME'=>$u['LAST_NAME'] ?? '',
                    'EMAIL'=>$u['EMAIL'] ?? '',
                    'FULL_NAME'=>trim(($u['NAME'] ?? '').' '.($u['LAST_NAME'] ?? ''))
                ];
            }
        }
        return null;
    }

    private static function getStudentCompletedModulesCount()
    {
        return 0;
    }

    private static function getWithFilter($filter, $arRequest = [])
    {
        self::loadIblock();

        $elements = LearningModulesTable::getElementsList(
            $filter,
            ['ID','IBLOCK_ID','NAME','CODE','PREVIEW_TEXT','DETAIL_TEXT','DATE_CREATE','TIMESTAMP_X','SORT'],
            ['SORT'=>'ASC','ID'=>'ASC'],
            $arRequest['limit'] ?? 50,
            $arRequest['page'] ?? 1
        );

        $result = [];
        foreach ($elements as $element) {
            $element['PROPERTIES'] = self::getElementProperties($element['ID']);
            $result[] = $element;
        }

        return [
            'items'=>$result,
            'total'=>LearningModulesTable::getElementsCount($filter)
        ];
    }

    private static function getElementProperties($elementId)
    {
        $props = [];
        if (!$elementId) return $props;

        $db = \CIBlockElement::GetProperty(Constants::IB_LEARNINGMODULES, $elementId, [], []);
        while ($p = $db->Fetch()) {
            if (empty($p['CODE'])) continue;

            if ($p['PROPERTY_TYPE'] === 'F' && !empty($p['VALUE'])) {
                $f = \CFile::GetFileArray($p['VALUE']);
                if ($f) {
                    $props[$p['CODE']] = [
                        'ID'=>$p['VALUE'],
                        'NAME'=>$f['ORIGINAL_NAME'] ?? $f['FILE_NAME'],
                        'SRC'=>$f['SRC'],
                        'SIZE'=>self::formatFileSize($f['FILE_SIZE']),
                        'TYPE'=>$f['CONTENT_TYPE'],
                        'VALUE'=>$f['SRC']
                    ];
                }
            }
            elseif ($p['PROPERTY_TYPE'] === 'L' && !empty($p['VALUE_ENUM'])) {
                $props[$p['CODE']] = [
                    'ID'=>$p['VALUE_ENUM_ID'],
                    'NAME'=>$p['VALUE_ENUM'],
                    'VALUE'=>$p['VALUE_ENUM']
                ];
            }
            elseif ($p['PROPERTY_TYPE'] === 'E' && !empty($p['VALUE'])) {
                $el = \CIBlockElement::GetByID($p['VALUE'])->Fetch();
                $props[$p['CODE']] = [
                    'ID'=>$p['VALUE'],
                    'NAME'=>$el['NAME'] ?? '',
                    'VALUE'=>$p['VALUE']
                ];
            }
            elseif ($p['PROPERTY_TYPE'] === 'S' && !empty($p['VALUE'])) {
                $props[$p['CODE']] = is_array($p['VALUE'])
                    ? ['TEXT'=>$p['VALUE']['TEXT'], 'TYPE'=>$p['VALUE']['TYPE'] ?? 'text']
                    : ['ID'=>$p['PROPERTY_ID'], 'NAME'=>$p['VALUE'], 'VALUE'=>$p['VALUE']];
            }
            elseif ($p['PROPERTY_TYPE'] === 'N' && !empty($p['VALUE'])) {
                $props[$p['CODE']] = [
                    'ID'=>$p['PROPERTY_ID'],
                    'NAME'=>$p['VALUE'],
                    'VALUE'=>(float)$p['VALUE']
                ];
            }
            elseif (!empty($p['VALUE'])) {
                $props[$p['CODE']] = [
                    'ID'=>$p['PROPERTY_ID'],
                    'NAME'=>$p['VALUE'],
                    'VALUE'=>$p['VALUE']
                ];
            }
        }

        return $props;
    }

    public static function uploadFile($arRequest)
    {
        self::loadIblock();

        if (empty($_FILES['file'])) throw new \Exception('Файл не был загружен');

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \Exception('Ошибка загрузки: '.$file['error']);
        if ($file['size'] > 10*1024*1024) throw new \Exception('Файл больше 10MB');

        $allowed = [
            'image/jpeg','image/png','image/gif','application/pdf',
            'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain','application/zip','application/x-rar-compressed'
        ];

        if (!in_array($file['type'], $allowed)) {
            throw new \Exception('Недопустимый тип: '.$file['type']);
        }

        try {
            $fileId = \CFile::SaveFile([
                "name"=>$file['name'],
                "size"=>$file['size'],
                "tmp_name"=>$file['tmp_name'],
                "type"=>$file['type'],
                "MODULE_ID"=>"iblock"
            ], "iblock");

            if (!$fileId) throw new \Exception('Не удалось сохранить файл');

            $info = \CFile::GetFileArray($fileId);
            if (!$info) throw new \Exception('Не удалось получить информацию о файле');

            return [
                'ID'=>$fileId,
                'NAME'=>$info['ORIGINAL_NAME'],
                'SRC'=>$info['SRC'],
                'SIZE'=>self::formatFileSize($info['FILE_SIZE']),
                'TYPE'=>$info['CONTENT_TYPE']
            ];
        }
        catch (\Exception $e) {
            if (isset($fileId)) \CFile::Delete($fileId);
            throw new \Exception('Ошибка обработки файла: '.$e->getMessage());
        }
    }

    private static function formatFileSize($size)
    {
        if ($size == 0) return '0 Б';
        $u = ['Б','КБ','МБ','ГБ','ТБ'];
        $i = floor(log($size,1024));
        return round($size/pow(1024,$i),2).' '.$u[$i];
    }

    public static function add($data)
    {
        self::loadIblock();

        $el = new \CIBlockElement;
        $fields = [
            'NAME'=>$data['NAME'],
            'IBLOCK_ID'=>Constants::IB_LEARNINGMODULES,
            'PREVIEW_TEXT'=>$data['PREVIEW_TEXT'] ?? '',
            'DETAIL_TEXT'=>$data['DETAIL_TEXT'] ?? '',
            'DETAIL_TEXT_TYPE'=>$data['DETAIL_TEXT_TYPE'] ?? 'text',
        ];

        if (!empty($data['PROPERTY_VALUES'])) {
            $fields['PROPERTY_VALUES'] = $data['PROPERTY_VALUES'];
        }

        $id = $el->Add($fields);
        if (!$id) throw new \Exception($el->LAST_ERROR);

        return $id;
    }

    public static function update($id, $data)
    {
        self::loadIblock();

        $el = new \CIBlockElement;

        $fields = [
            'NAME'=>$data['NAME'] ?? '',
            'PREVIEW_TEXT'=>$data['PREVIEW_TEXT'] ?? '',
            'DETAIL_TEXT'=>$data['DETAIL_TEXT'] ?? '',
        ];

        if (!empty($data['PROPERTY_VALUES'])) {
            $fields['PROPERTY_VALUES'] = $data['PROPERTY_VALUES'];
        }

        if (!$el->Update($id, $fields)) {
            throw new \Exception($el->LAST_ERROR);
        }

        return true;
    }

    public static function delete($id)
    {
        self::loadIblock();
        if (!\CIBlockElement::Delete($id)) {
            throw new \Exception('Не удалось удалить модуль');
        }
        return true;
    }
}