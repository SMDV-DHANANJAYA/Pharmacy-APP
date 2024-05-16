<?php

namespace App\Http\Controllers\API;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\PrescriptionDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class MedicationAPIController extends Controller
{
    /**
     * @return JsonResponse
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try{
            $medications = Medication::all();

            return response()->json([
                'data' => $medications,
                'message' => 'Data Retrieve Successfully'
            ],ResponseAlias::HTTP_OK);
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (($user->role == UserRole::MANAGER) || ($user->role == UserRole::CASHIER)){
            return response()->json([
                'data' => null,
                'message' => "Unauthorized Action"
            ],ResponseAlias::HTTP_UNAUTHORIZED);
        }

        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'unit_price' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1',
        ]);

        try{
            $medication = Medication::create($validatedData);

            return response()->json([
                'data' => $medication,
                'message' => 'Medication Created Successfully'
            ],ResponseAlias::HTTP_CREATED);
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param string $id
     * @return JsonResponse
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try{
            $medication = Medication::find($id);

            if ($medication){
                return response()->json([
                    'data' => $medication,
                    'message' => 'Data Retrieve Successfully'
                ],ResponseAlias::HTTP_OK);
            }
            else{
                return response()->json([
                    'data' => null,
                    'message' => 'Medication Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'unit_price' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1',
        ]);

        try{
            $medication = Medication::find($id);

            if(!$medication){
                return response()->json([
                    'data' => null,
                    'message' => 'Medication Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $medication->name = $validatedData['name'];
            $medication->description = $validatedData['description'];
            $medication->unit_price = $validatedData['unit_price'];
            $medication->quantity = $validatedData['quantity'];
            $medication->save();

            return response()->json([
                'data' => $medication->refresh(),
                'message' => 'Medication Updated Successfully'
            ],ResponseAlias::HTTP_OK);
        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param string $id
     * @return JsonResponse
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try{
            $user = Auth::user();
            if ($user->role == UserRole::CASHIER){
                return response()->json([
                    'data' => null,
                    'message' => "Unauthorized Action"
                ],ResponseAlias::HTTP_UNAUTHORIZED);
            }

            $medication = Medication::find($id);

            if(!$medication){
                return response()->json([
                    'data' => null,
                    'message' => 'Medication Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $isMedicationExistsInPrescription = PrescriptionDetails::where('medication_id',$medication->id)->exists();
            if($isMedicationExistsInPrescription){
                // we can stop delete process because this medication already use in prescription details table
                // just delete for demo
            }

            if ($user->role == UserRole::OWNER){
                $medication->forceDelete();
            }
            else{
                $medication->delete();
            }

            return response()->json([
                'data' => null,
                'message' => 'Medication Deleted Successfully'
            ],ResponseAlias::HTTP_OK);

        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
