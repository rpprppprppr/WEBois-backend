<?php

namespace Legacy\API;

use Legacy\General\Constants;

class Auth
{
    protected static function buildUserData($userId)
    {
        $userInfo = \CUser::GetByID($userId)->Fetch();
        $groups = \CUser::GetUserGroup($userId);

        return [
            'id' => $userId,
            'login' => $userInfo['LOGIN'],
            'name' => trim($userInfo['NAME'] . ' ' . $userInfo['LAST_NAME']),
            'email' => $userInfo['EMAIL'],
            'groups' => $groups,
            'is_teacher' => in_array(Constants::GROUP_TEACHERS, $groups),
            'is_student' => in_array(Constants::GROUP_STUDENTS, $groups)
        ];
    }

    public static function login($arRequest)
    {
        global $USER;

        $login = $arRequest['login'] ?? null;
        $password = $arRequest['password'] ?? null;

        if (!$login || !$password) {
            return ['message' => 'Login and password are required'];
        }

        if ($USER->Login($login, $password, 'Y') === true) {
            return [
                'message' => 'Successfully authenticated',
                'user' => self::buildUserData($USER->GetID())
            ];
        }

        return ['message' => 'Invalid login or password'];
    }

    public static function logout($arRequest)
    {
        global $USER;
        $USER->Logout();
        return ['message' => 'Successfully logged out'];
    }

    public static function checkAuth($arRequest)
    {
        global $USER;

        if ($USER->IsAuthorized()) {
            return [
                'is_authorized' => true,
                'user' => self::buildUserData($USER->GetID())
            ];
        }

        return ['is_authorized' => false];
    }

    public static function getProfile($arRequest)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['message' => 'Not authorized'];
        }

        return self::buildUserData($USER->GetID());
    }

    public static function getStudents($arRequest)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['message' => 'Not authorized'];
        }

        $filter = [
            'GROUPS_ID' => [Constants::GROUP_STUDENTS],
            'ACTIVE' => 'Y'
        ];

        $users = [];
        $rsUsers = \CUser::GetList(
            ($by = 'LAST_NAME'),
            ($order = 'ASC'),
            $filter,
            ['SELECT' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'EMAIL']]
        );

        while ($user = $rsUsers->Fetch()) {
            $users[] = [
                'id' => $user['ID'],
                'login' => $user['LOGIN'],
                'name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
                'email' => $user['EMAIL']
            ];
        }

        return [
            'items' => $users,
            'total' => count($users)
        ];
    }
}