<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Services\TransferService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Simple Fintech API',
    description: 'API for financial transfers between wallets'
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
class TransferController extends Controller
{
    public function __construct(private TransferService $transferService) {}

    #[OA\Post(
        path: '/transfer',
        summary: 'Transfer funds between wallets',
        description: 'Transfers funds from a payer wallet to a payee wallet. The payer must be a customer (not a store keeper).',
        operationId: 'transfer',
        tags: ['Transfers']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['payer', 'payee', 'value'],
            properties: [
                new OA\Property(property: 'payer', type: 'integer', example: 1, description: 'ID of the payer wallet'),
                new OA\Property(property: 'payee', type: 'integer', example: 6, description: 'ID of the payee wallet'),
                new OA\Property(property: 'value', type: 'number', format: 'float', example: 100.0, description: 'Transfer amount (min: 0.01, max: 999999999.99)'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Transfer successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Transfer was successful'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request (insufficient balance, store keeper restriction, or notification failed)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Insufficient balance'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Transfer not authorized by third party',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Transfer not authorized'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Wallet not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Wallet not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    ),
                    example: ['payer' => ['The payer ID must be an integer.']]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Internal server error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Internal server error'),
            ]
        )
    )]
    public function transfer(TransferRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->transferService->execute($data['payer'], $data['payee'], $data['value']);

        return response()->json(['message' => 'Transfer was successful'], 200);
    }
}
