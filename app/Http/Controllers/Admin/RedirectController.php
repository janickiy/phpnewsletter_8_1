<?php

namespace App\Http\Controllers\Admin;


use App\Services\DownloadService;
use App\Services\ProjectAccess;
use App\Services\RedirectReportFilter;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;


class RedirectController  extends Controller
{
    /**
     * Inject the download service used to generate redirect reports.
     */
    public function __construct(private DownloadService $downloadService)
    {
        parent::__construct();
    }

    /**
     * Show the redirect tracking overview page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.redirect.index', [
            'infoAlert' => __('frontend.hint.redirect_index'),
            'title' => __('frontend.title.redirect_index'),
        ]);
    }

    /**
     * Clear all redirect tracking statistics.
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        abort_unless(auth()->user()->canManageProjects(), 403);
        try {
            ProjectAccess::redirects(Redirect::query(), 'manage')->delete();

            return response()->json([
                'success' => true,
                'message' => __('message.statistics_cleared'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('frontend.str.delete_error'),
            ], 500);
        }
    }

    /**
     * Download redirect tracking details for a tracked URL.
     *
     * @param string $url
     * @return StreamedResponse
     */
    public function download(string $url): StreamedResponse
    {
        return $this->downloadService->redirect($url, request()->query('newsletter'));
    }

    /**
     * Show redirect tracking details for a tracked URL.
     *
     * @param string $url
     * @return View
     */
    public function info(string $url): View
    {
        $normalized = strtr($url, '-_', '+/');
        $normalized .= str_repeat('=', (4 - strlen($normalized) % 4) % 4);
        $decodedUrl = base64_decode($normalized, true);
        abort_unless($decodedUrl !== false, 404);
        $newsletter = request()->query('newsletter');
        $rows = RedirectReportFilter::apply(
            ProjectAccess::redirects(Redirect::query())->where('url', $decodedUrl),
            $newsletter
        );
        abort_unless($rows->exists(), 404);
        return view('admin.redirect.info', [
            'url' => $url,
            'newsletter' => $newsletter,
            'infoAlert' => __('frontend.hint.redirectlog_info'),
            'title' => __('frontend.title.redirect_info'),
        ]);
    }
}
