<?php

namespace App\Http\Controllers\Api\V100;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource\PosUserResource;
use App\Http\Resources\RechargeHistoryResource;
use App\Models\OrderDetail;
use App\Repositories\Interfaces\Admin\Addon\OfflineMethodInterface;
use App\Repositories\Interfaces\Admin\Addon\WalletInterface;
use App\Repositories\Interfaces\Admin\AddonInterface;
use App\Repositories\Interfaces\Admin\CurrencyInterface;
use App\Repositories\Interfaces\Admin\LanguageInterface;
use App\Repositories\Interfaces\Admin\OrderInterface;
use App\Repositories\Interfaces\UserInterface;
use App\Traits\ApiReturnFormatTrait;
use App\Utility\AppSettingUtility;
use Carbon\Carbon;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\UserCard;
use App\Models\UserReferralCode;
use Sentinel;

class UserController extends Controller
{
    use ApiReturnFormatTrait;

    public function profile(Request $request,OrderInterface $order): \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }

            $digital_products = $this->digitalProductOrders($user->id);

            $data = [
                'id'                => $user->id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'referral_id'       => $user->referral_id,
                'card_number'       => $user->card_number,
                'card_status'       => $user->card_status,
                'available_subscription_days'       => $user->available_subscription_days,
                'total_referral_users'  => $user->total_referral_users,
                'total_active_referral_users'   => $user->total_active_referral_users,
                'email'             => nullCheck($user->email),
                'phone'             => nullCheck($user->phone),
                'gender'            => nullCheck($user->gender),
                'date_of_birth'     => nullCheck($user->date_of_birth),
                // 'currency_code'     => nullCheck($user->currency_code),
                'image'             => $user->profile_image,
                'socials'           => $user->socials,
                'facebook'          => $user->socials && count($user->socials) > 0 && array_key_exists('facebook',$user->socials) ? $user->socials['facebook'] : '',
                'twitter'           => $user->socials && count($user->socials) > 0 && array_key_exists('twitter',$user->socials) ? $user->socials['twitter'] : '',
                'linkedin'          => $user->socials && count($user->socials) > 0 && array_key_exists('linkedin',$user->socials) ? $user->socials['linkedin'] : '',
                'instagram'         => $user->socials && count($user->socials) > 0 && array_key_exists('instagram',$user->socials) ? $user->socials['instagram'] : '',
                'pinterest'         => $user->socials && count($user->socials) > 0 && array_key_exists('pinterest',$user->socials) ? $user->socials['pinterest'] : '',
                'youtube'           => $user->socials && count($user->socials) > 0 && array_key_exists('youtube',$user->socials) ? $user->socials['youtube'] : '',
                'is_ordered_digital_product'  => $digital_products && $digital_products > 0,
            ];
            return $this->responseWithSuccess(__('Data Retrieved Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function updateProfile(Request $request,UserInterface $userInterface): \Illuminate\Http\JsonResponse
    {
        $user = \Auth::user() ?? null;
        if ($request->token) {
            try {
                if (!$user = JWTAuth::parseToken()->authenticate()) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            } catch (\Exception $e) {
                return $this->responseWithError(__('unauthorized_user'), [], 401);
            }
        }
        $validator = Validator::make($request->all(), [
            'first_name'    => 'required|max:50|regex:/^[a-zA-Z\s]*$/',
            'last_name'     => 'required|max:50|regex:/^[a-zA-Z\s]*$/',
            'gender'        => 'required',
            'date_of_birth' => 'required',
        ]);

        if (!$request->uid)
        {
            $validator = Validator::make($request->all(), [
                'email'         => 'required_without:phone|max:50|email|unique:users,email,'.$user->id,
                'phone'         => 'required_without:email|min:4|max:20|unique:users,phone,'.$user->id,
                'first_name'    => 'required|max:50|regex:/^[a-zA-Z\s]*$/',
                'last_name'     => 'required|max:50|regex:/^[a-zA-Z\s]*$/',
                'gender'        => 'required',
                'date_of_birth' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
            }
        }

        if ($validator->fails()) {
            return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
        }

        try {

            if($request->phone):
                $request['phone'] = str_replace(' ','',$request->phone);
            else:
                $request['phone'] = $user->phone;
            endif;

            $socials = [];

            if ($request->facebook)
            {
                $socials['facebook'] = $request->facebook;
            }

            if ($request->twitter)
            {
                $socials['twitter'] = $request->twitter;
            }

            if ($request->linkedin)
            {
                $socials['linkedin'] = $request->linkedin;
            }

            if ($request->instagram)
            {
                $socials['instagram'] = $request->instagram;
            }

            if ($request->pinterest)
            {
                $socials['pinterest'] = $request->pinterest;
            }

            if ($request->youtube)
            {
                $socials['youtube'] = $request->youtube;
            }

            $request['id']              = $user->id;
            $request['socials']         = $socials;
            $request['email']           = $request->email ? : $user->email;
            $request['date_of_birth']   = $request->date_of_birth ? Carbon::parse($request->date_of_birth)->format('Y-m-d') : null;

            $user = $userInterface->update($request);
            JWTAuth::invalidate(JWTAuth::getToken());

            try {
                if (!$token = JWTAuth::fromUser($user)) {
                    return $this->responseWithError(__('Invalid credentials'), [], 401);
                }
            } catch (JWTException $e) {
                return $this->responseWithError(__('Unable to login, please try again'), [], 422);

            } catch (ThrottlingException $e) {
                return $this->responseWithError(__('Suspicious activity on your ip, try after').' '. $e->getDelay() .' '.  __('seconds'), [], 422);

            } catch (NotActivatedException $e) {
                return $this->responseWithError(__('Account is not activated. Verify your account first'),[],400);

            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), [], 500);
            }

            $data = [
                'id'                => $user->id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'email'             => nullCheck($user->email),
                'phone'             => nullCheck($user->phone),
                'card_number'       => $user->card_number,
                'gender'            => nullCheck($user->gender),
                'date_of_birth'     => nullCheck($user->date_of_birth),
                'image'             => $user->profile_image,
                'token'             => $token,
            ];

            return $this->responseWithSuccess(__('Profile Updated Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password'       => 'min:6|max:32',
            'new_password'           => 'required|min:6|max:32|required_with:confirm_password|same:confirm_password',
            'confirm_password'       => 'required|min:6|max:32',
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
        }

        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }

            if (Hash::check($request->new_password, $user->password)) {
                return $this->responseWithError(__('New password cannot be same as current password'), [], 200);
            }
            if(Hash::check($request->current_password, $user->password)){
                $user->password = bcrypt($request->new_password);
                $user->last_password_change = Carbon::now();
                $user->save();
                return $this->responseWithSuccess(__('Password Changed Successfully'), [], 200);

            } else{
                return $this->responseWithError(__('Current Password does not match with old password'), [], 200);
            }

        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function myWallet(Request $request,WalletInterface $wallet,UserInterface $userInterface): \Illuminate\Http\JsonResponse
    {
        $user = \Auth::user() ?? null;
        if ($request->token) {
            try {
                if (!$user = JWTAuth::parseToken()->authenticate()) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            } catch (\Exception $e) {
                return $this->responseWithError(__('unauthorized_user'), [], 401);
            }
        }
        try {
            $data = [
                'recharges' => RechargeHistoryResource::collection($wallet->all()->where('user_id',$user->id)->latest()->paginate(get_pagination('api_paginate'))),
                'balance' => new PosUserResource( $userInterface->get($user->id))
            ];
            return $this->responseWithSuccess(__('Wallet Data Retrieved'), $data, 200);

        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            if(isDemoServer())
            {
                return $this->responseWithError(__('This action is not allowed in demo server'), [], 422);
            }

            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }
            if($user->is_deleted == 1)
            {
                return $this->responseWithError(__('User Not Found'), [], null);
            }

            if($user->user_type != 3)
            {
                return $this->responseWithError(__('you_are_not_allowed_to_perform_this_action'), [], 403);
            }

            $user->is_deleted = 1;
            $user->save();
            return $this->responseWithSuccess(__('account_deleted'), [], 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function apiRecharge(Request $request,CurrencyInterface $currency, OfflineMethodInterface $offlineMethod,AddonInterface $addon,LanguageInterface $language)
    {
        try {
            $user = null;
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }

            if($user)
            {
                Sentinel::login($user);
            }

            if ($request->lang)
            {
                $locale = $request->lang;
            }
            else{
                $locale = settingHelper('default_language');
            }

            app()->setLocale($locale);

                $ngn_exchange_rate      = 1;
                $is_paystack_activated  = settingHelper('is_paystack_activated') == 1;
                $is_flutterwave_activated  = settingHelper('is_flutterwave_activated') == 1;
                $ngn = AppSettingUtility::currencies()->where('code','NGN')->first();
                if($ngn):
                    $ngn_exchange_rate     = $ngn->exchange_rate;
                else:
                    $is_paystack_activated    = 0;
                    $is_flutterwave_activated = 0;
                endif;

                $data = [
                    'token'             => $request->token,
                    'amount'            => $request->amount,
                    'code'              => 'USD',
                    'lang'              => $locale,
                    'trx_id'            => str::random(10),
                    'currency'          => $request->curr,
                    'indian_currency'   => $currency->currencyByCode('INR'),
//                    'jazz_data'         => $this->jazzCashPayment(),
                    'jazz_url'          => config('jazz_cash.TRANSACTION_POST_URL'),
                    'addons'            => $addon->activePlugin(),
                    'ngn_exchange_rate' => $ngn_exchange_rate,
                    'paystack_activated'=> $is_paystack_activated,
                    'fw_activated'      => $is_flutterwave_activated,
                    'dark_logo'         => settingHelper('dark_logo') != [] && @is_file_exists(settingHelper('dark_logo')['original_image']) ?  get_media(@settingHelper('dark_logo')['original_image'], @settingHelper('dark_logo')['storage']) : static_asset('images/default/dark-logo.png'),
                    'favicon'           => @is_file_exists(@settingHelper('favicon')['image_57x57_url']) ? get_media(settingHelper('favicon')['image_57x57_url']) : static_asset('images/ico/apple-touch-icon-57-precomposed.png'),
                    'default_assets'    => [
                        'preloader'                     => static_asset('images/default/preloader.gif'),
                        'review_image'                  => static_asset('images/others/env.svg'),
                    ]
                ];

            return view('api.recharge',$data);

        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }

    public function digitalProductOrders($token=null)
    {
        $user_id =  $token;
        if ($user_id) {
            return OrderDetail::with('product:id,thumbnail,slug,product_file_id', 'order')
                ->whereHas('order', function ($query) use ($user_id){
                    $query->where('user_id', $user_id);
                    $query->where('payment_status','paid');
                    $query->where('is_deleted', 0);
                    $query->where('status', 1);
                    $query->when(settingHelper('seller_system') != 1, function ($q) {
                        $q->where('seller_id',1);
                    });
                })->whereHas('product', function($q){
                    $q->where('product_file_id','!=',null);
                    $q->where('is_digital',1);
                })
                ->Where( function($qu){
                    $qu->whereHas('refund', function($q){
                        $q->where('status','!=','approved');
                        $q->Where('status','!=','processed');
                    });
                    $qu->orWhereDoesntHave('refund');
                })

                ->groupBy('order_id')
                ->latest()
                ->count();
        } else {
            return [];
        }
    }
    
    public function referral_users_lists(Request $request, $user_id) : \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }

            $users = User::where('referral_user_id', $user_id)->get(['id', 'first_name', 'last_name', 'phone', 'card_status'])->makeHidden(['profile_image', 'user_profile_image', 'shipping_address', 'billing_address', 'last_recharge', 'total_referral_users', 'total_active_referral_users', 'first_name', 'last_name'])->toArray();

            return $this->responseWithSuccess(__('Data Retrieved Successfully'), $users, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }
    
    public function total_referral_users(Request $request, $user_id) : \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }

            $data = [
                'result' => User::where('referral_user_id', $user_id)->count(),
                'result_active' => User::where('referral_user_id', $user_id)->where('card_status', 'Active')->count()
                
            ];

            return $this->responseWithSuccess(__('Data Retrieved Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }
    
    public function total_active_referral_users(Request $request, $user_id) : \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }

            $data = [
                'result' => User::where('referral_user_id', $user_id)->where('card_status', 'Active')->count()
            ];
            return $this->responseWithSuccess(__('Data Retrieved Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }
    
    public function update_card_number(Request $request, $user_id) : \Illuminate\Http\JsonResponse
{
    try {
        $user = \Auth::user() ?? null;
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|max:50|unique:users,card_number,'.$user->id,
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
        }

        $user = User::find($user_id);
        $user->card_number = $request->card_number;
        $user->status = 'Active';
        $user->update();

        $data = [
            'card_number' => $user->card_number
        ];
        return $this->responseWithSuccess(__('Card Updated Successfully'), $data, 200);
    } catch (\Exception $e) {
        return $this->responseWithError($e->getMessage(), [], null);
    }
}

    
    public function update_balance_value(Request $request, $user_id) : \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }
            
            $validator = Validator::make($request->all(), [
                'value' => 'required|numeric|min:0',
                'type' => 'required|string|in:subtract,add',
            ]);
    
            if ($validator->fails()) {
                return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
            }
        
            $value = (float) $request->value;
            
            $user = User::find($user_id);
            if($request->type === 'add')
            {
                $user->balance += $value;
            } 
            elseif($request->type === 'subtract')
            {
                if(($user->balance - $value) < 0)
                {
                    $user->balance = 0;
                }else{
                    $user->balance -= $value;
                }
            }
            $user->update();

            $data = [
                'balance' => $user->balance
            ];
            return $this->responseWithSuccess(__('Balance Updated Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }
    
    public function make_card_active(Request $request, $user_id) : \Illuminate\Http\JsonResponse
    {
        try {
            $user = \Auth::user() ?? null;
            if ($request->token) {
                try {
                    if (!$user = JWTAuth::parseToken()->authenticate()) {
                        return $this->responseWithError(__('unauthorized_user'), [], 401);
                    }
                } catch (\Exception $e) {
                    return $this->responseWithError(__('unauthorized_user'), [], 401);
                }
            }
            
            $validator = Validator::make($request->all(), [
                'total_days' => 'required|numeric|min:0',
            ]);
    
            if ($validator->fails()) {
                return $this->responseWithError(__('Required field missing'), $validator->errors(), 422);
            }
        
            
            $user = User::find($user_id);
            
            $total_days = (int) $request->total_days;
            
            $oldUserCard = UserCard::where('user_id', $user->id)->where('status', 'Active')->first();
            
            if(!empty($oldUserCard))
            {
                $prevAvailableDays = strtotime($oldUserCard->end_date) - time();
                $prevAvailableDays = round($prevAvailableDays / (60 * 60 * 24));
                
                if($prevAvailableDays > 0)
                {
                    $total_days += $prevAvailableDays;
                }
                
                $oldUserCard->status = 'Inactive';
                $oldUserCard->update();
            }
            
            $userCard = new UserCard;
            $userCard->user_id = $user->id;
            $userCard->start_date = date('Y-m-d H:i:s');
            $userCard->end_date = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s")."+{$total_days} days"));
            $userCard->total_days = $total_days;
            $userCard->card_number = $user->card_number;
            $userCard->status = 'Active';
            $userCard->save();
            
            $user->card_status = 'Active';
            $user->update();

            $data = [
                'card_number'    => $userCard->card_number,
                'total_days'    => $userCard->total_days,
                'end_date'    => $userCard->end_date
            ];
            return $this->responseWithSuccess(__('Card Active Successfully'), $data, 200);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), [], null);
        }
    }
}
