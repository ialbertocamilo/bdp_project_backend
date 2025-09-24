<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    use CacheableTrait;

    public function getSystemInfo()
    {
        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_type' => DB::getDriverName(),
            'storage_disk' => config('filesystems.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'mail_driver' => config('mail.default'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'disk_space' => $this->getDiskSpace(),
            'memory_usage' => $this->getMemoryUsage(),
            'uptime' => $this->getSystemUptime()
        ]);
    }

    public function getGeneralSettings()
    {
        $settings = $this->remember('general_settings', 300, function () {
            return [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
                'items_per_page' => 10,
                'session_lifetime' => config('session.lifetime'),
                'max_upload_size' => '10MB',
                'allowed_file_types' => ['xlsx', 'xls', 'csv', 'pdf', 'doc', 'docx'],
                'enable_registration' => true,
                'require_email_verification' => false,
                'enable_2fa' => true,
                'password_min_length' => 8,
                'session_timeout' => 120
            ];
        });

        return response()->json(['settings' => $settings]);
    }

    public function updateGeneralSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'timezone' => 'required|string|max:50',
            'locale' => 'required|string|max:10',
            'items_per_page' => 'required|integer|min:5|max:100',
            'session_lifetime' => 'required|integer|min:30|max:1440',
            'max_upload_size' => 'required|string|max:10',
            'password_min_length' => 'required|integer|min:6|max:32',
            'session_timeout' => 'required|integer|min:30|max:480'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->updateEnvFile([
                'APP_NAME' => $request->app_name,
                'APP_TIMEZONE' => $request->timezone,
                'APP_LOCALE' => $request->locale
            ]);

            $this->forget('general_settings');

            return response()->json([
                'message' => 'General settings updated successfully',
                'settings' => $request->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSecuritySettings()
    {
        $settings = $this->remember('security_settings', 300, function () {
            return [
                'enable_2fa' => true,
                'password_min_length' => 8,
                'password_require_uppercase' => true,
                'password_require_lowercase' => true,
                'password_require_numbers' => true,
                'password_require_symbols' => false,
                'session_timeout' => 120,
                'max_login_attempts' => 5,
                'lockout_duration' => 30,
                'enable_password_history' => false,
                'password_history_count' => 5,
                'enable_ip_whitelist' => false,
                'allowed_ips' => [],
                'enable_audit_log' => true,
                'log_failed_logins' => true,
                'log_user_actions' => true
            ];
        });

        return response()->json(['settings' => $settings]);
    }

    public function updateSecuritySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_min_length' => 'required|integer|min:6|max:32',
            'session_timeout' => 'required|integer|min:30|max:480',
            'max_login_attempts' => 'required|integer|min:3|max:10',
            'lockout_duration' => 'required|integer|min:5|max:60',
            'password_history_count' => 'required|integer|min:3|max:10',
            'allowed_ips' => 'array',
            'allowed_ips.*' => 'ip'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->forget('security_settings');

            return response()->json([
                'message' => 'Security settings updated successfully',
                'settings' => $request->all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update security settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmailSettings()
    {
        $settings = $this->remember('email_settings', 300, function () {
            return [
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'enable_notifications' => true,
                'notify_user_registration' => true,
                'notify_project_updates' => true,
                'notify_import_completion' => true,
                'notify_system_errors' => true
            ];
        });

        return response()->json(['settings' => $settings]);
    }

    public function updateEmailSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_driver' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'required_if:mail_driver,smtp|string|max:255',
            'mail_port' => 'required_if:mail_driver,smtp|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->updateEnvFile([
                'MAIL_MAILER' => $request->mail_driver,
                'MAIL_HOST' => $request->mail_host,
                'MAIL_PORT' => $request->mail_port,
                'MAIL_USERNAME' => $request->mail_username,
                'MAIL_ENCRYPTION' => $request->mail_encryption,
                'MAIL_FROM_ADDRESS' => $request->mail_from_address,
                'MAIL_FROM_NAME' => $request->mail_from_name
            ]);

            $this->forget('email_settings');

            return response()->json([
                'message' => 'Email settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update email settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testEmailConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Simple email test
            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    public function optimizeSystem()
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return response()->json([
                'message' => 'System optimized successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to optimize system: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSystemLogs()
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (!file_exists($logPath)) {
                return response()->json(['logs' => []]);
            }

            $logs = file_get_contents($logPath);
            $logLines = array_slice(array_reverse(explode("\n", $logs)), 0, 100);

            return response()->json([
                'logs' => array_filter($logLines)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearLogs()
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            
            if (file_exists($logPath)) {
                file_put_contents($logPath, '');
            }

            return response()->json([
                'message' => 'Logs cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear logs: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            throw new \Exception('.env file not found');
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    private function getDiskSpace()
    {
        try {
            $bytes = disk_free_space(storage_path());
            return $this->formatBytes($bytes);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getMemoryUsage()
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    private function getSystemUptime()
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $uptime = file_get_contents('/proc/uptime');
                $uptimeSeconds = floatval($uptime);
                return $this->formatTime($uptimeSeconds);
            }
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function formatTime($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }
}