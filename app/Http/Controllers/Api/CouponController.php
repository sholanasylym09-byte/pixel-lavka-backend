<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckCouponRequest;
use App\Http\Resources\CouponResource;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private CouponService $coupons) {}

    public function index(Request $request)
    {
        return CouponResource::collection($this->coupons->listFor($request->user()));
    }

    /** Проверка купона перед оформлением заказа (те же правила, что и при заказе). */
    public function check(CheckCouponRequest $request): CouponResource
    {
        $coupon = $this->coupons->findUsable($request->user(), $request->validated('code'));

        return new CouponResource($coupon->load('miniGame'));
    }
}
