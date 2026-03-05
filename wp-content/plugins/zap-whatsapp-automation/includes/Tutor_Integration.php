<?php
namespace ZapWA;

if (!defined('ABSPATH')) { exit; }

class Tutor_Integration {

    private static $initialized = false;

    public static function init() {
        if (self::$initialized) return;
        self::$initialized = true;

        // Only hook if Tutor LMS is active
        if (!self::is_tutor_active()) return;

        add_action('tutor_after_enroll',           [self::class, 'on_enroll'],           10, 1);
        add_action('tutor_course_complete_after',  [self::class, 'on_course_complete'],  10, 1);
        add_action('tutor_lesson_complete_after',  [self::class, 'on_lesson_complete'],  10, 2);
    }

    public static function is_tutor_active() {
        return function_exists('tutor') || class_exists('TUTOR');
    }

    public static function on_enroll($course_id) {
        $user_id = get_current_user_id();
        if (!$user_id || !$course_id) return;

        // Fire the zap_evento action for flow engine compatibility
        do_action('zap_evento', [
            'event'   => 'course_enrolled',
            'user_id' => $user_id,
            'context' => ['course_id' => absint($course_id)],
        ]);

        // Record in event tracker
        if (class_exists('\ZapWA\Event_Tracker')) {
            Event_Tracker::record(
                $user_id,
                'course_enrolled',
                (string) absint($course_id),
                'tutor_lms',
                ['course_id' => absint($course_id)]
            );
        }
    }

    public static function on_course_complete($course_id) {
        $user_id = get_current_user_id();
        if (!$user_id || !$course_id) return;

        do_action('zap_evento', [
            'event'   => 'course_completed',
            'user_id' => $user_id,
            'context' => ['course_id' => absint($course_id)],
        ]);

        if (class_exists('\ZapWA\Event_Tracker')) {
            Event_Tracker::record(
                $user_id,
                'course_completed',
                (string) absint($course_id),
                'tutor_lms',
                ['course_id' => absint($course_id)]
            );
        }
    }

    public static function on_lesson_complete($lesson_id, $course_id) {
        $user_id = get_current_user_id();
        if (!$user_id || !$lesson_id) return;

        do_action('zap_evento', [
            'event'   => 'lesson_completed',
            'user_id' => $user_id,
            'context' => ['lesson_id' => absint($lesson_id), 'course_id' => absint($course_id)],
        ]);

        if (class_exists('\ZapWA\Event_Tracker')) {
            Event_Tracker::record(
                $user_id,
                'lesson_completed',
                (string) absint($lesson_id),
                'tutor_lms',
                ['lesson_id' => absint($lesson_id), 'course_id' => absint($course_id)]
            );
        }
    }
}
