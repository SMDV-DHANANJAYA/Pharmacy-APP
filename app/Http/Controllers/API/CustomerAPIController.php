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

class CustomerAPIController extends Controller
{
    /**
     * @return JsonResponse
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try{
            $customers = Customer::all();

            return response()->json([
                'data' => $customers,
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
            'nic' => 'required',
            'age' => 'required|integer|min:0',
            'mobile' => 'required',
            'address' => 'required',
            'prescriptions' => 'required|array',
            'prescriptions.*.note' => 'required',
            'prescriptions.*.total_amount' => 'required|numeric',
            'prescriptions.*.prescription_details' => 'required|array',
            'prescriptions.*.prescription_details.*.medication_id' => 'required|integer',
            'prescriptions.*.prescription_details.*.count' => 'required|integer'
        ]);

        try{
            DB::beginTransaction();

            $customer = Customer::create($validatedData);

            foreach ($validatedData['prescriptions'] as $prescriptionData){
                $prescription = Prescription::create([
                    'customer_id' => $customer->id,
                    'note' => $prescriptionData['note'],
                    'total_amount' => $prescriptionData['total_amount']
                ]);

                foreach ($prescriptionData['prescription_details'] as $prescription_detail){

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
            }

            DB::commit();

            return response()->json([
                'data' => $customer,
                'message' => 'Customer Created Successfully'
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
            $customer = Customer::find($id);

            if ($customer){
                return response()->json([
                    'data' => $customer,
                    'message' => 'Data Retrieve Successfully'
                ],ResponseAlias::HTTP_OK);
            }
            else{
                return response()->json([
                    'data' => null,
                    'message' => 'Customer Not Found'
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
            'nic' => 'required',
            'age' => 'required|integer|min:0',
            'mobile' => 'required',
            'address' => 'required',
        ]);

        try{
            $customer = Customer::find($id);

            if(!$customer){
                return response()->json([
                    'data' => null,
                    'message' => 'Customer Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $customer->name = $validatedData['name'];
            $customer->nic = $validatedData['nic'];
            $customer->age = $validatedData['age'];
            $customer->mobile = $validatedData['mobile'];
            $customer->address = $validatedData['address'];
            $customer->save();

            return response()->json([
                'data' => $customer->refresh(),
                'message' => 'Customer Updated Successfully'
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

            $customer = Customer::find($id);

            if(!$customer){
                return response()->json([
                    'data' => null,
                    'message' => 'Customer Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescription = Prescription::where('customer_id',$customer->id)->get();

            if(!$prescription){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            $prescriptionDetails = PrescriptionDetails::where('prescription_id',$prescription->id)->get();

            if(!$prescriptionDetails){
                return response()->json([
                    'data' => null,
                    'message' => 'Prescription Details Not Found'
                ],ResponseAlias::HTTP_NOT_FOUND);
            }

            if ($user->role == UserRole::OWNER){
                $customer->forceDelete();
                $prescription->forceDelete();
                $prescriptionDetails->forceDelete();
            }
            else{
                $customer->delete();
                $prescription->delete();
                $prescriptionDetails->delete();
            }

            return response()->json([
                'data' => $customer,
                'message' => 'Customer Deleted Successfully'
            ],ResponseAlias::HTTP_OK);

        } catch (\Exception $e){
            return response()->json([
                'data' => null,
                'message' => $e->getMessage()
            ],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
