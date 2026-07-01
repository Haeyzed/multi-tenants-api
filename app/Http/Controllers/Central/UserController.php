<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Exports\Central\UsersExport;
use App\Exports\Central\UsersImportSample;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Central\Concerns\ExportsSpreadsheets;
use App\Http\Requests\Central\ExportResourceRequest;
use App\Http\Requests\Central\StoreUserRequest;
use App\Http\Requests\Central\UpdateUserRequest;
use App\Http\Resources\Central\CentralUserResource;
use App\Imports\Central\UsersImport;
use App\Models\Central\CentralUser;
use App\Services\Central\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Manages central platform administrator accounts.
 */
class UserController extends ApiController
{
    use ExportsSpreadsheets;

    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'is_active' => ['nullable', 'array'],
            'is_active.*' => ['string', 'in:active,inactive'],
        ]);

        $users = $this->userService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($users, CentralUserResource::collection($users), 'Users retrieved successfully.');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', CentralUser::class);

        $user = $this->userService->create($request->validated());

        return $this->created(
            new CentralUserResource($user),
            'User created successfully.',
        );
    }

    /**
     * Display the specified user.
     */
    public function show(CentralUser $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(new CentralUserResource($this->userService->find($user->id)), 'User retrieved successfully.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, CentralUser $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->userService->update($user, $request->validated());

        return $this->updated(
            new CentralUserResource($user),
            'User updated successfully.',
        );
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(CentralUser $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return $this->deleted('User deleted successfully.');
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        return $this->success($this->userService->statistics(), 'User statistics retrieved successfully.');
    }

    /**
     * Get user options.
     */
    public function options(): JsonResponse
    {
        $this->authorize('viewAny', CentralUser::class);

        return $this->success($this->userService->getOptions(), 'User options retrieved successfully.');
    }

    /**
     * Delete multiple users.
     */
    public function destroyMany(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', CentralUser::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $count = $this->userService->deleteMany($validated['ids'], (int) $request->user()?->id);

        return $this->success(null, "{$count} users deleted successfully.");
    }

    /**
     * Export users to Excel.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', CentralUser::class);

        $validated = $request->validate(ExportResourceRequest::rules(
            UsersExport::availableColumns(),
            ['integer', 'exists:users,id'],
        ));

        $users = $this->userService->exportQuery(
            $validated['ids'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        $export = new UsersExport($users, $validated['columns'] ?? null);

        return $this->spreadsheetExport(
            $request,
            $export,
            'users-export',
            'Users Export',
            'Your users export is attached.',
        );
    }

    /**
     * Download a sample import template for users.
     */
    public function importSample(Request $request): BinaryFileResponse
    {
        $this->authorize('create', CentralUser::class);

        return $this->importSampleDownload($request, new UsersImportSample(), 'users');
    }

    /**
     * Import users from Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', CentralUser::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new UsersImport, $request->file('file'));

        return $this->success(null, 'Users imported successfully.');
    }
}
