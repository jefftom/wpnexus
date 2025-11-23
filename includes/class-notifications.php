<?php
/**
 * Notifications management class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Notifications Class.
 *
 * Handles email notifications and webhooks.
 *
 * @since 1.0.0
 */
class NexusForms_Notifications {

    /**
     * Send notifications for a form submission.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param int $entry_id Entry ID.
     * @param array $entry_data Entry data.
     */
    public function send(int $form_id, int $entry_id, array $entry_data): void {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('notifications');

        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE form_id = %d",
            $form_id
        ));

        foreach ($notifications as $notification) {
            $data = json_decode($notification->notification_data, true);

            switch ($notification->type) {
                case 'email':
                    $this->send_email($data, $entry_data, $form_id, $entry_id);
                    break;

                case 'webhook':
                    $this->send_webhook($data, $entry_data, $form_id, $entry_id);
                    break;
            }
        }

        /**
         * Fires after notifications are sent.
         *
         * @since 1.0.0
         * @param int $form_id Form ID.
         * @param int $entry_id Entry ID.
         * @param array $entry_data Entry data.
         */
        do_action('nexusforms_after_notifications', $form_id, $entry_id, $entry_data);
    }

    /**
     * Send email notification.
     *
     * @since 1.0.0
     * @param array $notification Notification settings.
     * @param array $entry_data Entry data.
     * @param int $form_id Form ID.
     * @param int $entry_id Entry ID.
     */
    private function send_email(array $notification, array $entry_data, int $form_id, int $entry_id): void {
        $to = $this->parse_smart_tags($notification['to'] ?? '', $entry_data);
        $subject = $this->parse_smart_tags($notification['subject'] ?? '', $entry_data);
        $message = $this->parse_smart_tags($notification['message'] ?? '', $entry_data);
        $from_name = $notification['from_name'] ?? get_bloginfo('name');
        $from_email = $notification['from_email'] ?? get_bloginfo('admin_email');

        // Set headers.
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];

        if (!empty($notification['reply_to'])) {
            $reply_to = $this->parse_smart_tags($notification['reply_to'], $entry_data);
            $headers[] = "Reply-To: {$reply_to}";
        }

        // Build email body with template.
        $body = $this->build_email_template($message, $entry_data, $form_id);

        /**
         * Filter email notification data.
         *
         * @since 1.0.0
         * @param array $email_data Email data.
         * @param array $entry_data Entry data.
         * @param int $form_id Form ID.
         */
        $email_data = apply_filters('nexusforms_email_notification', [
            'to' => $to,
            'subject' => $subject,
            'message' => $body,
            'headers' => $headers,
        ], $entry_data, $form_id);

        // Send email.
        $sent = wp_mail(
            $email_data['to'],
            $email_data['subject'],
            $email_data['message'],
            $email_data['headers']
        );

        if (!$sent) {
            error_log("NexusForms: Failed to send email notification for entry {$entry_id}");
        }
    }

    /**
     * Send webhook notification.
     *
     * @since 1.0.0
     * @param array $notification Notification settings.
     * @param array $entry_data Entry data.
     * @param int $form_id Form ID.
     * @param int $entry_id Entry ID.
     */
    private function send_webhook(array $notification, array $entry_data, int $form_id, int $entry_id): void {
        $url = $notification['url'] ?? '';

        if (empty($url)) {
            return;
        }

        $payload = [
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'entry_data' => $entry_data,
            'timestamp' => current_time('mysql'),
        ];

        /**
         * Filter webhook payload.
         *
         * @since 1.0.0
         * @param array $payload Webhook payload.
         * @param array $entry_data Entry data.
         * @param int $form_id Form ID.
         */
        $payload = apply_filters('nexusforms_webhook_payload', $payload, $entry_data, $form_id);

        $response = wp_remote_post($url, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            error_log("NexusForms: Webhook failed for entry {$entry_id}: " . $response->get_error_message());
        }
    }

    /**
     * Parse smart tags in content.
     *
     * @since 1.0.0
     * @param string $content Content with smart tags.
     * @param array $entry_data Entry data.
     * @return string Parsed content.
     */
    private function parse_smart_tags(string $content, array $entry_data): string {
        // Replace field tags {field_id}.
        foreach ($entry_data as $field_id => $value) {
            $tag = '{' . $field_id . '}';
            $replacement = is_array($value) ? implode(', ', $value) : $value;
            $content = str_replace($tag, $replacement, $content);
        }

        // Replace system tags.
        $tags = [
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_bloginfo('url'),
            '{admin_email}' => get_bloginfo('admin_email'),
            '{date}' => current_time('F j, Y'),
            '{time}' => current_time('g:i a'),
        ];

        foreach ($tags as $tag => $replacement) {
            $content = str_replace($tag, $replacement, $content);
        }

        /**
         * Filter parsed smart tags.
         *
         * @since 1.0.0
         * @param string $content Parsed content.
         * @param array $entry_data Entry data.
         */
        return apply_filters('nexusforms_parse_smart_tags', $content, $entry_data);
    }

    /**
     * Build email template.
     *
     * @since 1.0.0
     * @param string $message Email message.
     * @param array $entry_data Entry data.
     * @param int $form_id Form ID.
     * @return string HTML email template.
     */
    private function build_email_template(string $message, array $entry_data, int $form_id): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f4f4f4; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #fff; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2><?php echo esc_html(get_bloginfo('name')); ?></h2>
                </div>
                <div class="content">
                    <?php echo wp_kses_post(wpautop($message)); ?>
                </div>
                <div class="footer">
                    <p>This email was sent from <?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Add a notification to a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $notification_data Notification data.
     * @param string $type Notification type.
     * @return int|false Notification ID on success, false on failure.
     */
    public function add(int $form_id, array $notification_data, string $type = 'email'): int|false {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('notifications');

        $result = $wpdb->insert(
            $table,
            [
                'form_id' => $form_id,
                'notification_data' => json_encode($notification_data),
                'type' => $type,
            ],
            ['%d', '%s', '%s']
        );

        return $result ? $wpdb->insert_id : false;
    }
}
