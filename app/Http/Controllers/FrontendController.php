<?php

namespace App\Http\Controllers;

use App\DTO\Create\RedirectCreateData;
use App\DTO\Create\SubscriberCreateData;
use App\DTO\Update\ReadySentReadData;
use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use App\Http\Requests\Frontend\AddSubRequest;
use App\Models\Category;
use App\Models\Subscribers;
use App\Repositories\ReadySentRepository;
use App\Repositories\RedirectRepository;
use App\Repositories\SubscriberRepository;
use App\Services\SendMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FrontendController extends Controller
{
    private const TRACKING_PIXEL_SIZE = 1;

    /**
     * Inject repositories and services used by public tracking and subscription endpoints.
     *
     * @param SubscriberRepository $subscriberRepository
     * @param ReadySentRepository $readySentRepository
     * @param RedirectRepository $redirectRepository
     * @param SendMailService $sendMailService
     */
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository,
        private readonly ReadySentRepository $readySentRepository,
        private readonly RedirectRepository $redirectRepository,
        private readonly SendMailService $sendMailService,
    ) {
    }

    /**
     * Record an email open event and return a one-pixel tracking GIF.
     *
     * @param int $subscriber
     * @param int $template
     * @return Response
     */
    public function pic(int $subscriber, int $template): Response
    {
        $this->readySentRepository->markAsRead(
            new ReadySentReadData(
                subscriberId: $subscriber,
                templateId: $template,
            )
        );

        $image = imagecreatetruecolor(self::TRACKING_PIXEL_SIZE, self::TRACKING_PIXEL_SIZE);
        imagefilledrectangle($image, 0, 0, self::TRACKING_PIXEL_SIZE, self::TRACKING_PIXEL_SIZE, 0xFFFFFF);

        ob_start();
        imagegif($image);
        $content = ob_get_clean();
        imagedestroy($image);

        return response($content ?: '', 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Record a redirect click and send the subscriber to the decoded external URL.
     *
     * @param string $ref
     * @param int $subscriber
     * @return RedirectResponse
     */
    public function redirectLog(string $ref, int $subscriber): RedirectResponse
    {
        abort_if($ref === '', 404);

        $url = base64_decode($ref, true) ?: '';
        abort_unless($this->isRedirectUrlAllowed($url), 404);

        $subscriberModel = Subscribers::query()->find($subscriber);

        $this->redirectRepository->add(
            new RedirectCreateData(
                url: $url,
                time: now(),
                email: $subscriberModel->email ?? '',
            )
        );

        return redirect()->away($url);
    }

    /**
     * Deactivate a subscriber after validating the unsubscribe token.
     *
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function unsubscribe(int $subscriber, string $token): View
    {
        $subscriberModel = $this->subscriberRepository->find($subscriber);

        abort_if(!$subscriberModel || $subscriberModel->token !== $token, 404);

        $email = $subscriberModel->email;
        $subscriberModel->active = 0;
        $subscriberModel->save();

        return view('frontend.unsubscribe', [
            'msg' => str_replace('%EMAIL%', $email, __('frontend.str.address_has_been_deleted')),
        ]);
    }

    /**
     * Activate a subscriber after validating the confirmation token.
     *
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function subscribe(int $subscriber, string $token): View
    {
        $subscriberModel = $this->subscriberRepository->find($subscriber);

        abort_if(!$subscriberModel || $subscriberModel->token !== $token, 404);

        $subscriberModel->active = 1;
        $subscriberModel->save();

        return view('frontend.subscribe');
    }

    /**
     * Show the standalone public subscription form.
     *
     * @return View
     */
    public function form(): View
    {
        return view('frontend.subform', [
            'category' => Category::query()->orderBy('name')->get(),
            'title' => 'Subform',
        ]);
    }

    /**
     * Create a subscriber from the public subscription form and send confirmation emails.
     *
     * @param AddSubRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function addSub(AddSubRequest $request): JsonResponse
    {
        $settings = SettingsHelper::getInstance();
        $validated = $request->validated();

        $subscriber = $this->subscriberRepository->createFrontendSubscriber(
            new SubscriberCreateData(
                email: $validated['email'],
                name: $validated['name'] ?? '',
                active: $this->requiresConfirmation($settings) ? 0 : 1,
                token: StringHelper::token(),
                timeSent: now(),
                categoryIds: $validated['categoryId'] ?? [],
            )
        );

        try {
            $this->sendMailService->sendFrontendSubscriberEmails($subscriber);
        } catch (\Throwable $exception) {
            Log::warning('Failed to send frontend subscriber email.', [
                'subscriber_id' => $subscriber->id ?? null,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'result' => 'success',
            'msg' => __('frontend.msg.subscription_is_formed'),
        ]);
    }

    /**
     * Return the public list of subscriber categories used by embedded forms.
     *
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        return response()->json([
            'items' => Category::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Determine whether new frontend subscribers must confirm their email address.
     *
     * @param SettingsHelper $settings
     * @return bool
     */
    private function requiresConfirmation(SettingsHelper $settings): bool
    {
        return (int) $settings->getValueForKey('REQUIRE_SUB_CONFIRMATION') === 1;
    }

    /**
     * Validate that a redirect tracking URL uses an allowed external HTTP scheme.
     *
     * @param string $url
     * @return bool
     */
    private function isRedirectUrlAllowed(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }
}
