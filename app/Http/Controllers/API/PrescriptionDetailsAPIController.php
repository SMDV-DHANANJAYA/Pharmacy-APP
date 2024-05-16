<?php

namespace App\Http\Controllers\API;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\PrescriptionDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PrescriptionDetailsAPIController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'prescription_id' => 'required'
        ]);

        try{
            $prescriptionDetails = PrescriptionDetails::where('prescription_id',$validatedData['prescription_id'])->get();

            return response()->json([
                'data' => $prescriptionDetails,
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
            'prescription_id' => 'required|integer',
            'medication_id' => 'required|integer',
            'count' => 'required|integer'
        ]);

        try{
            $prescription = Prescription::find($validatedData['prescription_id']);
            if (!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $medicationId = $validatedData['medication_id'];

            $medication = Medication::find($medicationId);

            if (!$medication){
                return response()->json([
                    'data' => null,
                    'message' => $medicationId . ' Medication Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            if($medication->quantity < $validatedData['count']){
                return response()->json([
                    'data' => null,
                    'message' => $medication->name . ' Medication Quantity Not Enough For This Prescription'
                ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }
            else{
                $medication->quantity = $medication->quantity - $validatedData['count'];
                $medication->save();
            }

            $prescription->total_amount = $prescription->total_amount + ($validatedData['count'] * $medication->unit_price);
            $prescription->save();

            $prescriptionDetail = PrescriptionDetails::create($validatedData);

            return response()->json([
                'data' => $prescriptionDetail,
                'message' => 'Prescription Details Created Successfully'
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
            $prescriptionDetail = PrescriptionDetails::find($id);

            if ($prescriptionDetail){
                return response()->json([
                    'data' => $prescriptionDetail,
                    'message' => 'Data Retrieve Successfully'
                ],ResponseAlias::HTTP_OK);
            }
            else{
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Detail Not Found'
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
            'count' => 'required|integer',
        ]);

        try{
            $prescriptionDetail = PrescriptionDetails::find($id);

            if(!$prescriptionDetail){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Detail Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription = $prescriptionDetail->prescription;

            $medication = $prescriptionDetail->medication;

            if (!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            if (!$medication){
                return response()->json([
                    'data' => null,
                    'message' => 'Medication Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            if($prescriptionDetail->count != $validatedData['count']){
                if($prescriptionDetail->count < $validatedData['count']){
                    $medication->quantity = $medication->quantity - ($validatedData['count'] - $prescriptionDetail->count);
                    $prescription->total_amount = $prescription->total_amount + (($validatedData['count'] - $prescriptionDetail->count) * $medication->unit_price);
                }
                else{
                    $medication->quantity = $medication->quantity + ($prescriptionDetail->count - $validatedData['count']);
                    $prescription->total_amount = $prescription->total_amount - (($prescriptionDetail->count - $validatedData['count']) * $medication->unit_price);
                }

                $medication->save();
                $prescription->save();

                $prescriptionDetail->count = $validatedData['count'];
                $prescriptionDetail->save();
            }

            return response()->json([
                'data' => $prescriptionDetail->refresh(),
                'message' => 'Prescription Detail Updated Successfully'
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

            $prescriptionDetail = PrescriptionDetails::find($id);

            if(!$prescriptionDetail){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Detail Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription = $prescriptionDetail->prescription;
            if(!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Related To Prescription Details Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $medication = $prescriptionDetail->medication;
            if(!$medication){
                return response()->json([
                    'data' => null,
                    'message' => 'Medication Related To Prescription Details Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription->total_amount = $prescription->total_amount - ($medication->unit_price * $prescriptionDetail->count);
            $prescription->save();

            $medication->quantity = $medication->quantity + $prescriptionDetail->count;
            $medication->save();

            if ($user->role == UserRole::OWNER){
                $prescriptionDetail->forceDelete();
            }
            else{
                $prescriptionDetail->delete();
            }

            return response()->json([
                'data' => $prescriptionDetail,
                'message' => 'Prescription Detail Deleted Successfully'
            ],ResponseAlias::HTTP_OK);

        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
