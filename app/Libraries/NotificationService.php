<?php

namespace App\Libraries;

use App\Models\AppSettingModel;
use App\Models\NotificationModel;
use App\Models\UserModel;
use Config\Services;

class NotificationService
{
    public function sendToUser(int $userId, array $payload): ?int
    {
        if (! db_connect()->tableExists('notifications')) return null;
        $notificationModel = new NotificationModel();
        $dedupeKey = trim((string) ($payload['dedupe_key'] ?? ''));
        if ($dedupeKey !== '') {
            $existing = $notificationModel->where('dedupe_key', $dedupeKey)->first();
            if ($existing) return (int) $existing['id'];
        }

        $data = [
            'user_id' => $userId,
            'actor_user_id' => $payload['actor_user_id'] ?? session()->get('user_id'),
            'type' => $payload['type'] ?? 'info',
            'title' => $payload['title'] ?? 'Notifikasi EAMS',
            'message' => $payload['message'] ?? '',
            'url' => $payload['url'] ?? '/home',
            'entity_type' => $payload['entity_type'] ?? null,
            'entity_id' => $payload['entity_id'] ?? null,
            'dedupe_key' => $dedupeKey !== '' ? $dedupeKey : null,
            'email_status' => 'skipped',
            'whatsapp_status' => 'skipped',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $notificationModel->insert($data);
        $id = (int) $notificationModel->getInsertID();

        $user = (new UserModel())->find($userId);
        if ($user) {
            $settings = (new AppSettingModel())->allAsMap(true);
            $notificationModel->update($id, [
                'email_status' => $this->sendEmail($user, $data, $settings),
                'whatsapp_status' => $this->sendWhatsApp($user, $data, $settings),
            ]);
        }
        return $id;
    }

    private function sendEmail(array $user, array $data, array $settings): string
    {
        if (($settings['notification_email_enabled'] ?? '0') !== '1') return 'skipped';
        $recipient = trim((string) ($user['email'] ?? ''));
        if ($recipient === '' && filter_var($user['username'] ?? '', FILTER_VALIDATE_EMAIL)) $recipient = $user['username'];
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) return 'missing_target';
        try {
            $email = Services::email();
            $email->setTo($recipient);
            $email->setSubject('[' . ($settings['company_name'] ?? 'EAMS') . '] ' . $data['title']);
            $email->setMessage($data['message'] . "\n\n" . base_url(ltrim((string) $data['url'], '/')));
            return $email->send() ? 'sent' : 'failed';
        } catch (\Throwable $e) {
            log_message('error', 'Notification email failed: ' . $e->getMessage());
            return 'failed';
        }
    }

    private function sendWhatsApp(array $user, array $data, array $settings): string
    {
        if (($settings['notification_whatsapp_enabled'] ?? '0') !== '1') return 'skipped';
        $number = preg_replace('/\D+/', '', (string) ($user['wa_number'] ?? ''));
        $webhook = trim((string) ($settings['notification_whatsapp_webhook'] ?? ''));
        if ($number === '' || $webhook === '') return 'missing_target';
        try {
            $headers = ['Accept' => 'application/json'];
            $token = trim((string) ($settings['notification_whatsapp_token'] ?? ''));
            if ($token !== '') $headers['Authorization'] = 'Bearer ' . $token;
            Services::curlrequest()->post($webhook, [
                'headers' => $headers,
                'json' => ['to' => $number, 'message' => $data['title'] . "\n" . $data['message'] . "\n" . base_url(ltrim((string) $data['url'], '/'))],
                'timeout' => 8,
            ]);
            return 'sent';
        } catch (\Throwable $e) {
            log_message('error', 'Notification WhatsApp failed: ' . $e->getMessage());
            return 'failed';
        }
    }
}
