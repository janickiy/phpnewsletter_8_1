<?php

namespace App\Http\Controllers\Admin;

use App\Models\ReadySent;
use App\Models\Logs;
use App\Services\DownloadService;
use App\Services\ProjectAccess;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    /**
     * Inject the download service used to generate log reports.
     */
    public function __construct(private readonly DownloadService $downloadService)
    {
        parent::__construct();
    }

    /**
     * Show the mailing log overview page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.log.index', [
            'title' => __('frontend.title.log_index'),
            'infoAlert' => __('frontend.hint.log_info'),
        ]);
    }

    /**
     * Show detailed delivery results for one mailing run.
     *
     * @param int $id
     * @return View
     */
    public function info(int $id): View
    {
        abort_unless(ProjectAccess::scope(ReadySent::query())->where('log_id', $id)->exists(), 404);
        return view('admin.log.info', [
            'id' => $id,
            'infoAlert' => __('frontend.hint.log_info'),
            'title' => __('frontend.title.log_info'),
        ]);
    }

    /**
     * Download the delivery report for one mailing run.
     *
     * @param int $id
     * @return Response|StreamedResponse
     */
    public function download(int $id): Response|StreamedResponse
    {
        return $this->downloadService->log($id);
    }

    /**
     * Clear all delivery and mailing log records.
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        abort_unless(auth()->user()->canManageProjects(), 403);
        try {
            DB::transaction(function (): void {
                $logIds = ProjectAccess::scope(ReadySent::query(), 'manage')->whereNotNull('log_id')->distinct()->pluck('log_id');
                ProjectAccess::scope(ReadySent::query(), 'manage')->delete();
                Logs::query()->whereIn('id', $logIds)
                    ->whereNotIn('id', ReadySent::query()->whereNotNull('log_id')->select('log_id'))
                    ->delete();
            });

            return response()->json([
                'success' => true,
                'message' => __('frontend.msg.data_successfully_deleted'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('frontend.str.delete_error'),
            ], 500);
        }
    }
}
