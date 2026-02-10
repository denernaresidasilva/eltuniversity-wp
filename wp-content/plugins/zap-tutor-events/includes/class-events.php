<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) exit;

class Events {

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
            'tutor_course_completed'         => 'Curso 100% concluído',
            'tutor_assignment_submitted'     => 'Trabalho enviado',
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
        add_action('tutor_course_complete_after', [self::class, 'course_completed'], 10, 2);
        add_action('tutor_assignment_after_submitted', [self::class, 'assignment_submitted'], 10, 2);

        add_action('tutor_quiz/start/after', [self::class, 'quiz_started'], 10, 3);
        add_action('tutor_quiz_finished', [self::class, 'quiz_finished'], 10, 3);

        add_action('tutor_order_payment_status_changed', [self::class, 'order_status_changed'], 10, 3);
    }

    /* ================= USUÁRIO ================= */

    public static function student_signup($user_id) {
        Dispatcher::dispatch(
            'tutor_student_signup',
            $user_id
        );
    }

    public static function student_login($user_id) {
        Dispatcher::dispatch(
            'tutor_student_login',
            $user_id
        );
    }

    /* ================= CURSO ================= */

    public static function course_enrolled($course_id, $user_id, $enrolled_id) {
        Dispatcher::dispatch(
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

        Dispatcher::dispatch(
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

        Dispatcher::dispatch(
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

        Dispatcher::dispatch(
            'tutor_course_progress_50',
            $user_id,
            [
                'course_id' => $course_id,
                'progress'  => $progress,
            ]
        );
    }

    /**
     * Course 100% completed
     * 
     * @param int $course_id Course ID
     * @param int $user_id User ID
     */
    public static function course_completed($course_id, $user_id) {
        Dispatcher::dispatch(
            'tutor_course_completed',
            $user_id,
            [
                'course_id' => $course_id,
                'progress'  => 100,
            ]
        );
    }

    /**
     * Assignment submitted
     * 
     * @param int $assignment_id Assignment ID
     * @param int $user_id User ID (may not be passed by hook, use get_current_user_id())
     */
    public static function assignment_submitted($assignment_id, $user_id = null) {
        if (empty($user_id)) {
            $user_id = get_current_user_id();
        }
        
        // Get course ID from assignment
        $course_id = get_post_meta($assignment_id, '_tutor_course_id_for_assignments', true);
        
        Dispatcher::dispatch(
            'tutor_assignment_submitted',
            $user_id,
            [
                'assignment_id' => $assignment_id,
                'course_id'     => $course_id,
            ]
        );
    }

    /* ================= QUIZ ================= */

    public static function quiz_started($quiz_id, $user_id, $attempt_id) {
        Dispatcher::dispatch(
            'tutor_quiz_started',
            $user_id,
            [
                'quiz_id'    => $quiz_id,
                'attempt_id' => $attempt_id,
            ]
        );
    }

    public static function quiz_finished($attempt_id, $quiz_id, $user_id) {
        Dispatcher::dispatch(
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

        Dispatcher::dispatch(
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
