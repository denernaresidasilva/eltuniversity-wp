<?php
if (!defined('ABSPATH')) exit;

class ZAP_Events {

    /**
     * REGISTRO CENTRAL DE EVENTOS
     * chave => label
     */
    public static function registry() {
        return [
            'tutor_student_signup'           => 'Aluno cadastrado',
            'tutor_student_login'            => 'Aluno logado',
            'tutor_course_enrolled'          => 'Aluno matriculado no curso',
            'tutor_enrol_status_changed'     => 'Status da matrícula alterado',
            'tutor_lesson_completed'         => 'Aula concluída',
            'tutor_course_progress_50'       => 'Curso 50% concluído',
            'tutor_quiz_started'             => 'Quiz iniciado',
            'tutor_quiz_finished'            => 'Quiz finalizado',
            'tutor_order_payment_status_changed' => 'Status do pagamento alterado',
        ];
    }

    /**
     * INIT
     */
    public static function init() {

        // Expor eventos para outros plugins
        add_filter('zap_tutor_events_list', function () {
            return self::registry();
        });

        // Tutor hooks
        add_action('tutor_after_student_signup', [self::class, 'student_signup']);
        add_action('tutor_after_login_success', [self::class, 'student_login']);

        add_action('tutor_after_enrolled', [self::class, 'course_enrolled'], 10, 3);
        add_action('tutor/course/enrol_status_change/after', [self::class, 'enrol_status_changed'], 10, 2);

        add_action('tutor_lesson_completed_after', [self::class, 'lesson_completed'], 10, 3);

        add_action('tutor_quiz/start/after', [self::class, 'quiz_started'], 10, 3);
        add_action('tutor_quiz_finished', [self::class, 'quiz_finished'], 10, 3);

        add_action('tutor_order_payment_status_changed', [self::class, 'order_status_changed'], 10, 3);
    }

    /* ================= USUÁRIO ================= */

    public static function student_signup($user_id) {
        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_student_signup',
            $user_id
        );
    }

    public static function student_login($user_id) {
        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_student_login',
            $user_id
        );
    }

    /* ================= CURSO ================= */

    public static function course_enrolled($course_id, $user_id, $enrolled_id) {
        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_course_enrolled',
            $user_id,
            [
                'course_id'   => $course_id,
                'enrolled_id' => $enrolled_id,
            ]
        );
    }

    public static function enrol_status_changed($enrol_id, $new_status) {

        $enrol = tutor_utils()->get_enrolment_by_enrol_id($enrol_id);
        if (!$enrol) return;

        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_enrol_status_changed',
            (int) $enrol->user_id,
            [
                'course_id' => (int) $enrol->course_id,
                'status'    => $new_status,
            ]
        );
    }

    /* ================= AULAS ================= */

    public static function lesson_completed($lesson_id, $course_id, $user_id) {

        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_lesson_completed',
            $user_id,
            [
                'lesson_id' => $lesson_id,
                'course_id' => $course_id,
            ]
        );

        self::check_course_50($course_id, $user_id);
    }

    private static function check_course_50($course_id, $user_id) {

        $progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
        if ($progress < 50) return;

        $meta_key = '_zap_course_50_' . $course_id;
        if (get_user_meta($user_id, $meta_key, true)) return;

        update_user_meta($user_id, $meta_key, 1);

        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_course_progress_50',
            $user_id,
            [
                'course_id' => $course_id,
                'progress'  => $progress,
            ]
        );
    }

    /* ================= QUIZ ================= */

    public static function quiz_started($quiz_id, $user_id, $attempt_id) {
        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_quiz_started',
            $user_id,
            [
                'quiz_id'    => $quiz_id,
                'attempt_id' => $attempt_id,
            ]
        );
    }

    public static function quiz_finished($attempt_id, $quiz_id, $user_id) {
        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_quiz_finished',
            $user_id,
            [
                'quiz_id'    => $quiz_id,
                'attempt_id' => $attempt_id,
            ]
        );
    }

    /* ================= PAGAMENTOS ================= */

    public static function order_status_changed($order_id, $from, $to) {

        $order = tutor_utils()->get_order_by_id($order_id);
        if (!$order) return;

        ZAP_Tutor_Events_Dispatcher::dispatch(
            'tutor_order_payment_status_changed',
            (int) $order->user_id,
            [
                'order_id' => $order_id,
                'from'     => $from,
                'to'       => $to,
            ]
        );
    }
}
