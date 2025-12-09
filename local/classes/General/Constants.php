<?php
namespace Legacy\General;

class Constants
{

	/** ПОЛЬЗОВАТЕЛИ, ИМЕЮЩИЕ ПРАВО ГОЛОСОВАТЬ ЗА РЕЙТИНГ */
	public const GROUP_RATING_VOTE = '3';

	/** ПОЛЬЗОВАТЕЛИ ИМЕЮЩИЕ ПРАВО ГОЛОСОВАТЬ ЗА АВТОРИТЕТ */
	public const GROUP_RATING_VOTE_AUTHORITY = '4';

	// Инфоблоки
    const IB_COURSES = 3;
    const IB_LEARNINGMODULES = 4;
    
    // Свойства инфоблока LearningModules
	const PROP_TEACHER = 3;
    const PROP_STUDENT = 4;
    const COURSE_DESCRIPTION = 5;
    const PROP_COURSE = 6;
    const PROP_MODULE_TYPE = 7; 
    const PROP_CONTENT = 8;
    const PROP_TASK_TYPE = 9;
    const PROP_DEADLINE = 13;
    const PROP_MAX_SCORE = 10;
    const PROP_NEXT_MODULE = 11;
    const PROP_FILE = 12;
    
    // Группы пользователей
    const GROUP_STUDENTS = 5;
    const GROUP_TEACHERS = 6;
    
    // Highload-блоки
    const HLBLOCK_SUBMISSIONS = 1;
}
