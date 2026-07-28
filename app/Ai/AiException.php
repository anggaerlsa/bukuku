<?php

namespace App\Ai;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * A provider refused, or could not be reached.
 *
 * The message is written for the author, in Indonesian, because it is shown to
 * them directly — "402" tells them nothing, "saldo API habis" tells them what
 * to go and do.
 *
 * Nothing built here ever includes the request that was sent. The API key
 * travels in a header and the novel travels in the body, and neither belongs in
 * an error message that may end up in a log.
 */
class AiException extends RuntimeException
{
    public static function fromResponse(Response $response): self
    {
        $status = $response->status();

        // Providers put a reason in the body; it is about the request, not the
        // credentials, so it is safe to relay. Trimmed, in case it is enormous.
        $detail = (string) data_get($response->json(), 'error.message', '');

        $message = match (true) {
            $status === 401 => 'Kunci API ditolak. Periksa kembali kuncinya di halaman Profil.',
            $status === 402 => 'Saldo API-mu habis. Isi ulang di dasbor penyedia, lalu coba lagi.',
            $status === 429 => 'Penyedia sedang membatasi laju permintaan. Tunggu sebentar, lalu coba lagi.',
            $status === 400 => 'Permintaan ditolak penyedia' . ($detail ? ": {$detail}" : '.'),
            $status >= 500 => 'Server penyedia sedang bermasalah. Coba lagi beberapa saat lagi.',
            default => 'Penyedia menolak permintaan (HTTP ' . $status . ')' . ($detail ? ": {$detail}" : '.'),
        };

        return new self(mb_strimwidth($message, 0, 400, '…'));
    }

    public static function unreachable(): self
    {
        return new self('Tidak bisa menghubungi penyedia AI. Periksa koneksi, lalu coba lagi.');
    }

    public static function empty(): self
    {
        return new self('Penyedia menjawab tanpa isi. Coba kirim ulang pertanyaanmu.');
    }
}
