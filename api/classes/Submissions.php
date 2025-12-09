<?php

namespace Legacy\API;

use Legacy\General\Constants;
use Legacy\HighLoadBlock\SubmissionsTable;
use Bitrix\Main\Loader;

class Submissions
{
    public static function get($arRequest = [])
    {
        try {
            if (Loader::includeModule('highloadblock')) {
                $items = SubmissionsTable::getList(
                    $arRequest['filter'] ?? [],
                    ['*'],
                    $arRequest['order'] ?? ['ID' => 'DESC'],
                    $arRequest['limit'] ?? 50,
                    $arRequest['offset'] ?? 0
                );
                $total = SubmissionsTable::getCount($arRequest['filter'] ?? []);
                return ['items' => $items, 'total' => $total];
            }
            throw new \Exception('Не удалось подключить модуль highloadblock');
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдач: ' . $e->getMessage());
        }
    }

    public static function getById($arRequest = [])
    {
        try {
            $id = $arRequest['id'] ?? $_GET['id'] ?? null;
            if (!$id) throw new \Exception('Не указан id сдачи');
            $submission = SubmissionsTable::getById($id);
            if (!$submission) throw new \Exception('Сдача не найдена');
            return $submission;
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдачи: ' . $e->getMessage());
        }
    }

    public static function getByStudent($arRequest = [])
    {
        try {
            $studentId = $arRequest['studentId'] ?? $_GET['studentId'] ?? null;
            if (!$studentId) throw new \Exception('Не указан studentId');
            $items = SubmissionsTable::getByStudent(
                $studentId,
                $arRequest['filter'] ?? [],
                $arRequest['order'] ?? ['ID' => 'DESC'],
                $arRequest['limit'] ?? 50,
                $arRequest['offset'] ?? 0
            );
            $total = SubmissionsTable::getCount(['UF_USER_ID' => $studentId]);
            return ['items' => $items, 'total' => $total];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдач студента: ' . $e->getMessage());
        }
    }

    public static function getByLesson($arRequest = [])
    {
        try {
            $lessonId = $arRequest['lessonId'] ?? $_GET['lessonId'] ?? null;
            if (!$lessonId) throw new \Exception('Не указан lessonId');
            $items = SubmissionsTable::getByLesson(
                $lessonId,
                $arRequest['filter'] ?? [],
                $arRequest['order'] ?? ['ID' => 'DESC'],
                $arRequest['limit'] ?? 50,
                $arRequest['offset'] ?? 0
            );
            $total = SubmissionsTable::getCount(['UF_LESSON_ID' => $lessonId]);
            return ['items' => $items, 'total' => $total];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдач по заданию: ' . $e->getMessage());
        }
    }

    public static function getForGrading($arRequest = [])
    {
        try {
            $teacherId = $arRequest['teacherId'] ?? $_GET['teacherId'] ?? null;
            if (!$teacherId) throw new \Exception('Не указан teacherId');
            $teacherCourses = LearningModules::getTeacherCourses($teacherId);
            if (empty($teacherCourses)) return ['items' => [], 'total' => 0];
            $courseIds = array_column($teacherCourses, 'ID');
            $items = SubmissionsTable::getForGrading(
                $courseIds,
                $arRequest['filter'] ?? [],
                $arRequest['order'] ?? ['UF_DATE_SUBMITTED' => 'ASC'],
                $arRequest['limit'] ?? 50,
                $arRequest['offset'] ?? 0
            );
            $total = SubmissionsTable::getCount(['UF_STATUS' => 'На проверке']);
            return ['items' => $items, 'total' => $total];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдач для проверки: ' . $e->getMessage());
        }
    }

    public static function getWithDetails($arRequest = [])
    {
        try {
            if (Loader::includeModule('highloadblock')) {
                $items = SubmissionsTable::getWithDetails(
                    $arRequest['filter'] ?? [],
                    $arRequest['order'] ?? ['ID' => 'DESC'],
                    $arRequest['limit'] ?? 50,
                    $arRequest['offset'] ?? 0
                );
                $total = SubmissionsTable::getCount($arRequest['filter'] ?? []);
                return ['items' => $items, 'total' => $total];
            }
            throw new \Exception('Не удалось подключить модуль highloadblock');
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении сдач с деталями: ' . $e->getMessage());
        }
    }

    public static function uploadFile($arRequest = [])
    {
        if (!Loader::includeModule('iblock')) throw new \Exception('Не удалось подключить модуль iblock');
        if (empty($_FILES['file'])) throw new \Exception('Файл не был загружен');
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \Exception('Ошибка загрузки файла: ' . $file['error']);
        if ($file['size'] > 10 * 1024 * 1024) throw new \Exception('Размер файла не должен превышать 10MB');
        $allowedTypes = [
            'image/jpeg','image/png','image/gif','application/pdf',
            'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain','application/zip','application/x-rar-compressed','application/x-7z-compressed'
        ];
        if (!in_array($file['type'], $allowedTypes)) throw new \Exception('Недопустимый тип файла: ' . $file['type']);
        try {
            $fileArray = [
                "name"=>$file['name'],
                "size"=>$file['size'],
                "tmp_name"=>$file['tmp_name'],
                "type"=>$file['type'],
                "MODULE_ID"=>"main"
            ];
            $fileId = \CFile::SaveFile($fileArray, "lms/submissions");
            if (!$fileId) throw new \Exception('Не удалось сохранить файл');
            $fileInfo = \CFile::GetFileArray($fileId);
            if (!$fileInfo) throw new \Exception('Не удалось получить информацию о файле');
            return [
                'ID'=>$fileId,
                'NAME'=>$fileInfo['ORIGINAL_NAME'],
                'SRC'=>$fileInfo['SRC'],
                'SIZE'=>self::formatFileSize($fileInfo['FILE_SIZE']),
                'TYPE'=>$fileInfo['CONTENT_TYPE']
            ];
        } catch (\Exception $e) {
            if (isset($fileId)) \CFile::Delete($fileId);
            throw new \Exception('Ошибка обработки файла: ' . $e->getMessage());
        }
    }

    public static function add($arRequest = [])
    {
        try {
            $data = $arRequest;
            $requiredFields = ['UF_USER_ID','UF_LESSON_ID'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) throw new \Exception("Не указано обязательное поле: {$field}");
            }
            $submissionData = [
                'UF_USER_ID'=>(int)$data['UF_USER_ID'],
                'UF_LESSON_ID'=>(int)$data['UF_LESSON_ID'],
                'UF_STATUS'=>(int)$data['UF_STATUS'],
                'UF_DATE_SUBMITTED'=>new \Bitrix\Main\Type\DateTime()
            ];
            if (!empty($data['UF_ANSWER_TEXT'])) $submissionData['UF_ANSWER_TEXT']=trim($data['UF_ANSWER_TEXT']);
            if (!empty($data['UF_LINK'])) $submissionData['UF_LINK']=trim($data['UF_LINK']);
            if (!empty($data['UF_FILE_ID'])) {
                $fileInfo = \CFile::GetFileArray($data['UF_FILE_ID']);
                if ($fileInfo) $submissionData['UF_FILE_ID']=[
                    'name'=>$fileInfo['ORIGINAL_NAME'],
                    'type'=>$fileInfo['CONTENT_TYPE'],
                    'tmp_name'=>$_SERVER['DOCUMENT_ROOT'].$fileInfo['SRC'],
                    'error'=>0,
                    'size'=>$fileInfo['FILE_SIZE'],
                    'MODULE_ID'=>'main'
                ];
            }
            if (!Loader::includeModule('highloadblock')) throw new \Exception('Модуль highloadblock не подключен');
            $entity = \Legacy\HighLoadBlock\SubmissionsTable::getEntity();
            if (!$entity) throw new \Exception('Не удалось получить entity HL-блока');
            $entityClass = $entity->getDataClass();
            $existingSubmission = \Legacy\HighLoadBlock\SubmissionsTable::getByStudentAndLesson(
                (int)$data['UF_USER_ID'],
                (int)$data['UF_LESSON_ID']
            );
            if ($existingSubmission) {
                $result = $entityClass::update($existingSubmission['ID'],$submissionData);
                if ($result->isSuccess()) return ['id'=>$existingSubmission['ID'],'message'=>'Сдача обновлена'];
                throw new \Exception("Ошибка обновления: ".implode(', ',$result->getErrorMessages()));
            } else {
                $result = $entityClass::add($submissionData);
                if ($result->isSuccess()) return ['id'=>$result->getId(),'message'=>'Сдача успешно создана'];
                throw new \Exception("Ошибка создания: ".implode(', ',$result->getErrorMessages()));
            }
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при добавлении сдачи: '.$e->getMessage());
        }
    }

    public static function update($arRequest = [])
    {
        try {
            $id = $arRequest['id'] ?? null;
            $data = $arRequest['data'] ?? $arRequest;
            if (!$id || empty($data)) throw new \Exception('Не указан id сдачи или данные для обновления');
            $updateData = [];
            foreach ($data as $key=>$value) {
                if (strpos($key,'UF_')===0) {
                    if (in_array($key,['UF_USER_ID','UF_LESSON_ID','UF_FILE_ID','UF_SCORE','UF_DOWNLOAD_COUNT'])) $updateData[$key]=(int)$value;
                    else if ($key==='UF_STATUS') $updateData[$key]=is_numeric($value)?(int)$value:$value;
                    else $updateData[$key]=$value;
                }
            }
            $success = \Legacy\HighLoadBlock\SubmissionsTable::update((int)$id,$updateData);
            if ($success) return ['success'=>true,'message'=>'Сдача успешно обновлена','id'=>$id];
            throw new \Exception('Ошибка обновления сдачи');
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при обновлении сдачи: '.$e->getMessage());
        }
    }

    public static function delete($arRequest = [])
    {
        try {
            $id = $arRequest['id'] ?? null;
            if (!$id) throw new \Exception('Не указан id сдачи');
            $submission = SubmissionsTable::getById($id);
            if ($submission && !empty($submission['UF_FILE_ID'])) \CFile::Delete($submission['UF_FILE_ID']);
            $result = SubmissionsTable::delete($id);
            if ($result->isSuccess()) return ['success'=>true,'message'=>'Сдача успешно удалена'];
            throw new \Exception(implode(', ',$result->getErrorMessages()));
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при удалении сдачи: '.$e->getMessage());
        }
    }

    public static function gradeSubmission($arRequest = [])
    {
        try {
            $id = $arRequest['id'] ?? null;
            $score = $arRequest['score'] ?? null;
            $comment = $arRequest['comment'] ?? '';
            if (!$id || $score===null) throw new \Exception('Не указан id сдачи или оценка');
            $mapping = self::getStatusMapping();
            $updateData = [
                'UF_SCORE'=>(int)$score,
                'UF_TEACHER_COMMENT'=>$comment,
                'UF_STATUS'=>$mapping['graded']
            ];
            $result = \Legacy\HighLoadBlock\SubmissionsTable::update($id,$updateData);
            if ($result->isSuccess()) return ['success'=>true,'message'=>'Оценка сохранена','id'=>$id];
            throw new \Exception(implode(', ',$result->getErrorMessages()));
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при оценке сдачи: '.$e->getMessage());
        }
    }

    public static function checkExists($arRequest = [])
    {
        try {
            $studentId = $arRequest['studentId'] ?? null;
            $lessonId = $arRequest['lessonId'] ?? null;
            if (!$studentId || !$lessonId) throw new \Exception('Не указан studentId или lessonId');
            return ['exists'=>SubmissionsTable::exists($studentId,$lessonId)];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при проверке существования сдачи: '.$e->getMessage());
        }
    }

    public static function getStudentProgress($arRequest = [])
    {
        try {
            $studentId = $arRequest['studentId'] ?? $_GET['studentId'] ?? null;
            if (!$studentId) throw new \Exception('Не указан studentId');
            $allSubmissions = SubmissionsTable::getByStudent($studentId,[],['ID'=>'ASC'],0);
            $total=count($allSubmissions);
            $graded=0;
            $totalScore=0;
            foreach ($allSubmissions as $submission) {
                if ($submission['UF_STATUS']==='Проверено' && $submission['UF_SCORE']>0) {
                    $graded++;
                    $totalScore+=$submission['UF_SCORE'];
                }
            }
            $averageScore=$graded>0?$totalScore/$graded:0;
            return [
                'total_submissions'=>$total,
                'graded_submissions'=>$graded,
                'pending_submissions'=>$total-$graded,
                'average_score'=>round($averageScore,2),
                'total_score'=>$totalScore
            ];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении прогресса студента: '.$e->getMessage());
        }
    }

    public static function getFileInfo($arRequest = [])
    {
        try {
            $fileId = $arRequest['fileId'] ?? null;
            if (!$fileId) throw new \Exception('Не указан fileId');
            $file = \CFile::GetFileArray($fileId);
            if (!$file) throw new \Exception('Файл не найден');
            return [
                'ID'=>$file['ID'],
                'NAME'=>$file['ORIGINAL_NAME'],
                'SRC'=>$file['SRC'],
                'SIZE'=>self::formatFileSize($file['FILE_SIZE']),
                'TYPE'=>$file['CONTENT_TYPE']
            ];
        } catch (\Exception $e) {
            throw new \Exception('Ошибка при получении информации о файле: '.$e->getMessage());
        }
    }

    private static function formatFileSize($size)
    {
        if ($size==0) return '0 Б';
        $units=['Б','КБ','МБ','ГБ','ТБ'];
        $i=floor(log($size,1024));
        return round($size/pow(1024,$i),2).' '.$units[$i];
    }

    private static function incrementDownloadCount($submissionId)
    {
        try {
            $current = SubmissionsTable::getById($submissionId);
            $downloadCount = isset($current['UF_DOWNLOAD_COUNT'])?(int)$current['UF_DOWNLOAD_COUNT']:0;
            SubmissionsTable::update($submissionId,['UF_DOWNLOAD_COUNT'=>$downloadCount+1]);
        } catch (\Exception $e) {}
    }

    private static function getStatusMapping()
    {
        return [
            'pending'=>1,
            'graded'=>2,
            'pending'=>3,
            'graded'=>4,
            'returned'=>5
        ];
    }

    public static function getStatusInfo($arRequest = [])
    {
        $mapping=self::getStatusMapping();
        return [
            'success'=>true,
            'current_mapping'=>$mapping,
            'note'=>'Временно используем: 1="На проверке" (Yes), 2="Проверено" (No). Добавьте новые значения в список UF_STATUS.'
        ];
    }
}