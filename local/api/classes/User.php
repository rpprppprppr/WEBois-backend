<?php

namespace Legacy\API;

use CUser;

class User
{
    public static function get($arRequest = [])
    {
        global $USER;

        if ($USER->IsAuthorized()) {
            $userId = $USER->GetID();
            $rsUser = CUser::GetByID($userId);
            $arUser = $rsUser->Fetch();

            return [
                'id' => $arUser['ID'],
                'login' => $arUser['LOGIN'],
                'email' => $arUser['EMAIL'],
                'firstName' => $arUser['NAME'],
                'lastName' => $arUser['LAST_NAME']
            ];
        } else {
            return ['message' => 'User not authenticated'];
        }
    }

    public static function getById($arRequest = [])
    {
        $userId = $arRequest['id'] ?? $_GET['id'] ?? null;

        if (!$userId) {
            return ['message' => 'User ID is required'];
        }

        $rsUser = CUser::GetByID($userId);

        if ($arUser = $rsUser->Fetch()) {
            $userGroups = CUser::GetUserGroup($userId);

            return [
                'id' => $arUser['ID'],
                'login' => $arUser['LOGIN'],
                'email' => $arUser['EMAIL'],
                'firstName' => $arUser['NAME'],
                'lastName' => $arUser['LAST_NAME'],
                'secondName' => $arUser['SECOND_NAME'] ?? '',
                'personalPhone' => $arUser['PERSONAL_PHONE'] ?? '',
                'personalMobile' => $arUser['PERSONAL_MOBILE'] ?? '',
                'workPhone' => $arUser['WORK_PHONE'] ?? '',
                'personalStreet' => $arUser['PERSONAL_STREET'] ?? '',
                'personalCity' => $arUser['PERSONAL_CITY'] ?? '',
                'personalState' => $arUser['PERSONAL_STATE'] ?? '',
                'personalZip' => $arUser['PERSONAL_ZIP'] ?? '',
                'personalCountry' => $arUser['PERSONAL_COUNTRY'] ?? '',
                'workCompany' => $arUser['WORK_COMPANY'] ?? '',
                'workPosition' => $arUser['WORK_POSITION'] ?? '',
                'timeZone' => $arUser['TIME_ZONE'] ?? '',
                'dateRegister' => $arUser['DATE_REGISTER'] ?? '',
                'lastLogin' => $arUser['LAST_LOGIN'] ?? '',
                'isOnline' => $arUser['IS_ONLINE'] ?? false,
                'groups' => $userGroups,
                'isTeacher' => in_array(\Legacy\General\Constants::GROUP_TEACHERS, $userGroups),
                'isStudent' => in_array(\Legacy\General\Constants::GROUP_STUDENTS, $userGroups),
                'isAdmin' => in_array(1, $userGroups)
            ];
        } else {
            return ['message' => 'User not found'];
        }
    }

    public static function getList($arRequest = [])
    {
        $filter = $arRequest['filter'] ?? [];
        $limit = $arRequest['limit'] ?? 50;
        $page = $arRequest['page'] ?? 1;

        $navParams = ['nPageSize' => $limit, 'iNumPage' => $page];
        $defaultFilter = ['ACTIVE' => 'Y'];
        $finalFilter = array_merge($defaultFilter, $filter);

        $rsUsers = CUser::GetList('ID', 'ASC', $finalFilter, $navParams);

        $users = [];
        while ($arUser = $rsUsers->Fetch()) {
            $userGroups = CUser::GetUserGroup($arUser['ID']);

            $users[] = [
                'id' => $arUser['ID'],
                'login' => $arUser['LOGIN'],
                'email' => $arUser['EMAIL'],
                'firstName' => $arUser['NAME'],
                'lastName' => $arUser['LAST_NAME'],
                'isOnline' => $arUser['IS_ONLINE'] ?? false,
                'dateRegister' => $arUser['DATE_REGISTER'] ?? '',
                'lastLogin' => $arUser['LAST_LOGIN'] ?? '',
                'isTeacher' => in_array(\Legacy\General\Constants::GROUP_TEACHERS, $userGroups),
                'isStudent' => in_array(\Legacy\General\Constants::GROUP_STUDENTS, $userGroups)
            ];
        }

        $total = CUser::GetList('ID', 'ASC', $finalFilter);

        return [
            'items' => $users,
            'total' => $total->SelectedRowsCount()
        ];
    }

    public static function getTeachers($arRequest = [])
    {
        return self::getList([
            'filter' => ['GROUPS_ID' => \Legacy\General\Constants::GROUP_TEACHERS],
            'limit' => $arRequest['limit'] ?? 50,
            'page' => $arRequest['page'] ?? 1
        ]);
    }

    public static function getStudents($arRequest = [])
    {
        return self::getList([
            'filter' => ['GROUPS_ID' => \Legacy\General\Constants::GROUP_STUDENTS],
            'limit' => $arRequest['limit'] ?? 50,
            'page' => $arRequest['page'] ?? 1
        ]);
    }
}