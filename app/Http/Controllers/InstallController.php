<?php

namespace App\Http\Controllers;


use App\Helpers\StringHelper;
use App\Models\User;
use App\Http\Requests\Frontend\InstallRequest;
use App\Http\Requests\Frontend\InstallAdminRequest;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Session;
use Hash;
use Artisan;
use Cookie;
use DB;
use Log;
use Config;

class InstallController extends Controller
{
    private const APPLICATION_VERSION = '8.0.0';

    /**
     * Show the first installer screen.
     *
     * @return View
     */
    public function index(): View
    {
        return view('install.start');
    }

    /**
     * Show PHP extension and version requirements for the installer.
     *
     * @return View
     */
    public function requirements(): View
    {
        $requirements = $this->getRequirements();
        $allLoaded = $this->allRequirementsLoaded();

        return view('install.requirements', compact('requirements', 'allLoaded'));
    }

    /**
     * Show filesystem permission checks after requirements pass.
     *
     * @return View|RedirectResponse
     */
    public function permissions(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        $folders = $this->getPermissions();
        $allGranted = $this->allPermissionsGranted();

        return view('install.permissions', compact('folders', 'allGranted'));
    }

    /**
     * Show the database credentials form after environment checks pass.
     *
     * @return View|RedirectResponse
     */
    public function database(): View|RedirectResponse
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        return view('install.database');
    }

    /**
     * Validate database credentials and store them in the session for the next install step.
     *
     * @param InstallRequest $request
     * @return RedirectResponse|View
     */
    public function installation(InstallRequest $request): RedirectResponse|View
    {
        if (!$this->allRequirementsLoaded()) {
            return redirect()->route('install.requirements');
        }

        if (!$this->allPermissionsGranted()) {
            return redirect()->route('install.permissions');
        }

        $dbCredentials = $request->only('host', 'username', 'password', 'database', 'prefix');

        if (!$this->dbCredentialsAreValid($dbCredentials)) {
            return redirect()->route('install.database')
                ->withInput()
                ->withErrors(trans('install.str.connection_to_database_cannot_be_established'));
        }

        Session::put('install.db_credentials', $dbCredentials);

        return redirect()->route('install.admin');
    }

    /**
     * Show the admin account creation step of the installer.
     *
     * @return View
     */
    public function admin(): View
    {
        return view('install.installation');
    }

    /**
     * Write configuration, run migrations and seeders, then create the first admin user.
     *
     * @param InstallAdminRequest $request
     * @return RedirectResponse
     */
    public function install(InstallAdminRequest $request): RedirectResponse
    {
        try {
            $db = Session::pull('install.db_credentials');
            $installLocale = $this->getInstallLocale();

            copy(base_path('.env.example'), base_path('.env'));

            $this->reloadEnv();

            $path = base_path('.env');
            $appKey = $this->generateAppKey();
            $applicationVersion = $this->getApplicationVersion();
            $env = file_get_contents($path);
            $env = str_replace('DB_HOST=' . env('DB_HOST'), 'DB_HOST=' . $db['host'], $env);
            $env = str_replace('DB_DATABASE=' . env('DB_DATABASE'), 'DB_DATABASE=' . $db['database'], $env);
            $env = str_replace('DB_USERNAME=' . env('DB_USERNAME'), 'DB_USERNAME=' . $db['username'], $env);
            $env = str_replace('DB_PASSWORD=' . env('DB_PASSWORD'), 'DB_PASSWORD="' . $db['password'] . '"', $env);
            $env = str_replace('APP_URL=', 'APP_URL=' . StringHelper::getUrl(), $env);
            $env = $this->setEnvValue($env, 'APP_KEY', $appKey);
            $env = $this->setEnvValue($env, 'APP_LOCALE', $installLocale);
            $env = $this->setEnvValue($env, 'VERSION', $applicationVersion);

            file_put_contents($path, $env);

            Artisan::call('config:clear');
            $this->setDatabaseCredentials($db);
            config([
                'app.locale' => $installLocale,
                'app.installed_locale' => $installLocale,
                'app.key' => $appKey,
                'app.version' => $applicationVersion,
            ]);
            app()->setLocale($installLocale);
            config(['app.debug' => true]);

            Artisan::call('migrate', ['--force' => true]);
            User::create(['name' => 'admin', 'login' => $request->input('login'), 'role' => 'admin', 'password' => Hash::make($request->input('password'))]);
            Artisan::call('db:seed', ['--force' => true]);

            return redirect()
                ->route('install.complete')
                ->withCookie(Cookie::forever('lang', $installLocale));
        } catch (\Exception $e) {
            @unlink(base_path('.env'));
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            return redirect()->route('install.error');
        }
    }

    /**
     * Generate a Laravel APP_KEY value suitable for the configured cipher.
     *
     * @return string
     */
    private function generateAppKey(): string
    {
        return 'base64:' . base64_encode(
            Encrypter::generateKey(Config::get('app.cipher', 'AES-256-CBC'))
        );
    }

    /**
     * Resolve a non-empty version even before the installer has created .env.
     */
    private function getApplicationVersion(): string
    {
        $version = trim((string) config('app.version'));

        return $version !== '' ? $version : self::APPLICATION_VERSION;
    }

    /**
     * Reload environment variables after creating or replacing the .env file.
     *
     * @return void
     */
    private function reloadEnv(): void
    {
        (new LoadEnvironmentVariables)->bootstrap(app());
    }

    /**
     * Return the supported locale selected during installation.
     *
     * @return string
     */
    private function getInstallLocale(): string
    {
        $locale = Session::get('install.locale', app()->getLocale());

        return in_array($locale, Config::get('app.locales', []), true)
            ? $locale
            : Config::get('app.fallback_locale', 'en');
    }

    /**
     * Set or append one value in the generated .env contents.
     *
     * @param string $contents
     * @param string $key
     * @param string $value
     * @return string
     */
    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $contents)) {
            $updatedContents = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $contents);

            return $updatedContents ?? $contents;
        }

        return rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }

    /**
     * Return the installed application locale from configuration.
     *
     * @return string
     */
    private function getConfiguredLocale(): string
    {
        $locale = Config::get('app.installed_locale', Config::get('app.fallback_locale', 'en'));

        return in_array($locale, Config::get('app.locales', []), true)
            ? $locale
            : Config::get('app.fallback_locale', 'en');
    }

    /**
     * Show the successful installation completion page.
     *
     * @return View
     */
    public function complete(): View
    {
        $locale = $this->getConfiguredLocale();

        app()->setLocale($locale);
        Cookie::queue(Cookie::forever('lang', $locale));

        return view('install.complete');
    }

    /**
     * Show the installation error page when setup fails.
     *
     * @return View
     */
    public function error(): View
    {
        return view('install.error');
    }

    /**
     * Build the list of required PHP extensions and runtime checks.
     *
     * @return array
     */
    private function getRequirements(): array
    {
        $requirements = [
            'PHP Version (>= 8.4.0)' => version_compare(phpversion(), '8.4.0', '>='),
            'Zip' => extension_loaded('zip'),
            'iconv' => extension_loaded("iconv"),
            'PDO Extension' => extension_loaded('PDO'),
            'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'mbstring' => extension_loaded('mbstring'),
            'JSON PHP Extension' => extension_loaded('json'),
            'Fileinfo Extension' => extension_loaded('fileinfo')
        ];

        if (extension_loaded('xdebug')) {
            $requirements['Xdebug Max Nesting Level (>= 500)'] = (int)ini_get('xdebug.max_nesting_level') >= 500;
        }

        return $requirements;
    }

    /**
     * Determine whether every required PHP extension and runtime check passed.
     *
     * @return bool
     */
    private function allRequirementsLoaded(): bool
    {
        $allLoaded = true;

        foreach ($this->getRequirements() ?? [] as $loaded) {
            if ($loaded === false) {
                $allLoaded = false;
            }
        }

        return $allLoaded;
    }

    /**
     * Build the list of filesystem paths that must be writable by the application.
     *
     * @return array
     */
    private function getPermissions(): array
    {
        return [
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'Base Directory' => is_writable(base_path('')),
        ];
    }

    /**
     * Determine whether every required filesystem path is writable.
     *
     * @return bool
     */
    private function allPermissionsGranted(): bool
    {
        $allGranted = true;

        foreach ($this->getPermissions() as $permission => $granted) {
            if ($granted === false) {
                $allGranted = false;
            }
        }

        return $allGranted;
    }

    /**
     * Test whether the supplied database credentials can connect to the database.
     *
     * @param array $credentials
     * @return bool
     */
    private function dbCredentialsAreValid(array $credentials): bool
    {
        $this->setDatabaseCredentials($credentials);

        try {
            DB::statement("SHOW TABLES");
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Apply database credentials to the runtime configuration for validation and migration.
     *
     * @param array $credentials
     * @return void
     */
    private function setDatabaseCredentials(array $credentials): void
    {
        $default = config('database.default');

        config([
            "database.connections.{$default}.host" => $credentials['host'],
            "database.connections.{$default}.database" => $credentials['database'],
            "database.connections.{$default}.username" => $credentials['username'],
            "database.connections.{$default}.password" => $credentials['password']
        ]);
    }

    /**
     * Handle installer AJAX actions such as changing the interface language.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ajax(Request $request): JsonResponse
    {
        if ($request->input('action')) {
            switch ($request->input('action')) {
                case 'change_lng':

                    if ($request->input('locale')) {
                        $locale = (string)$request->input('locale');

                        if (in_array($locale, Config::get('app.locales', []), true)) {
                            Session::put('install.locale', $locale);
                            app()->setLocale($locale);

                            Cookie::queue(
                                Cookie::forever('lang', $locale));
                        }
                    }

                    return response()->json(['result' => true]);
            }
        }

        return response()->json(['result' => false]);
    }
}
