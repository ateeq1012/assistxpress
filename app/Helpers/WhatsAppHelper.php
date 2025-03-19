<?php

namespace App\Helpers;

use App\Models\User;

class WhatsAppHelper
{
	public static function sendMessage($to, $message)
	{
		$response = Http::withHeaders([
			'accept' => 'application/json',
			'Content-Type' => 'application/json',
		])->post('https://wa-api-echo-pk.axamesh.net/api/sendText', [
			'chatId' => $to,
			'reply_to' => null,
			'text' => $message,
			'session' => 'default',
		]);

		if ($response->successful()) {
		} else {
		}
	}
}