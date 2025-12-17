<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users with filters and search.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Search by name, username, or phone
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Paginate with 15 per page
        $users = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Display the specified user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'user_name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => [
                'sometimes',
                'string',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'sometimes|in:follower,celebrity',
            'gender' => 'sometimes|in:male,female,other',
            'verified' => 'sometimes|boolean',
            'completed' => 'sometimes|boolean',
            'is_admin' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:6',
        ]);

        // Update fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('user_name')) {
            $user->user_name = $request->user_name;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('role')) {
            $user->role = $request->role;
        }
        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }
        if ($request->has('verified')) {
            $user->verified = $request->verified;
        }
        if ($request->has('completed')) {
            $user->completed = $request->completed;
        }
        if ($request->has('is_admin')) {
            $user->is_admin = $request->is_admin;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Prevent deleting self
        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete yourself.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
