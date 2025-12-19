<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Services\TransferService;
use Exception;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
  public function __construct(private TransferService $transferService) {}

  public function transfer(TransferRequest $request): JsonResponse
  {
    try {
      $data = $request->validated();
      $this->transferService->execute($data['payer'], $data['payee'], $data['value']);

      return response()->json(['message' => 'Transfer was successful'], 200);
    } catch (Exception $e) {
      $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
      return response()->json(['message' => $e->getMessage()], $statusCode);
    }
  }
}
