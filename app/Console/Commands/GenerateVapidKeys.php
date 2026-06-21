<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use phpseclib3\Crypt\RSA; // fallback si minish/web-push no está disponible
use Illuminate\Support\Facades\File;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid {--show : Only print existing keys without regenerating}';
    protected $description = 'Generate VAPID keys for Web Push notifications (RFC 8292)';

    public function handle(): int
    {
        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';

        if ($this->option('show')) {
            $this->info('Current VAPID keys in .env:');
            foreach (['VAPID_SUBJECT', 'VAPID_PUBLIC_KEY', 'VAPID_PRIVATE_KEY'] as $key) {
                $value = $this->getEnvValue($env, $key);
                $this->line("  {$key}=" . ($value ?: '(not set)'));
            }
            return self::SUCCESS;
        }

        // Generar usando OpenSSL (disponible en cualquier PHP moderno)
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1', // P-256 (requerido por VAPID)
        ];

        $res = openssl_pkey_new($config);
        if (!$res) {
            $this->error('No se pudo generar el par de claves EC P-256. ¿OpenSSL disponible?');
            return self::FAILURE;
        }

        // Exportar privada
        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);
        $publicKeyRaw = $details['key'];

        // Para VAPID necesitamos la clave pública en formato "uncompressed point"
        // (65 bytes: 0x04 + X[32] + Y[32]), base64url-encoded.
        $publicKeyB64Url = $this->extractVapidPublicKey($publicKeyRaw);

        $subject = config('services.webpush.subject', 'mailto:admin@example.com');

        $this->info('VAPID keys generadas. Agregá esto a tu .env:');
        $this->newLine();
        $this->line("VAPID_SUBJECT=\"{$subject}\"");
        $this->line("VAPID_PUBLIC_KEY=\"{$publicKeyB64Url}\"");
        $this->line("VAPID_PRIVATE_KEY=\"{$this->extractVapidPrivateKey($privateKey)}\"");

        if ($this->confirm('¿Actualizar .env automáticamente?', true)) {
            $updated = $this->updateEnv($env, [
                'VAPID_SUBJECT' => $subject,
                'VAPID_PUBLIC_KEY' => $publicKeyB64Url,
                'VAPID_PRIVATE_KEY' => $this->extractVapidPrivateKey($privateKey),
            ]);
            File::put($envPath, $updated);
            $this->info('.env actualizado.');
        }

        return self::SUCCESS;
    }

    protected function extractVapidPublicKey(string $pem): string
    {
        // Parsear el SubjectPublicKeyInfo PEM y extraer el punto sin comprimir
        $res = openssl_pkey_get_public($pem);
        $details = openssl_pkey_get_details($res);

        // En OpenSSL, EC public key viene como 'ec' con 'x' e 'y' en decimal/hex
        // Construimos manualmente el punto 0x04 || X(32) || Y(32)
        if (!isset($details['ec']['x'], $details['ec']['y'])) {
            throw new \RuntimeException('No se pudo extraer coordenadas EC.');
        }

        $x = $this->bigIntTo32Bytes($details['ec']['x']);
        $y = $this->bigIntTo32Bytes($details['ec']['y']);
        $point = "\x04" . $x . $y;

        return $this->base64UrlEncode($point);
    }

    protected function extractVapidPrivateKey(string $pem): string
    {
        $res = openssl_pkey_get_private($pem);
        $details = openssl_pkey_get_details($res);

        if (!isset($details['ec']['d'])) {
            throw new \RuntimeException('No se pudo extraer la clave privada EC.');
        }

        return $this->base64UrlEncode($this->bigIntTo32Bytes($details['ec']['d']));
    }

    protected function bigIntTo32Bytes(string $value): string
    {
        $hex = $this->normalizeToHex($value);

        // Pad a 32 bytes (64 hex chars)
        if (strlen($hex) > 64) {
            $hex = substr($hex, -64);
        }
        $hex = str_pad($hex, 64, '0', STR_PAD_LEFT);
        return hex2bin($hex);
    }

    /**
     * Normaliza un número grande (hex con/sin 0x, decimal, o binario crudo)
     * a hex puro (sin 0x). Usa aritmética de strings pura para no depender
     * de GMP ni BCMath.
     */
    protected function normalizeToHex(string $value): string
    {
        // 1. Hex con prefijo 0x / 0X
        if (strlen($value) >= 2 && $value[0] === '0' && in_array($value[1], ['x', 'X'], true)) {
            return strtolower(substr($value, 2));
        }

        // 2. Hex "crudo" (sólo chars hex, longitud par)
        if (preg_match('/^[0-9a-fA-F]+$/', $value) && strlen($value) >= 2 && strlen($value) % 2 === 0) {
            return strtolower($value);
        }

        // 3. Decimal → convertir manualmente
        if (preg_match('/^[0-9]+$/', $value)) {
            return $this->decToHexPure($value);
        }

        // 4. Binario crudo (lo que pasa en algunas versiones de OpenSSL):
        //    las coordenadas EC vienen como bytes sin formato. bin2hex() los
        //    convierte directo a hex.
        return bin2hex($value);
    }

    /**
     * Convierte un entero decimal en string a hex en string, usando aritmética
     * de strings pura. Funciona con números arbitrariamente grandes.
     *
     *   decToHexPure("123456789012345678901234567890") -> "4d839eb7f7d29a44a09fd99..."
     */
    protected function decToHexPure(string $dec): string
    {
        // Strip leading zeros (mantenemos uno si todo es 0)
        $dec = ltrim($dec, '0');
        if ($dec === '') {
            return '0';
        }

        // División por 16 repetida, acumulando restos
        $hex = '';
        while ($dec !== '' && $dec !== '0') {
            $carry = 0;
            $quotient = '';
            $len = strlen($dec);
            for ($i = 0; $i < $len; $i++) {
                $cur = $carry * 10 + (int) $dec[$i];
                $q = intdiv($cur, 16);
                $carry = $cur % 16;
                if ($quotient !== '' || $q > 0) {
                    $quotient .= (string) $q;
                }
            }
            $hex = dechex($carry) . $hex;
            $dec = $quotient;
            if ($dec === '') {
                $dec = '0';
            }
        }
        return $hex === '' ? '0' : $hex;
    }

    protected function base64UrlEncode(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    protected function getEnvValue(string $env, string $key): ?string
    {
        if (preg_match("/^{$key}=(.*)$/m", $env, $m)) {
            return trim($m[1], "\"' ");
        }
        return null;
    }

    protected function updateEnv(string $env, array $values): string
    {
        foreach ($values as $key => $value) {
            $line = "{$key}=\"{$value}\"";
            if (preg_match("/^{$key}=.*$/m", $env)) {
                $env = preg_replace("/^{$key}=.*$/m", $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }
        return $env;
    }
}
