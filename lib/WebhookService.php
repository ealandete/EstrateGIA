<?php

class WebhookService {
    private static array $hooks = [];

    public static function configure(array $hooks): void {
        self::$hooks = $hooks;
    }

    public static function send(string $event, string $message, array $data = []): void {
        foreach (self::$hooks as $hook) {
            if (!in_array($event, $hook['events'] ?? ['*'])) continue;
            $payload = json_encode([
                'text' => "*EstrateGIA* — {$message}",
                'attachments' => [[ 'fields' => array_map(fn($k,$v) => ['title'=>$k,'value'=>(string)$v,'short'=>true], array_keys($data), $data) ]]
            ]);
            @file_get_contents($hook['url'], false, stream_context_create([
                'http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $payload, 'timeout' => 5]
            ]));
        }
    }
}
