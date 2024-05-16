<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthAPIController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function signup(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'username' => 'required',
            'name' => 'required',
            'role' => ['required',Rule::enum(UserRole::class)],
            'password' => 'required|string|min:8',
        ]);

        try{
            $user = User::create([
                'username' => $validatedData['username'],
                'name' => $validatedData['name'],
                'role' => $validatedData['role'],
                'password' => Hash::make($validatedData['password']),
            ]);

            return response()->json([
                'data' => $user,
                'message' => 'User Created Successfully'
            ],ResponseAlias::HTTP_CREATED);
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required|string|min:8',
        ]);

        try{
            if (Auth::attempt($validatedData)) {
                $token = $request->user()->createToken(Carbon::now());

                return response()->json([
                    'data' => Auth::user(),
                    'access_token' => $token->plainTextToken,
                    'message' => 'User Login Successfully'
                ],ResponseAlias::HTTP_OK);
            }

            return response()->json([
                'data' => null,
                'message' => 'Invalid Username Or Password'
            ], ResponseAlias::HTTP_UNAUTHORIZED);
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try{
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'data' => null,
                'message' => 'Logout Successfully'
            ],ResponseAlias::HTTP_OK);
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
