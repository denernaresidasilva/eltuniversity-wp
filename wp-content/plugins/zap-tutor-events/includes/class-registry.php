<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) exit;

class Registry {

    private static $events = [];

    public static function init() {

        self::$events = apply_filters('zap_events_registry', [
            'tutor_student_signup' => [
                'label' => 'Aluno cadastrado',
                'version' => '1.0',
            ],
            'tutor_course_enrolled' => [
                'label' => 'Curso matriculado',
                'version' => '1.0',
            ],
            'tutor_lesson_completed' => [
                'label' => 'Aula concluída',
                'version' => '1.0',
            ],
            'tutor_course_progress_50' => [
                'label' => 'Curso 50% concluído',
                'version' => '1.0',
            ],
            'tutor_course_completed' => [
                'label' => 'Curso concluído',
                'version' => '1.0',
            ],
        ]);
    }

    public static function all() {
        return self::$events;
    }
}
