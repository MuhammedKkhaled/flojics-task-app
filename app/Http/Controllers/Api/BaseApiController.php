<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

class BaseApiController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /** @var array<string, mixed> */
    private array $response = [];

    private int $statusCode = Response::HTTP_OK;

    /** @param array<string, mixed> $data */
    public function addToResponse(array $data): static
    {
        $this->response += $data;

        return $this;
    }

    public function addMessageToResponse(string $message): static
    {
        return $this->addToResponse(['message' => $message]);
    }

    public function fromResource(JsonResource $resource): static
    {
        $wrap = $resource::$wrap;

        $this->addToResponse([is_string($wrap) ? $wrap : 'data' => $resource]);

        if ($resource->resource instanceof LengthAwarePaginator) {
            $this->addToResponse($this->getPaginationResponse($resource->resource));
        }

        return $this;
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /** @return array{meta: array<string, int|string|null>} */
    public function getPaginationResponse(LengthAwarePaginator $resource): array
    {
        return [
            'meta' => [
                'total_Items' => $resource->total(),
                'items_Per_Page' => $resource->perPage(),
                'Items_in_page' => $resource->count(),
                'current_Page' => $resource->currentPage(),
                'last_Page' => $resource->lastPage(),
                'next_pageUrl' => $resource->nextPageUrl(),
                'previous_pageUrl' => $resource->previousPageUrl(),
            ],
        ];
    }

    public function toResponse(): JsonResponse
    {
        return response()->json($this->response, $this->statusCode);
    }

    /** @param array<string, mixed> $data */
    public function successResponse(
        string $message,
        array $data,
        int $statusCode = Response::HTTP_OK,
    ): JsonResponse {
        return $this
            ->setStatusCode($statusCode)
            ->addMessageToResponse($message)
            ->addToResponse($data)
            ->toResponse();
    }

    public function errorResponse(
        string $message,
        mixed $error = null,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
    ): JsonResponse {
        return $this
            ->setStatusCode($statusCode)
            ->addMessageToResponse($message)
            ->addToResponse(['error' => $error])
            ->toResponse();
    }
}
