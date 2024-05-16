<?php

namespace App\Http\Controllers\API;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\PrescriptionDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PrescriptionAPIController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'customer_id' => 'required'
        ]);

        try{
            $prescriptions = Prescription::where('customer_id',$validatedData['customer_id'])->get();

            return response()->json([
                'data' => $prescriptions,
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
            'customer_id' => 'required',
            'note' => 'required',
            'total_amount' => 'required|numeric',
            'prescription_details' => 'required|array',
            'prescription_details.*.medication_id' => 'required|integer',
            'prescription_details.*.count' => 'required|integer'
        ]);

        try{
            DB::beginTransaction();

            $customer = Customer::find($validatedData['customer_id']);
            if (!$customer){
                return response()->json([
                    'data' => null,
                    'message' => 'Customer Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription = Prescription::create($validatedData);

            foreach ($validatedData['prescription_details'] as $prescription_detail){

                $medicationId = $prescription_detail['medication_id'];

                $medication = Medication::find($medicationId);

                if (!$medication){
                    DB::rollBack();

                    return response()->json([
                        'data' => null,
                        'message' => $medicationId . ' Medication Not Found'
                    ],ResponseAlias::HTTP_NOT_FOUND);
                }

                if($medication->quantity < $prescription_detail['count']){
                    DB::rollBack();

                    return response()->json([
                        'data' => null,
                        'message' => $medication->name . ' Medication Quantity Not Enough For This Customer'
                    ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
                }
                else{
                    $medication->quantity = $medication->quantity - $prescription_detail['count'];
                    $medication->save();
                }

                PrescriptionDetails::create([
                    'prescription_id' => $prescription->id,
                    'medication_id' => $medication->id,
                    'count' => $prescription_detail['count']
                ]);
            }

            DB::commit();

            return response()->json([
                'data' => $prescription,
                'message' => 'Prescription Created Successfully'
            ],ResponseAlias::HTTP_CREATED);
        } catch (\Exception $e){
            DB::rollBack();
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
            $prescription = Prescription::find($id);

            if ($prescription){
                return response()->json([
                    'data' => $prescription,
                    'message' => 'Data Retrieve Successfully'
                ],ResponseAlias::HTTP_OK);
            }
            else{
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
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
            'note' => 'required',
        ]);

        try{
            $prescription = Prescription::find($id);

            if(!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription->note = $validatedData['note'];
            $prescription->save();

            return response()->json([
                'data' => $prescription->refresh(),
                'message' => 'Prescription Updated Successfully'
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

            $prescription = Prescription::find($id);

            if(!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $perscriptionDetails = $prescription->prescription_details();

            if ($user->role == UserRole::OWNER){
                $perscriptionDetails->forceDelete();
                $prescription->forceDelete();
            }
            else{
                $perscriptionDetails->delete();
                $prescription->delete();
            }

            return response()->json([
                'data' => null,
                'message' => 'Prescription Deleted Successfully'
            ],ResponseAlias::HTTP_OK);

        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
